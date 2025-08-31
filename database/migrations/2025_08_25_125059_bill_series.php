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
        Schema::create('bill_series', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('bill_category_id')->constrained();

            $table->string('name', 100);
            $table->text('description')->nullable();

            $table->double('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->enum('frequency', ['monthly', 'annual', 'custom']);
            $table->integer('custom_frequency_days')->nullable();
            $table->tinyInteger('frequency_interval')->default(1)->comment('Pengali frekuensi, misal: 5 untuk 5 tahun sekali');

            $table->tinyInteger('due_day')->comment('Tanggal jatuh tempo setiap periode (1-31)');
            $table->date('start_date');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->foreignUuid('bill_series_id')
                ->nullable()
                ->after('bill_category_id')
                ->constrained('bill_series')
                ->onDelete('cascade');
            $table->date('period')->nullable()->after('due_date');
            $table->double('amount', 15, 2)->nullable()->change();
            $table->dropColumn(['billing_type', 'frequency', 'custom_frequency_days']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_series');
        Schema::table('bills', function (Blueprint $table) {
            // Urutan dibalik untuk rollback.
            // 1. Tambahkan kembali kolom-kolom yang dihapus.
            $table->enum('billing_type', ['fixed', 'recurring'])->default('fixed')->after('currency');
            $table->enum('frequency', ['monthly', 'annual', 'custom'])->nullable()->after('billing_type');
            $table->integer('custom_frequency_days')->nullable()->after('frequency');

            // 2. Kembalikan kolom amount menjadi tidak bisa NULL.
            $table->double('amount', 15, 2)->nullable(false)->change();

            // 3. Hapus kolom periode.
            $table->dropColumn('period');

            // 4. Hapus foreign key dan kolom bill_series_id.
            $table->dropConstrainedForeignId('bill_series_id');
        });
    }
};
