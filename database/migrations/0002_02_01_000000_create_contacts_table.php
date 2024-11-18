<?php

use Illuminate\Support\Facades\DB;
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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            match (DB::connection($this->getConnection())->getDriverName()) {
                'mysql'  => $table->string('name')->virtualAs("CONCAT(first_name, ' ', last_name)"),
                'pgsql'  => $table->string('name')->virtualAs("CONCAT(first_name, ' ', last_name)"),
                'sqlite' => $table->string('name')->virtualAs("first_name || ' ' || last_name"),
            };
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('role')->nullable();
            $table->string('department')->nullable();
            $table->string('address')->nullable();
            $table->string('address_extension')->nullable();
            $table->string('locality')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code')->nullable();
            $table->string('salutation')->nullable();
            $table->string('language')->nullable();

            $table->foreignId('category_id')->nullable()->constrained('contact_categories')->cascadeOnUpdate()->nullOnDelete();

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
        Schema::dropIfExists('contacts');
    }
};
