<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $t) {
            // Did the first member choose a secret phrase? null = not decided yet. Existing rooms all have one.
            $t->boolean('phrase_set')->nullable();
        });
        \DB::table('rooms')->whereNotNull('key_check')->update(['phrase_set' => true]);
    }

    public function down(): void
    {
        Schema::table('rooms', fn (Blueprint $t) => $t->dropColumn('phrase_set'));
    }
};
