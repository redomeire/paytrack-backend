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
        Schema::create('monthly_category_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->unique();
            $table->foreignUuid('bill_category_id')->constrained('bill_categories')->unique();
            $table->string('currency', 5);
            $table->tinyInteger('summary_year')->unique();
            $table->tinyInteger('summary_month')->unique();
            $table->decimal('total_amount_spent', 15, 2);
            $table->integer('bill_count');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_category_summaries');
    }
};
