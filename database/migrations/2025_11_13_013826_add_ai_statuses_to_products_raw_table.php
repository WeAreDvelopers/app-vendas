<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sintaxe ENUM/MODIFY é específica do MySQL; no-op em outros drivers (ex.: SQLite dos testes).
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE products_raw MODIFY COLUMN status ENUM('raw','normalized','enriched','ready','processing_ai','ai_processed','ai_failed') DEFAULT 'raw'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE products_raw MODIFY COLUMN status ENUM('raw','normalized','enriched','ready') DEFAULT 'raw'");
        }
    }
};
