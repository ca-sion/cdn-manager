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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('long_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->string('address_extension')->nullable();
            $table->string('locality')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code')->nullable();
            $table->string('iban')->nullable();
            $table->string('iban_qr')->nullable();
            $table->string('ide')->nullable();
            $table->string('logo')->nullable();
            $table->string('invoice_email')->nullable();
            $table->string('invoice_note')->nullable();
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
        Schema::dropIfExists('clients');
    }
};
