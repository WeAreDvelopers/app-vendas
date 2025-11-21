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
        Schema::create('mercado_livre_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();

            // Tokens OAuth
            $table->text('access_token');
            $table->text('refresh_token');
            $table->integer('expires_in'); // Segundos até expirar
            $table->timestamp('expires_at'); // Data/hora de expiração

            // Informações do usuário ML
            $table->string('ml_user_id')->nullable();
            $table->string('ml_nickname')->nullable();

            // Controle
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_refresh_at')->nullable();

            $table->timestamps();

            // Índices
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('ml_user_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mercado_livre_tokens');
    }
};
