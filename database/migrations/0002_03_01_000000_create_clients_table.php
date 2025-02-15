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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('long_name')->nullable();
            $table->string('type')->nullable();
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
            $table->string('invoicing_name')->nullable();
            $table->string('invoicing_email')->nullable();
            $table->string('invoicing_address')->nullable();
            $table->string('invoicing_address_extension')->nullable();
            $table->string('invoicing_address_extension_two')->nullable();
            $table->string('invoicing_postal_code')->nullable();
            $table->string('invoicing_locality')->nullable();
            $table->string('invoicing_country')->nullable();
            $table->string('invoicing_note')->nullable();

            $table->foreignId('category_id')->nullable()->constrained('client_categories')->cascadeOnUpdate()->nullOnDelete();

            $table->integer('order_column')->nullable();
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
        Schema::dropIfExists('clients');
    }
};
