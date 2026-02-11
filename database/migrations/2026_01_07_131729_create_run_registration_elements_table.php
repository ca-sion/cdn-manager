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
        Schema::create('run_registration_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_registration_id')->constrained('run_registrations')->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable();
            $table->string('nationality')->nullable();
            $table->string('email')->nullable();
            $table->string('team')->nullable();

            $table->foreignId('run_id')->nullable()->constrained('runs')->nullOnDelete();
            $table->string('run_name')->nullable();
            $table->string('bloc')->nullable();
            $table->boolean('with_video')->default(false);
            $table->string('voucher_code')->nullable();

            // Address details (Elite)
            $table->string('address')->nullable();
            $table->string('address_extension')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('locality')->nullable();
            $table->string('country')->nullable();

            // Payment (Elite)
            $table->string('iban')->nullable();
            $table->text('payment_note')->nullable();

            // Fees & Bonuses
            $table->boolean('has_free_registration_fee')->default(false);
            $table->boolean('has_bonus_start')->default(false);
            $table->decimal('bonus_start_amount', 8, 2)->nullable();
            $table->decimal('bonus_ranking_amount', 8, 2)->nullable();
            $table->decimal('bonus_arrival_amount', 8, 2)->nullable();

            // Accommodation
            $table->boolean('has_accommodation')->default(false);
            $table->boolean('accommodation_friday')->default(false);
            $table->boolean('accommodation_saturday')->default(false);
            $table->text('accommodation_precision')->nullable();

            // Expenses
            $table->boolean('has_expense_reimbursement')->default(false);
            $table->text('expense_reimbursement_precision')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('run_registration_elements');
    }
};
