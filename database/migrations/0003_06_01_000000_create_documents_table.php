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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnUpdate()->nullOnDelete();

            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->date('date')->nullable();
            $table->string('validity_year_start')->nullable();
            $table->string('validity_year_end')->nullable();
            $table->json('medias')->nullable();

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
        Schema::dropIfExists('documents');
    }
};
