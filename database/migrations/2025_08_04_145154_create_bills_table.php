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
        Schema::create('bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained();
            $table->foreignUuid('bill_category_id')->constrained();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->double('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->enum('billing_type', ['fixed', 'recurring'])->default('fixed');
            $table->enum('frequency', ['monthly', 'annual', 'custom'])->default('monthly');
            $table->integer('custom_frequency_days')->default(1);
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->text('attachment_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
