<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class IntegrationSettings
{
    /**
     * Busca uma configuração de integração
     */
    public static function get(int $companyId, string $platform, string $key, ?string $default = null): ?string
    {
        $setting = DB::table('integration_settings')
            ->where('company_id', $companyId)
            ->where('platform', $platform)
            ->where('key', $key)
            ->first();

        if (!$setting) {
            return $default;
        }

        try {
            return $setting->is_encrypted ? decrypt($setting->value) : $setting->value;
        } catch (\Exception $e) {
            \Log::error("Erro ao descriptografar setting {$platform}.{$key}: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Salva uma configuração de integração
     */
    public static function set(int $companyId, string $platform, string $key, ?string $value, bool $encrypt = false): void
    {
        if ($value === null) {
            return;
        }

        $settingValue = $encrypt ? encrypt($value) : $value;

        DB::table('integration_settings')->updateOrInsert(
            [
                'company_id' => $companyId,
                'platform' => $platform,
                'key' => $key
            ],
            [
                'value' => $settingValue,
                'is_encrypted' => $encrypt,
                'updated_at' => now()
            ]
        );
    }

    /**
     * Remove uma configuração
     */
    public static function delete(int $companyId, string $platform, string $key): void
    {
        DB::table('integration_settings')
            ->where('company_id', $companyId)
            ->where('platform', $platform)
            ->where('key', $key)
            ->delete();
    }

    /**
     * Busca App ID do Mercado Livre por company
     */
    public static function getMercadoLivreAppId(int $companyId): ?string
    {
        return self::get($companyId, 'mercado_livre', 'app_id', config('services.mercado_livre.app_id'));
    }

    /**
     * Busca Secret Key do Mercado Livre por company
     */
    public static function getMercadoLivreSecretKey(int $companyId): ?string
    {
        return self::get($companyId, 'mercado_livre', 'secret_key', config('services.mercado_livre.secret_key'));
    }
}
