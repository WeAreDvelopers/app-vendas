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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document')->nullable(); // CNPJ/CPF
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(true);
            $table->json('settings')->nullable(); // Configurações gerais
            $table->timestamps();
        });

        // Tabela pivot para usuários e empresas (um usuário pode ter acesso a várias empresas)
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_admin')->default(false); // Admin da empresa
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });

        // Tabela de integrações por empresa
        Schema::create('company_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('integration_type'); // 'mercado_livre', 'shopee', etc
            $table->boolean('active')->default(true);
            $table->json('credentials')->nullable(); // Tokens, secrets, etc (criptografado)
            $table->json('settings')->nullable(); // Configurações específicas
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'integration_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_integrations');
        Schema::dropIfExists('company_user');
        Schema::dropIfExists('companies');
    }
};
