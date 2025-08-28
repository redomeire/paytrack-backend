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
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('bill_category_id')->constrained();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->double('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->enum('billing_type', ['fixed', 'recurring'])->default('fixed');
            $table->enum('frequency', ['monthly', 'annual', 'custom'])->nullable();
            $table->integer('custom_frequency_days')->nullable();
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->text('attachment_url')->nullable();
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
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
