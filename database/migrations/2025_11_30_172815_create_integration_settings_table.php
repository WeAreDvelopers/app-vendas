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
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('platform', 50); // mercado_livre, shopee, shopify, etc
            $table->string('key', 100); // app_id, client_id, api_key, etc
            $table->text('value')->nullable(); // valor da configuração (pode ser criptografado)
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();

            // Índices
            $table->index(['company_id', 'platform']);
            $table->unique(['company_id', 'platform', 'key']);

            // Foreign key
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
    }
};
