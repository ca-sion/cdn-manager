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
        Schema::create('client_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->integer('order_column')->nullable();
            $table->string('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_contact');
    }
};
