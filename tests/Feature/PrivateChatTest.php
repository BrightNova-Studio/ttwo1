<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use App\Support\Ids;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PrivateChatTest extends TestCase
{
    use RefreshDatabase;

    private function makeRoom(): array
    {
        $chat = Ids::freshChat();
        [$m1, $m2] = Ids::memberPair();
        $room = Room::create(['chat_hash' => Ids::hash($chat)]);
        $room->members()->create(['slot' => 1, 'id_hash' => Ids::hash($m1)]);
        $room->members()->create(['slot' => 2, 'id_hash' => Ids::hash($m2)]);

        return [$room, $chat, $m1, $m2];
    }

    private function enter(string $chat, string $member)
    {
        return $this->postJson('/api/enter', ['chat_id' => $chat, 'member_id' => $member]);
    }

    private function as(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_entry_page_is_hardened_and_sets_no_cookie(): void
    {
        $r = $this->get('/');
        $r->assertOk();
        $r->assertHeader('Referrer-Policy', 'no-referrer');
        $r->assertHeader('X-Frame-Options', 'DENY');
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
        $this->assertStringContainsString("default-src 'none'", $r->headers->get('Content-Security-Policy'));
        $this->assertCount(0, $r->headers->getCookies(), 'members must never receive a cookie');
    }

    public function test_members_get_in_with_the_right_ids_and_wrong_ones_are_refused(): void
    {
        [, $chat, $m1] = $this->makeRoom();

        $this->enter($chat, $m1)->assertOk()->assertJsonStructure(['token', 'slot', 'room', 'peer']);
        $this->enter($chat, '000-000')->assertStatus(401);
        $this->enter('ZZ-0000', $m1)->assertStatus(401);
        // lower case and missing dashes are fine
        $this->enter(strtolower(str_replace('-', '', $chat)), $m1)->assertOk();
    }

    public function test_ids_are_not_stored_in_readable_form(): void
    {
        [$room, $chat, $m1] = $this->makeRoom();
        $this->assertStringNotContainsString(Ids::normalize($chat), $room->chat_hash);
        $this->assertStringNotContainsString(Ids::normalize($m1), (string) $room->members()->first()->id_hash);
    }

    public function test_repeated_failures_lock_the_room_even_for_the_right_id(): void
    {
        [, $chat, $m1] = $this->makeRoom();
        config(['chat.max_failures' => 3]);
        Cache::flush();

        for ($i = 0; $i < 3; $i++) {
            $this->enter($chat, '000-000')->assertStatus(401);
        }
        $this->enter($chat, $m1)->assertStatus(429);
    }

    public function test_one_session_per_member_a_new_sign_in_ends_the_old_one(): void
    {
        [, $chat, $m1] = $this->makeRoom();
        $a = $this->enter($chat, $m1)->json('token');
        $this->getJson('/api/poll', $this->as($a))->assertOk();

        $b = $this->enter($chat, $m1)->json('token');
        $this->getJson('/api/poll', $this->as($a))->assertStatus(401);
        $this->getJson('/api/poll', $this->as($b))->assertOk();
    }

    public function test_messages_flow_between_the_two_members_as_ciphertext(): void
    {
        [, $chat, $m1, $m2] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');
        $t2 = $this->enter($chat, $m2)->json('token');

        $send = $this->postJson('/api/messages', ['body_enc' => 'CIPHERTEXT-A'], $this->as($t1))->assertCreated();
        $id = $send->json('message.id');

        $poll = $this->getJson('/api/poll?since=0', $this->as($t2))->assertOk();
        $this->assertSame('CIPHERTEXT-A', $poll->json('messages.0.body_enc'));
        $this->assertFalse($poll->json('messages.0.mine'));

        // delivered flows back to the sender, then seen
        $this->postJson('/api/messages/seen', ['up_to' => $id], $this->as($t2))->assertOk();
        $back = $this->getJson('/api/poll?since=0', $this->as($t1))->json('messages.0');
        $this->assertTrue($back['delivered']);
        $this->assertTrue($back['seen']);
    }

    public function test_an_outsider_with_a_token_for_another_room_sees_nothing_of_this_one(): void
    {
        [, $chat, $m1] = $this->makeRoom();
        [, $chat2, $other1] = $this->makeRoom();
        $mine = $this->enter($chat, $m1)->json('token');
        $theirs = $this->enter($chat2, $other1)->json('token');

        $this->postJson('/api/messages', ['body_enc' => 'SECRET'], $this->as($mine))->assertCreated();
        $poll = $this->getJson('/api/poll?since=0', $this->as($theirs))->assertOk();
        $this->assertCount(0, $poll->json('messages'));
    }

    public function test_view_once_body_is_hidden_from_polls_and_destroyed_after_one_open(): void
    {
        [, $chat, $m1, $m2] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');
        $t2 = $this->enter($chat, $m2)->json('token');

        $id = $this->postJson('/api/messages', ['body_enc' => 'ONCE', 'view_once' => true], $this->as($t1))->json('message.id');

        $poll = $this->getJson('/api/poll?since=0', $this->as($t2))->json('messages.0');
        $this->assertNull($poll['body_enc']);
        $this->assertTrue($poll['view_once']);

        // the sender cannot open their own view-once message
        $this->postJson("/api/messages/{$id}/view", [], $this->as($t1))->assertStatus(410);

        $this->postJson("/api/messages/{$id}/view", [], $this->as($t2))->assertOk()->assertJson(['body_enc' => 'ONCE']);
        $this->postJson("/api/messages/{$id}/view", [], $this->as($t2))->assertStatus(410);
        $this->assertNull(Message::find($id)->body_enc, 'ciphertext must be erased from the database after viewing');
    }

    public function test_either_member_can_clear_the_chat_for_both(): void
    {
        [$room, $chat, $m1, $m2] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');
        $t2 = $this->enter($chat, $m2)->json('token');
        $this->postJson('/api/messages', ['body_enc' => 'one'], $this->as($t1));
        $this->postJson('/api/messages', ['body_enc' => 'two'], $this->as($t2));

        $this->postJson('/api/room/clear', [], $this->as($t2))->assertOk();

        $this->assertSame(0, Message::where('room_id', $room->id)->count());
        $this->assertSame(1, $this->getJson('/api/poll?since=0', $this->as($t1))->json('room.epoch'));
    }

    public function test_unsend_only_works_on_your_own_message_and_erases_the_body(): void
    {
        [, $chat, $m1, $m2] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');
        $t2 = $this->enter($chat, $m2)->json('token');
        $id = $this->postJson('/api/messages', ['body_enc' => 'oops'], $this->as($t1))->json('message.id');

        $this->deleteJson("/api/messages/{$id}", [], $this->as($t2))->assertStatus(404);
        $this->deleteJson("/api/messages/{$id}", [], $this->as($t1))->assertOk();
        $this->assertNull(Message::find($id)->body_enc);
    }

    public function test_disappearing_messages_expire_and_are_purged(): void
    {
        [$room, $chat, $m1] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');
        $this->postJson('/api/room/disappear', ['seconds' => 30], $this->as($t1))->assertOk();
        $this->postJson('/api/room/disappear', ['seconds' => 7], $this->as($t1))->assertStatus(422);

        $id = $this->postJson('/api/messages', ['body_enc' => 'temp'], $this->as($t1))->json('message.id');
        $this->assertNotNull(Message::find($id)->expires_at);

        $this->travel(2)->minutes();
        $room->members()->update(['last_seen_at' => now()]);
        $this->getJson('/api/poll?since=0', $this->as($t1))->assertOk();
        $this->assertNull(Message::find($id));
    }

    public function test_disabled_room_locks_everyone_out(): void
    {
        [$room, $chat, $m1] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');
        $room->update(['status' => 'disabled']);

        $this->getJson('/api/poll', $this->as($t1))->assertStatus(403);
        $this->enter($chat, $m1)->assertStatus(401);
    }

    public function test_an_idle_session_expires(): void
    {
        [, $chat, $m1] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');
        $this->travel(5)->minutes();
        $this->getJson('/api/poll', $this->as($t1))->assertStatus(401);
    }

    public function test_leaving_kills_the_token(): void
    {
        [, $chat, $m1] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');
        $this->postJson('/api/leave', [], $this->as($t1))->assertOk();
        $this->getJson('/api/poll', $this->as($t1))->assertStatus(401);
    }

    public function test_first_secret_check_wins_and_the_second_member_gets_it_back(): void
    {
        [, $chat, $m1, $m2] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');
        $this->postJson('/api/key-check', ['key_check' => 'CHECK-1'], $this->as($t1))->assertOk();

        $enter2 = $this->enter($chat, $m2);
        $this->assertSame('CHECK-1', $enter2->json('key_check'));
        $t2 = $enter2->json('token');
        $this->postJson('/api/key-check', ['key_check' => 'CHECK-2'], $this->as($t2))->assertStatus(409)->assertJson(['key_check' => 'CHECK-1']);
    }

    /* ----------------------------------------------------------- operator */

    public function test_operator_creates_a_room_and_credentials_work_but_are_never_stored_readable(): void
    {
        $op = User::factory()->create();

        $resp = $this->actingAs($op)->post('/operator/rooms', ['label' => 'Acme']);
        $resp->assertRedirect(route('operator.dashboard'));
        $c = session('credentials');
        $this->assertNotEmpty($c['chat_id']);

        $this->enter($c['chat_id'], $c['member_1'])->assertOk();
        $this->enter($c['chat_id'], $c['member_2'])->assertOk();

        $room = Room::first();
        $this->assertStringNotContainsString(Ids::normalize($c['chat_id']), json_encode([$room->toArray(), $room->members->makeVisible(['id_hash'])->toArray()]));
    }

    public function test_operator_dashboard_never_exposes_message_content(): void
    {
        $op = User::factory()->create();
        [$room, $chat, $m1] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');
        $this->postJson('/api/messages', ['body_enc' => 'TOP-SECRET-CIPHERTEXT'], $this->as($t1));
        $room->members()->where('slot', 1)->update(['nick_enc' => 'NICK-CIPHERTEXT']);

        $html = $this->actingAs($op)->get('/operator')->assertOk()->getContent();
        $this->assertStringNotContainsString('TOP-SECRET-CIPHERTEXT', $html);
        $this->assertStringNotContainsString('NICK-CIPHERTEXT', $html);
    }

    public function test_operator_pages_need_a_login_and_members_cannot_use_them(): void
    {
        $this->get('/operator')->assertRedirect(route('operator.login'));
        $this->post('/operator/rooms', [])->assertRedirect(route('operator.login'));
    }

    public function test_reissue_replaces_the_member_id_and_ends_their_session(): void
    {
        $op = User::factory()->create();
        [$room, $chat, $m1] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');

        $this->actingAs($op)->post("/operator/rooms/{$room->id}/reissue/1")->assertRedirect();
        $new = session('credentials')['member_1'];

        $this->enter($chat, $m1)->assertStatus(401);
        $this->getJson('/api/poll', $this->as($t1))->assertStatus(401);
        $this->enter($chat, $new)->assertOk();
    }

    public function test_operator_can_edit_a_room_and_set_an_end_date_that_locks_it(): void
    {
        $op = User::factory()->create();
        [$room, $chat, $m1] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');

        $this->actingAs($op)->patch("/operator/rooms/{$room->id}", [
            'label' => 'Renamed', 'disappear_seconds' => 300, 'expires_at' => now()->addDay()->toDateString(),
        ])->assertRedirect();
        $room->refresh();
        $this->assertSame('Renamed', $room->label);
        $this->assertSame(300, $room->disappear_seconds);
        $this->getJson('/api/poll', $this->as($t1))->assertOk()->assertJsonPath('room.disappear', 300);

        $this->travel(2)->days();
        $room->members()->update(['last_seen_at' => now()]);
        $this->getJson('/api/poll', $this->as($t1))->assertStatus(403);
        $this->enter($chat, $m1)->assertStatus(401);
    }

    public function test_operator_can_sign_members_out(): void
    {
        $op = User::factory()->create();
        [$room, $chat, $m1, $m2] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');
        $t2 = $this->enter($chat, $m2)->json('token');

        $this->actingAs($op)->post("/operator/rooms/{$room->id}/kick/1")->assertRedirect();
        $this->getJson('/api/poll', $this->as($t1))->assertStatus(401);
        $this->getJson('/api/poll', $this->as($t2))->assertOk();

        $this->actingAs($op)->post("/operator/rooms/{$room->id}/kick/all")->assertRedirect();
        $this->getJson('/api/poll', $this->as($t2))->assertStatus(401);
    }

    public function test_reset_issues_all_new_ids_erases_messages_and_clears_the_secret(): void
    {
        $op = User::factory()->create();
        [$room, $chat, $m1, $m2] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');
        $this->postJson('/api/key-check', ['key_check' => 'OLD'], $this->as($t1));
        $this->postJson('/api/messages', ['body_enc' => 'old'], $this->as($t1));

        $this->actingAs($op)->post("/operator/rooms/{$room->id}/reset")->assertRedirect();
        $c = session('credentials');

        $this->assertSame(0, Message::count());
        $this->assertNull($room->fresh()->key_check);
        $this->enter($chat, $m1)->assertStatus(401);
        $this->getJson('/api/poll', $this->as($t1))->assertStatus(401);
        $this->enter($c['chat_id'], $c['member_1'])->assertOk();
        $this->enter($c['chat_id'], $c['member_2'])->assertOk();
    }

    public function test_operator_accounts_can_be_added_and_removed_but_not_the_last_or_self(): void
    {
        $op = User::factory()->create();

        $this->actingAs($op)->post('/operator/operators', ['name' => 'Second', 'email' => 'second@example.com', 'password' => 'a-long-password-1'])->assertRedirect();
        $second = User::where('email', 'second@example.com')->firstOrFail();

        $this->actingAs($op)->delete("/operator/operators/{$op->id}")->assertSessionHasErrors('operators');
        $this->actingAs($op)->delete("/operator/operators/{$second->id}")->assertRedirect();
        $this->assertSame(1, User::count());
        $this->actingAs($op)->post('/operator/operators', ['name' => 'x', 'email' => 'x@example.com', 'password' => 'short'])->assertSessionHasErrors('password');
    }

    public function test_operator_can_disable_wipe_and_delete_but_wipe_leaves_no_messages(): void
    {
        $op = User::factory()->create();
        [$room, $chat, $m1] = $this->makeRoom();
        $t1 = $this->enter($chat, $m1)->json('token');
        $this->postJson('/api/messages', ['body_enc' => 'x'], $this->as($t1));

        $this->actingAs($op)->post("/operator/rooms/{$room->id}/wipe")->assertRedirect();
        $this->assertSame(0, Message::count());

        $this->actingAs($op)->post("/operator/rooms/{$room->id}/toggle")->assertRedirect();
        $this->assertSame('disabled', $room->fresh()->status);

        $this->actingAs($op)->delete("/operator/rooms/{$room->id}")->assertRedirect();
        $this->assertSame(0, Room::count());
    }

    /* ------------------------------------------------------ modes & expiry */

    private function selfRoom(string $mode = 'temp'): array
    {
        $r = $this->postJson('/api/rooms', ['mode' => $mode])->assertCreated()->json();

        return [$r['key'], $r['you'], $r['partner']];
    }

    public function test_anyone_can_create_a_temp_or_long_room_and_both_members_get_in(): void
    {
        [$key, $you, $partner] = $this->selfRoom('temp');
        $this->assertMatchesRegularExpression('/^[A-Z]{2}-\d{4}$/', $key);
        $this->assertNotSame($you, $partner);
        $this->enter($key, $you)->assertOk();
        $this->enter($key, $partner)->assertOk();

        [$k2] = $this->selfRoom('long');
        $this->assertSame(['temp', 'long'], Room::orderBy('id')->pluck('mode')->all());
        $this->assertSame(2, \App\Models\RoomEvent::where('event', 'created')->where('source', 'self')->count());
        $this->assertNotSame($key, $k2);
        $this->postJson('/api/rooms', ['mode' => 'forever'])->assertStatus(422);
    }

    public function test_self_service_codes_are_only_stored_as_hashes(): void
    {
        [$key, $you] = $this->selfRoom();
        $room = Room::first();
        $dump = json_encode([$room->getAttributes(), $room->members()->get()->map->getAttributes()]);
        $this->assertStringNotContainsString(Ids::normalize($key), $dump);
        $this->assertStringNotContainsString(Ids::normalize($you), $dump);
    }

    public function test_temp_room_is_deleted_30_minutes_after_both_are_away_and_counted_once(): void
    {
        [$key, $you] = $this->selfRoom('temp');
        $room = Room::first();
        $this->travel(29)->minutes();
        \App\Support\RoomLifecycle::sweep();
        $this->assertSame(1, Room::count(), 'still alive at 29 minutes');

        $this->travel(2)->minutes();
        $this->assertSame(1, \App\Support\RoomLifecycle::sweep());
        $this->assertSame(0, Room::count());
        $this->assertSame(0, \App\Models\Member::count());
        $this->assertSame(1, \App\Models\RoomEvent::where('event', 'expired')->where('mode', 'temp')->count());
        $this->enter($key, $you)->assertStatus(401);
    }

    public function test_signing_in_resets_the_clock_and_a_long_room_outlives_a_temp_room(): void
    {
        [$key, $you] = $this->selfRoom('temp');
        [$lkey] = $this->selfRoom('long');

        $this->travel(20)->minutes();
        $token = $this->enter($key, $you)->assertOk()->json('token');   // clock resets here
        \App\Support\RoomLifecycle::sweep();
        $this->assertSame(2, Room::count(), 'temp room survived because of the sign-in, long room is untouched');

        // Staying online (polling) keeps the room alive far past 30 minutes.
        for ($i = 0; $i < 40; $i++) {
            $this->travel(1)->minutes();
            $this->getJson('/api/poll', $this->as($token))->assertOk();
            \App\Support\RoomLifecycle::sweep();
        }
        $this->assertSame(2, Room::count());

        // Both away: 29 minutes later it is still there, 2 more and it is gone.
        $this->travel(29)->minutes();
        \App\Support\RoomLifecycle::sweep();
        $this->assertSame(2, Room::count());
        $this->travel(2)->minutes();
        \App\Support\RoomLifecycle::sweep();
        $this->assertSame(['long'], Room::pluck('mode')->all());

        $this->travel(31)->days();
        \App\Support\RoomLifecycle::sweep();
        $this->assertSame(0, Room::count());
        $this->assertSame(1, \App\Models\RoomEvent::where('event', 'expired')->where('mode', 'long')->count());
    }

    public function test_knocking_on_a_lapsed_room_deletes_it_even_without_the_scheduler(): void
    {
        [$key, $you] = $this->selfRoom('temp');
        $this->travel(45)->minutes();
        $this->enter($key, $you)->assertStatus(401);
        $this->assertSame(0, Room::count());
    }

    public function test_room_creation_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < (int) config('chat.create_per_hour'); $i++) {
            $this->postJson('/api/rooms', ['mode' => 'temp'])->assertCreated();
        }
        $this->postJson('/api/rooms', ['mode' => 'temp'])->assertStatus(429);
    }

    public function test_operator_statistics_count_creations_expiries_and_removals_without_content(): void
    {
        $op = User::factory()->create();
        $this->selfRoom('temp');
        $this->selfRoom('long');
        $this->travel(31)->minutes();
        \App\Support\RoomLifecycle::sweep();                              // temp room expires
        $long = Room::first();
        $this->actingAs($op)->delete("/operator/rooms/{$long->id}")->assertRedirect();

        $r = $this->actingAs($op)->get('/operator/stats?range=7d')->assertOk();
        $r->assertSeeInOrder(['Rooms created', '2']);
        $this->assertSame(1, \App\Models\RoomEvent::where('event', 'expired')->count());
        $this->assertSame(1, \App\Models\RoomEvent::where('event', 'removed')->count());
        $this->actingAs($op)->get('/operator/stats?range=custom&from='.now()->subDays(3)->toDateString().'&to='.now()->toDateString())->assertOk();
        $this->actingAs($op)->get('/operator/stats?range=custom&from=2026-01-10&to=2026-01-01')->assertSessionHasErrors('to');
        auth()->logout();
        $this->get('/operator/stats')->assertRedirect();
    }

    public function test_operator_can_block_a_member_created_room(): void
    {
        $op = User::factory()->create();
        [$key, $you] = $this->selfRoom('long');
        $room = Room::first();
        $this->actingAs($op)->post("/operator/rooms/{$room->id}/toggle")->assertRedirect();
        $this->enter($key, $you)->assertStatus(401);
    }

    public function test_explainer_pages_render_and_set_no_cookie(): void
    {
        foreach (['/how-it-works', '/temp-vs-long', '/privacy', '/faq'] as $url) {
            $r = $this->get($url)->assertOk();
            $this->assertCount(0, $r->headers->getCookies());
        }
        $this->get('/temp-vs-long')->assertSee('30 minutes')->assertSee('30 days');
        $this->get('/')->assertSee('Secret Room Key')->assertSee('Create a room')->assertSee('/privacy', false);
    }

    public function test_ids_are_short_and_memorable(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}-\d{4}$/', Ids::chat());
            $this->assertMatchesRegularExpression('/^\d{3}-\d{3}$/', Ids::member());
            [$a, $b] = Ids::memberPair();
            $this->assertNotSame($a, $b);
        }
    }
}
