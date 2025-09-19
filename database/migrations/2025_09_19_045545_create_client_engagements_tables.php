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
        Schema::create('client_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('stage')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('responsible_contact_id')->nullable()->constrained('contacts')->cascadeOnUpdate()->nullOnDelete();
            $table->string('responsible')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'edition_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('delivery_method')->after('status')->nullable(); // email, post, manual
            $table->foreignId('responsible_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
        });

        Schema::table('provision_elements', function (Blueprint $table) {
            $table->boolean('is_paid')->after('status')->default(false); // if managed without invoice
            $table->string('delivery_method')->after('is_paid')->nullable();  // email, post, manual
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_engagements');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['responsible_contact_id']);
            $table->dropColumn('responsible_contact_id');
            $table->dropColumn('delivery_method');
        });

        Schema::table('provision_elements', function (Blueprint $table) {
            $table->dropColumn('delivery_method');
            $table->dropColumn('is_paid');
        });
    }
};
