<?php

namespace App\Helpers;

use App\Models\Company;
use App\Models\CompanyIntegration;

class CompanyHelper
{
    /**
     * Obtém a empresa atual do usuário logado
     */
    public static function currentCompany(): ?Company
    {
        if (!auth()->check()) {
            return null;
        }

        return auth()->user()->getCurrentCompany();
    }

    /**
     * Obtém o ID da empresa atual
     */
    public static function currentCompanyId(): ?int
    {
        if (!auth()->check()) {
            return null;
        }

        return auth()->user()->current_company_id;
    }

    /**
     * Obtém a integração do Mercado Livre da empresa atual
     */
    public static function mercadoLivreIntegration(): ?CompanyIntegration
    {
        $company = self::currentCompany();

        if (!$company) {
            return null;
        }

        return $company->integrations()
            ->where('integration_type', 'mercado_livre')
            ->first();
    }

    /**
     * Verifica se o Mercado Livre está conectado
     */
    public static function isMercadoLivreConnected(): bool
    {
        $integration = self::mercadoLivreIntegration();

        return $integration && $integration->isConnected();
    }

    /**
     * Obtém o access token do Mercado Livre
     * Atualiza automaticamente se estiver expirado
     */
    public static function getMercadoLivreAccessToken(): ?string
    {
        $integration = self::mercadoLivreIntegration();

        if (!$integration) {
            return null;
        }

        // Se está expirado ou prestes a expirar (menos de 1 hora), renova
        if ($integration->isExpired() ||
            ($integration->expires_at && $integration->expires_at->isBefore(now()->addHour()))) {

            $controller = app(\App\Http\Controllers\Panel\IntegrationController::class);
            $renewed = $controller->mercadoLivreRefreshToken($integration);

            if (!$renewed) {
                return null;
            }

            // Recarrega a integração
            $integration = $integration->fresh();
        }

        $credentials = $integration->getDecryptedCredentials();

        return $credentials['access_token'] ?? null;
    }

    /**
     * Obtém credenciais completas do Mercado Livre
     */
    public static function getMercadoLivreCredentials(): ?array
    {
        $integration = self::mercadoLivreIntegration();

        if (!$integration) {
            return null;
        }

        return $integration->getDecryptedCredentials();
    }

    /**
     * Obtém o User ID do Mercado Livre
     */
    public static function getMercadoLivreUserId(): ?string
    {
        $credentials = self::getMercadoLivreCredentials();

        return $credentials['user_id'] ?? null;
    }

    /**
     * Obtém o nickname do Mercado Livre
     */
    public static function getMercadoLivreNickname(): ?string
    {
        $credentials = self::getMercadoLivreCredentials();

        return $credentials['nickname'] ?? null;
    }

    /**
     * Verifica se o usuário é admin da empresa atual
     */
    public static function isCurrentCompanyAdmin(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $companyId = self::currentCompanyId();

        if (!$companyId) {
            return false;
        }

        return auth()->user()->isAdminOf($companyId);
    }

    /**
     * Obtém a integração do Google Drive da empresa atual
     */
    public static function googleDriveIntegration(): ?CompanyIntegration
    {
        $company = self::currentCompany();

        if (!$company) {
            return null;
        }

        return $company->integrations()
            ->where('integration_type', 'google_drive')
            ->first();
    }

    /**
     * Verifica se o Google Drive está conectado
     */
    public static function isGoogleDriveConnected(): bool
    {
        $integration = self::googleDriveIntegration();

        return $integration && $integration->isConnected();
    }

    /**
     * Obtém o access token do Google Drive
     * Atualiza automaticamente se estiver expirado
     */
    public static function getGoogleDriveAccessToken(): ?string
    {
        $integration = self::googleDriveIntegration();

        if (!$integration) {
            return null;
        }

        // Se está expirado ou prestes a expirar (menos de 5 minutos), renova
        if ($integration->isExpired() ||
            ($integration->expires_at && $integration->expires_at->isBefore(now()->addMinutes(5)))) {

            $controller = app(\App\Http\Controllers\Panel\IntegrationController::class);
            $renewed = $controller->googleDriveRefreshToken($integration);

            if (!$renewed) {
                return null;
            }

            // Recarrega a integração
            $integration = $integration->fresh();
        }

        $credentials = $integration->getDecryptedCredentials();

        return $credentials['access_token'] ?? null;
    }

    /**
     * Obtém credenciais completas do Google Drive
     */
    public static function getGoogleDriveCredentials(): ?array
    {
        $integration = self::googleDriveIntegration();

        if (!$integration) {
            return null;
        }

        return $integration->getDecryptedCredentials();
    }
}
