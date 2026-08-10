<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products_raw', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });
        Schema::table('import_errors', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });

        // Backfill a partir do supplier_import correspondente (ignora órfãos).
        DB::statement('
            UPDATE products_raw
            SET company_id = (
                SELECT si.company_id FROM supplier_imports si
                WHERE si.id = products_raw.supplier_import_id
            )
            WHERE company_id IS NULL
        ');
        DB::statement('
            UPDATE import_errors
            SET company_id = (
                SELECT si.company_id FROM supplier_imports si
                WHERE si.id = import_errors.supplier_import_id
            )
            WHERE company_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('import_errors', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
        Schema::table('products_raw', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
