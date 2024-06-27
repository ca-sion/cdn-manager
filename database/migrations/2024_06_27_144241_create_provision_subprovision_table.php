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
        Schema::create('provision_subprovision', function (Blueprint $table) {
            $table->foreignId('provision_id')->constrained('provisions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('subprovision_id')->constrained('provisions')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provision_subprovision');
    }
};
