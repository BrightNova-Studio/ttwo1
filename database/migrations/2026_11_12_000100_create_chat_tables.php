<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $t) {
            $t->id();
            $t->string('chat_hash', 64)->unique();
            $t->string('label', 120)->nullable();          // operator's own note, e.g. a customer reference
            $t->string('status', 12)->default('active');   // active | disabled
            $t->text('key_check')->nullable();             // encrypted known value so clients can verify the shared secret
            $t->unsignedInteger('disappear_seconds')->default(0);
            $t->unsignedBigInteger('rev')->default(0);     // bumped on every change; clients poll "since rev"
            $t->unsignedInteger('epoch')->default(0);      // bumped when the chat is cleared
            $t->timestamp('last_activity_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('room_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('slot');               // 1 or 2
            $t->string('id_hash', 64);
            $t->text('nick_enc')->nullable();              // nickname, encrypted in the browser
            $t->string('token_hash', 64)->nullable()->index();
            $t->unsignedInteger('sessions')->default(0);   // how many times this member has signed in
            $t->timestamp('joined_at')->nullable();
            $t->timestamp('last_seen_at')->nullable();
            $t->timestamp('typing_until')->nullable();
            $t->timestamps();
            $t->unique(['room_id', 'slot']);
        });

        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('room_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('sender_slot');
            $t->longText('body_enc')->nullable();          // ciphertext only. NULL once unsent or view-once opened.
            $t->boolean('view_once')->default(false);
            $t->timestamp('viewed_at')->nullable();
            $t->timestamp('unsent_at')->nullable();
            $t->unsignedBigInteger('reply_to')->nullable();
            $t->text('react_1')->nullable();               // reactions are encrypted too
            $t->text('react_2')->nullable();
            $t->unsignedBigInteger('rev')->index();
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamp('seen_at')->nullable();
            $t->timestamps();
            $t->index(['room_id', 'rev']);
        });

        Schema::create('operator_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 60);
            $t->string('room_label', 120)->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_logs');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('members');
        Schema::dropIfExists('rooms');
    }
};
