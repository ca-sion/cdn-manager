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
        Schema::create('runs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('distance', 8, 2)->nullable();
            $table->decimal('cost', 8, 2)->nullable();
            $table->json('available_for_types')->nullable();
            $table->json('start_blocs')->nullable();
            $table->date('registrations_deadline')->nullable();
            $table->integer('registrations_limit')->nullable();
            $table->integer('registrations_number')->nullable();
            $table->string('datasport_code')->nullable();
            $table->string('code')->nullable();
            $table->boolean('accepts_voucher')->default(false);
            $table->foreignId('provision_id')->nullable()->constrained('provisions')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('runs');
    }
};
