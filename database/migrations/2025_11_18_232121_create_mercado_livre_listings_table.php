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
        Schema::create('mercado_livre_listings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');

            // Dados do anúncio no ML
            $table->string('ml_id', 50)->nullable()->unique(); // ID do anúncio no ML
            $table->string('status', 20)->default('draft'); // draft, pending_review, active, paused, closed
            $table->string('listing_type_id', 20)->default('gold_special'); // gold_special, gold_pro, free

            // Informações básicas (score 100%)
            $table->string('title', 60); // Máximo 60 caracteres
            $table->string('category_id', 20); // Ex: MLB1234
            $table->decimal('price', 10, 2);
            $table->string('currency_id', 3)->default('BRL');
            $table->integer('available_quantity')->default(1);
            $table->string('buying_mode', 20)->default('buy_it_now'); // buy_it_now, auction
            $table->string('condition', 10)->default('new'); // new, used

            // Descrição
            $table->text('plain_text_description')->nullable(); // Descrição texto puro
            $table->string('video_id', 20)->nullable(); // ID do vídeo YouTube

            // Atributos da categoria (JSON)
            $table->json('attributes')->nullable(); // [{id: 'BRAND', value_name: 'Samsung'}]

            // Envio e logística
            $table->string('shipping_mode', 20)->default('me2'); // me2 (mercado envios), custom
            $table->boolean('free_shipping')->default(false);
            $table->string('shipping_local_pick_up')->default('true'); // true/false como string

            // Garantia
            $table->string('warranty_type', 50)->nullable(); // Tipo de garantia
            $table->string('warranty_time', 50)->nullable(); // Tempo de garantia

            // Análise de qualidade
            $table->integer('quality_score')->default(0); // 0-100
            $table->json('missing_fields')->nullable(); // Campos faltantes para 100%
            $table->json('validation_errors')->nullable(); // Erros de validação

            // Datas de sincronização
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            // Índices
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index('status');
            $table->index('ml_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mercado_livre_listings');
    }
};
