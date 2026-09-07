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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('postal_code')->nullable();
            $table->string('locality')->nullable();
            $table->string('country')->default('SUI');
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('run_registrations', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('school_name')->constrained('schools')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('run_registrations', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
        });

        Schema::dropIfExists('schools');
    }
};
