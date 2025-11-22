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
        Schema::create('product_integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');

            // Plataforma de integração (mercado_livre, shopee, amazon, etc)
            $table->string('platform', 50);

            // ID externo na plataforma
            $table->string('external_id', 100)->nullable();

            // Status da integração
            $table->string('status', 20)->default('pending'); // pending, active, paused, failed, removed

            // Dados adicionais específicos da plataforma (JSON)
            $table->json('metadata')->nullable();

            // Controle de sincronização
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('sync_errors')->nullable(); // Erros da última sincronização

            $table->timestamps();

            // Índices
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index('platform');
            $table->index('status');
            $table->index(['product_id', 'platform']); // Produto pode ter múltiplas plataformas
            $table->unique(['product_id', 'platform', 'external_id']); // Evita duplicação
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_integrations');
    }
};
