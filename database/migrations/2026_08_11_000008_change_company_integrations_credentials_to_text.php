<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `credentials` era JSON, mas passou a guardar o blob CRIPTOGRAFADO (cast
 * 'encrypted:array'), que não é JSON válido. No MySQL a coluna JSON valida o
 * conteúdo e rejeita ("Invalid JSON text"). Converte para TEXT para aceitar o
 * ciphertext. (No SQLite dos testes a coluna JSON já é texto — por isso passava.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_integrations', function (Blueprint $table) {
            $table->text('credentials')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('company_integrations', function (Blueprint $table) {
            $table->json('credentials')->nullable()->change();
        });
    }
};
