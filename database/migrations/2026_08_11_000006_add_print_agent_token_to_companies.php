<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Token do agente de impressão por empresa. Cada agente físico usa o token
     * da sua empresa e só recebe as etiquetas dela. Um token "mestre" global
     * (config printagent.token) continua vendo todas as filas.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('print_agent_token')->nullable()->unique()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('print_agent_token');
        });
    }
};
