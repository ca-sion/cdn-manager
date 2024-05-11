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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnUpdate()->nullOnDelete();
            $table->string('status')->nullable();
            $table->string('title')->nullable();
            $table->string('number')->nullable();
            $table->date('date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('reference')->nullable();
            $table->string('client_reference')->nullable();
            $table->boolean('is_pro_forma')->nullable();

            $table->boolean('include_vat')->nullable();
            $table->string('total_include_vat')->nullable();
            $table->string('total_exclude_vat')->nullable();
            $table->string('currency')->nullable();

            $table->json('positions')->nullable();
            $table->json('payment_instructions')->nullable();

            $table->string('content')->nullable();
            $table->string('footer')->nullable();
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
        Schema::dropIfExists('invoices');
    }
};
