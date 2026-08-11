<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O status vinha como ENUM restrito ('paid','shipped','canceled','ready_to_print'),
     * mas os status reais do Mercado Livre (e de futuras integrações) incluem valores
     * fora dessa lista (ex.: 'confirmed', 'payment_required', 'cancelled'). No MySQL
     * estrito, gravar um valor fora do enum quebra a transação de ingestão. Passamos
     * a coluna para string para aceitar qualquer status de origem.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('ready_to_print')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['paid', 'shipped', 'canceled', 'ready_to_print'])
                ->default('ready_to_print')
                ->change();
        });
    }
};
