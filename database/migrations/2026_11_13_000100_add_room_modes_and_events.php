<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $t) {
            $t->string('mode', 8)->default('long')->after('status');       // temp | long
            $t->string('source', 10)->default('operator')->after('mode');  // self | operator
            $t->timestamp('last_active_at')->nullable()->index();          // last moment a member was present
        });

        DB::table('rooms')->update(['last_active_at' => DB::raw('created_at')]);

        // Anonymous life-cycle counters for the operator's statistics. No room id, no IDs, no content:
        // rooms are hard-deleted, so this is the only thing that remembers they existed.
        Schema::create('room_events', function (Blueprint $t) {
            $t->id();
            $t->string('event', 12);   // created | expired | removed
            $t->string('mode', 8);
            $t->string('source', 10)->default('self');
            $t->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_events');
        Schema::table('rooms', function (Blueprint $t) {
            $t->dropColumn(['mode', 'source', 'last_active_at']);
        });
    }
};
