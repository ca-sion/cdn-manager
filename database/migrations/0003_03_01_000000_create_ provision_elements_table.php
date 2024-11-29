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
        Schema::create('provision_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('provision_id')->nullable()->constrained('provisions')->cascadeOnUpdate()->nullOnDelete();
            $table->nullableMorphs('recipient');

            $table->string('status')->nullable();
            $table->string('secondary_status')->nullable();
            $table->date('due_date')->nullable();
            $table->string('precision')->nullable();
            $table->float('numeric_indicator')->nullable();
            $table->string('textual_indicator')->nullable();
            $table->string('goods_to_be_delivered')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->cascadeOnUpdate()->nullOnDelete();
            $table->string('contact_text')->nullable();
            $table->string('contact_location')->nullable();
            $table->date('contact_date')->nullable();
            $table->time('contact_time')->nullable();
            $table->json('placeholders')->nullable();
            $table->json('medias')->nullable();
            $table->string('media_status')->nullable();
            $table->string('responsible')->nullable();
            $table->foreignId('dicastry_id')->nullable()->constrained('dicastries')->cascadeOnUpdate()->nullOnDelete();
            $table->string('tracking_status')->nullable();
            $table->date('tracking_date')->nullable();
            $table->string('accreditation_type')->nullable();

            $table->boolean('has_product')->nullable();
            $table->integer('quantity')->nullable()->default(1);
            $table->string('unit')->nullable();
            $table->float('cost')->nullable();
            $table->float('tax_rate')->nullable();
            $table->float('discount')->nullable();
            $table->boolean('include_vat')->nullable();

            $table->string('vip_category')->nullable();
            $table->integer('vip_invitation_number')->nullable();
            $table->string('vip_response_status')->nullable();
            $table->json('vip_guests')->nullable();

            $table->integer('order_column')->nullable();
            $table->string('note', 40000)->nullable();
            $table->json('content')->nullable();
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
        Schema::dropIfExists('provision_elements');
    }
};
