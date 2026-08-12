<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Criptografa em repouso as credenciais já existentes de company_integrations
 * (antes gravadas como JSON em texto puro). A partir do cast 'encrypted:array'
 * do model, novas gravações já ficam criptografadas; esta migração cobre o
 * legado. É idempotente: linhas já criptografadas são ignoradas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('company_integrations')
            ->whereNotNull('credentials')
            ->orderBy('id')
            ->each(function ($row) {
                $raw = $row->credentials;

                // Já criptografado? (payload válido do Crypt) → não mexe.
                try {
                    Crypt::decryptString($raw);
                    return;
                } catch (\Throwable $e) {
                    // Não é payload criptografado; segue para tratar como texto puro.
                }

                // Só criptografa se for JSON de credenciais em texto puro.
                $decoded = json_decode($raw, true);
                if (!is_array($decoded)) {
                    return;
                }

                DB::table('company_integrations')
                    ->where('id', $row->id)
                    ->update(['credentials' => Crypt::encryptString($raw)]);
            });
    }

    public function down(): void
    {
        // Reverte para texto puro (best-effort). Linhas não criptografadas são ignoradas.
        DB::table('company_integrations')
            ->whereNotNull('credentials')
            ->orderBy('id')
            ->each(function ($row) {
                try {
                    $plain = Crypt::decryptString($row->credentials);
                } catch (\Throwable $e) {
                    return; // já em texto puro
                }

                DB::table('company_integrations')
                    ->where('id', $row->id)
                    ->update(['credentials' => $plain]);
            });
    }
};
