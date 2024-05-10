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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->cascadeOnUpdate()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('code')->nullable();
            $table->string('unit')->nullable();
            $table->float('price')->nullable();
            $table->float('tax_rate')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
