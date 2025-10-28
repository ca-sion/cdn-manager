<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_engagements', function (Blueprint $table) {
            $table->timestamp('sent_at')->after('note')->nullable();
            $table->timestamp('viewed_at')->after('sent_at')->nullable();
            $table->timestamp('relaunched_at')->after('viewed_at')->nullable();
            $table->timestamp('cancelled_at')->after('relaunched_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_engagements', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'viewed_at', 'relaunched_at', 'cancelled_at']);
        });
    }
};
