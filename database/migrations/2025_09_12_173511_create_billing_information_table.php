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
        Schema::create('billing_information', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->enum('type', ['BANK_ACCOUNT', 'EWALLET'])->default('BANK_ACCOUNT');
            $table->json('details');
            $table->boolean('default')->default(false);
            $table->timestamps();
        });

        // add foreign key to bills
        Schema::table('bills', function (Blueprint $table) {
            $table->foreignUuid('billing_information_id')
                ->after('bill_category_id')
                ->constrained('billing_information')
                ->onDelete('cascade');
        });

        // add foreign key to bill_series
        Schema::table('bill_series', function (Blueprint $table) {
            $table->foreignUuid('billing_information_id')
                ->after('bill_category_id')
                ->constrained('billing_information')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_information');
    }
};
