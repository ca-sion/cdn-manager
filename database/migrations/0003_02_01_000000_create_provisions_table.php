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
        Schema::create('provisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnUpdate()->nullOnDelete();

            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->integer('numeric_indicator')->nullable();
            $table->string('dimensions_indicator')->nullable();
            $table->string('format_indicator')->nullable();
            $table->string('due_date_indicator')->nullable();
            $table->string('contact_indicator')->nullable();
            $table->boolean('to_receive')->nullable();

            $table->boolean('has_content')->nullable();
            $table->boolean('has_due_date')->nullable();
            $table->boolean('has_precision')->nullable();
            $table->boolean('has_numeric_indicator')->nullable();
            $table->boolean('has_textual_indicator')->nullable();
            $table->boolean('has_product')->nullable();
            $table->boolean('has_contact')->nullable();
            $table->boolean('has_media')->nullable();
            $table->boolean('has_goods_to_be_delivered')->nullable();
            $table->boolean('has_responsible')->nullable();
            $table->boolean('has_tracking')->nullable();
            $table->boolean('has_accreditation')->nullable();
            $table->boolean('has_vip')->nullable();
            $table->boolean('has_placeholder')->nullable();
            $table->boolean('has_subprovision')->nullable();

            $table->foreignId('dicastry_id')->nullable()->constrained('dicastries')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('provision_categories')->cascadeOnUpdate()->nullOnDelete();
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
        Schema::dropIfExists('provisions');
    }
};
