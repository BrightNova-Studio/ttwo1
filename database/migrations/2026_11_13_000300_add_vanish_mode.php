<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $t) {
            $t->boolean('vanish')->default(false);      // Vanish mode: new messages erase themselves after being seen
        });
        Schema::table('messages', function (Blueprint $t) {
            $t->boolean('vanish')->default(false);      // sent while Vanish mode was on
        });
    }

    public function down(): void
    {
        Schema::table('messages', fn (Blueprint $t) => $t->dropColumn('vanish'));
        Schema::table('rooms', fn (Blueprint $t) => $t->dropColumn('vanish'));
    }
};
