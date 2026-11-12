<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\OperatorLog;
use App\Models\Room;
use App\Models\User;
use App\Support\Ids;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The operator manages rooms and credentials. By design there is NO method here, and no
 * column the operator can reach, that returns message text: the server only holds ciphertext
 * and the decryption key exists only in the two members' browsers.
 */
class OperatorController extends Controller
{
    public function index()
    {
        $rooms = Room::with('members')->latest('id')->get();

        return view('operator.dashboard', [
            'rooms' => $rooms,
            'stats' => [
                'total' => $rooms->count(),
                'active' => $rooms->where('status', 'active')->count(),
                'disabled' => $rooms->where('status', 'disabled')->count(),
                'live' => $rooms->filter(fn ($r) => $r->members->contains(fn ($m) => $m->isOnline()))->count(),
            ],
            'supportEmail' => config('chat.support_email'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'disappear_seconds' => ['nullable', 'integer', Rule::in(config('chat.disappear_options'))],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $chatId = Ids::generate();
        $m1 = Ids::generate();
        $m2 = Ids::generate();

        $room = DB::transaction(function () use ($data, $chatId, $m1, $m2) {
            $room = Room::create([
                'chat_hash' => Ids::hash($chatId),
                'label' => $data['label'] ?? null,
                'disappear_seconds' => $data['disappear_seconds'] ?? 0,
                'expires_at' => $data['expires_at'] ?? null,
                'created_by' => Auth::id(),
            ]);
            $room->members()->create(['slot' => 1, 'id_hash' => Ids::hash($m1)]);
            $room->members()->create(['slot' => 2, 'id_hash' => Ids::hash($m2)]);

            return $room;
        });

        $this->log('room.created', $room);

        // Shown exactly once. After this redirect nobody, including the operator, can read them again.
        return redirect()->route('operator.dashboard')->with('credentials', [
            'label' => $room->label,
            'chat_id' => $chatId,
            'member_1' => $m1,
            'member_2' => $m2,
            'title' => 'New chat room ready',
        ]);
    }

    /** A member lost their ID: issue a new one. The old one stops working at once. */
    public function reissue(Room $room, int $slot): RedirectResponse
    {
        abort_unless(in_array($slot, [1, 2], true), 404);

        $new = Ids::generate();
        Member::where('room_id', $room->id)->where('slot', $slot)->update([
            'id_hash' => Ids::hash($new),
            'token_hash' => null,
        ]);

        $this->log('member.reissued', $room);

        return redirect()->route('operator.dashboard')->with('credentials', [
            'label' => $room->label,
            'chat_id' => null,
            'member_'.$slot => $new,
            'title' => 'New Member ID for member '.$slot,
        ]);
    }

    /** Edit the operator-side settings of a room: note, default disappearing timer, end date. */
    public function update(Request $request, Room $room): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'disappear_seconds' => ['required', 'integer', Rule::in(config('chat.disappear_options'))],
            'expires_at' => ['nullable', 'date'],
        ]);

        $room->mutate(fn () => $room->update([
            'label' => $data['label'] ?? null,
            'disappear_seconds' => $data['disappear_seconds'],
            'expires_at' => $data['expires_at'] ?? null,
        ]));
        $this->log('room.updated', $room);

        return back()->with('ok', 'Room updated.');
    }

    /** Sign one or both members out of every session, right now. */
    public function kick(Room $room, string $who): RedirectResponse
    {
        abort_unless(in_array($who, ['1', '2', 'all'], true), 404);

        $q = Member::where('room_id', $room->id);
        if ($who !== 'all') {
            $q->where('slot', (int) $who);
        }
        $q->update(['token_hash' => null, 'typing_until' => null, 'last_seen_at' => now()->subMinute()]);
        $this->log('member.signed_out', $room);

        return back()->with('ok', 'Signed out.');
    }

    /**
     * Start over: brand new Chat ID and Member IDs, all messages erased, secret phrase cleared.
     * For when a pair lost their secret phrase or a room was compromised.
     */
    public function reset(Room $room): RedirectResponse
    {
        $chatId = Ids::generate();
        $m1 = Ids::generate();
        $m2 = Ids::generate();

        $room->mutate(function () use ($room, $chatId, $m1, $m2) {
            $room->messages()->delete();
            $room->update(['chat_hash' => Ids::hash($chatId), 'key_check' => null, 'status' => 'active']);
            Room::whereKey($room->id)->increment('epoch');
            foreach ([1 => $m1, 2 => $m2] as $slot => $id) {
                Member::where('room_id', $room->id)->where('slot', $slot)->update([
                    'id_hash' => Ids::hash($id), 'token_hash' => null, 'nick_enc' => null,
                    'joined_at' => null, 'sessions' => 0, 'last_seen_at' => null, 'typing_until' => null,
                ]);
            }
        });
        $this->log('room.reset', $room);

        return redirect()->route('operator.dashboard')->with('credentials', [
            'label' => $room->label,
            'chat_id' => $chatId,
            'member_1' => $m1,
            'member_2' => $m2,
            'title' => 'Room reset: new IDs',
        ]);
    }

    public function toggle(Room $room): RedirectResponse
    {
        $room->update(['status' => $room->isActive() ? 'disabled' : 'active']);
        if (! $room->isActive()) {
            Member::where('room_id', $room->id)->update(['token_hash' => null]);
        }
        $this->log($room->isActive() ? 'room.enabled' : 'room.disabled', $room);

        return back()->with('ok', 'Room '.($room->isActive() ? 'enabled' : 'disabled').'.');
    }

    /** Deletes the stored (encrypted) messages. The operator can erase, never read. */
    public function wipe(Room $room): RedirectResponse
    {
        $room->mutate(function () use ($room) {
            $room->messages()->delete();
            Room::whereKey($room->id)->increment('epoch');
        });
        $this->log('room.wiped', $room);

        return back()->with('ok', 'All stored messages for that room were erased.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $this->log('room.deleted', $room);
        $room->delete();

        return redirect()->route('operator.dashboard')->with('ok', 'Room deleted.');
    }

    public function logs()
    {
        return view('operator.logs', ['logs' => OperatorLog::latest('id')->limit(200)->get()]);
    }

    public function settings()
    {
        return view('operator.settings', [
            'supportEmail' => config('chat.support_email'),
            'operators' => User::orderBy('id')->get(['id', 'name', 'email', 'created_at']),
        ]);
    }

    public function addOperator(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password'])]);
        $this->log('operator.added', null);

        return back()->with('ok', 'Operator added. Share the password with them privately.');
    }

    public function removeOperator(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['operators' => 'You cannot remove your own account.']);
        }
        if (User::count() <= 1) {
            return back()->withErrors(['operators' => 'At least one operator must remain.']);
        }
        $user->delete();
        $this->log('operator.removed', null);

        return back()->with('ok', 'Operator removed.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);
        $this->log('operator.password_changed', null);

        return back()->with('ok', 'Password changed.');
    }

    private function log(string $action, ?Room $room): void
    {
        OperatorLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'room_label' => $room?->label ?: ($room ? 'Room #'.$room->id : null),
            'ip' => request()->ip(),
        ]);
    }
}
