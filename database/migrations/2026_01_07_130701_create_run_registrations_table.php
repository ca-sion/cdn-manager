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
        Schema::create('run_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('type'); // Enum
            
            // Invoicing Details
            $table->string('invoicing_company_name')->nullable();
            $table->string('invoicing_address')->nullable();
            $table->string('invoicing_address_extension')->nullable();
            $table->string('invoicing_postal_code')->nullable();
            $table->string('invoicing_locality')->nullable();
            $table->string('invoicing_email')->nullable();
            $table->text('invoicing_note')->nullable();
            
            // Payment Info
            $table->string('payment_iban')->nullable();
            $table->text('payment_note')->nullable();
            
            // Company specific
            $table->string('company_name')->nullable();
            
            // School specific
            $table->string('school_name')->nullable();
            $table->string('school_postal_code')->nullable();
            $table->string('school_locality')->nullable();
            $table->string('school_country')->nullable();
            $table->string('school_class_level')->nullable();
            $table->string('school_class_holder_first_name')->nullable();
            $table->string('school_class_holder_last_name')->nullable();
            $table->string('school_class_holder_email')->nullable();
            $table->string('school_class_holder_phone')->nullable();
            
            // Contact Details
            $table->string('contact_first_name')->nullable();
            $table->string('contact_last_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('run_registrations');
    }
};