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
        Schema::create('client_provisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('provision_id')->nullable()->constrained('provisions')->cascadeOnUpdate()->nullOnDelete();

            $table->string('name')->nullable();
            $table->string('status')->nullable();
            $table->string('status_text')->nullable();
            $table->string('descriptor')->nullable();
            $table->string('inscription')->nullable();
            $table->float('number')->nullable();
            $table->string('number_text')->nullable();
            $table->string('responsible')->nullable();
            $table->string('contact_text')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->cascadeOnUpdate()->nullOnDelete();
            $table->string('location')->nullable();
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->date('date_text')->nullable();
            $table->string('text')->nullable();
            $table->json('medias')->nullable();

            $table->boolean('has_product')->nullable();
            $table->integer('quantity')->nullable()->default(1);
            $table->string('unit')->nullable();
            $table->float('price')->nullable();
            $table->float('tax_rate')->nullable();
            $table->float('discount')->nullable();
            $table->boolean('include_vat')->nullable();

            $table->string('note')->nullable();
            $table->json('meta')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_provision');
    }
};
