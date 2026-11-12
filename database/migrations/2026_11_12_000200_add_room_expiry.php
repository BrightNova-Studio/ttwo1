<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $t) {
            $t->timestamp('expires_at')->nullable()->after('status'); // operator can end a room automatically on a date
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $t) {
            $t->dropColumn('expires_at');
        });
    }
};
