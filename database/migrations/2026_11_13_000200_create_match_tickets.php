<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The waiting line for "Random connect". Short-lived: a ticket lives while its owner is looking,
        // and a matched ticket is erased within minutes. Holds no names, no accounts and no chat content.
        Schema::create('match_tickets', function (Blueprint $t) {
            $t->id();
            $t->string('token_hash', 64)->unique();
            $t->string('topic', 16)->default('any');
            $t->text('pub');                                   // browser's public key (ECDH), relayed to the match
            $t->string('status', 10)->default('waiting');      // waiting | matched
            $t->string('room_key', 8)->nullable();             // handed over once matched, then erased with the ticket
            $t->string('pass_code', 8)->nullable();
            $t->text('peer_pub')->nullable();
            $t->timestamp('last_poll_at')->nullable()->index();
            $t->timestamps();
            $t->index(['status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_tickets');
    }
};
