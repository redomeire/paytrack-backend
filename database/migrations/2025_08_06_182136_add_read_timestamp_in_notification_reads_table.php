<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notification_reads', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('is_read');
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('read_at');
            $table->dropColumn('is_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_reads', function (Blueprint $table) {
            //
        });
    }
};
