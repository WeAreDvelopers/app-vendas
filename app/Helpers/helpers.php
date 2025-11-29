<?php

use App\Helpers\CompanyHelper;
use App\Models\Company;
use App\Models\CompanyIntegration;

if (!function_exists('currentCompany')) {
    /**
     * Obtém a empresa atual do usuário logado
     */
    function currentCompany(): ?Company
    {
        return CompanyHelper::currentCompany();
    }
}

if (!function_exists('currentCompanyId')) {
    /**
     * Obtém o ID da empresa atual
     */
    function currentCompanyId(): ?int
    {
        return CompanyHelper::currentCompanyId();
    }
}

if (!function_exists('mlIntegration')) {
    /**
     * Obtém a integração do Mercado Livre da empresa atual
     */
    function mlIntegration(): ?CompanyIntegration
    {
        return CompanyHelper::mercadoLivreIntegration();
    }
}

if (!function_exists('mlConnected')) {
    /**
     * Verifica se o Mercado Livre está conectado
     */
    function mlConnected(): bool
    {
        return CompanyHelper::isMercadoLivreConnected();
    }
}

if (!function_exists('mlAccessToken')) {
    /**
     * Obtém o access token do Mercado Livre
     * Renova automaticamente se necessário
     */
    function mlAccessToken(): ?string
    {
        return CompanyHelper::getMercadoLivreAccessToken();
    }
}

if (!function_exists('mlUserId')) {
    /**
     * Obtém o User ID do Mercado Livre
     */
    function mlUserId(): ?string
    {
        return CompanyHelper::getMercadoLivreUserId();
    }
}

if (!function_exists('mlNickname')) {
    /**
     * Obtém o nickname do Mercado Livre
     */
    function mlNickname(): ?string
    {
        return CompanyHelper::getMercadoLivreNickname();
    }
}

if (!function_exists('isCompanyAdmin')) {
    /**
     * Verifica se o usuário é admin da empresa atual
     */
    function isCompanyAdmin(): bool
    {
        return CompanyHelper::isCurrentCompanyAdmin();
    }
}

if (!function_exists('driveIntegration')) {
    /**
     * Obtém a integração do Google Drive da empresa atual
     */
    function driveIntegration(): ?CompanyIntegration
    {
        return CompanyHelper::googleDriveIntegration();
    }
}

if (!function_exists('driveConnected')) {
    /**
     * Verifica se o Google Drive está conectado
     */
    function driveConnected(): bool
    {
        return CompanyHelper::isGoogleDriveConnected();
    }
}

if (!function_exists('driveAccessToken')) {
    /**
     * Obtém o access token do Google Drive
     * Renova automaticamente se necessário
     */
    function driveAccessToken(): ?string
    {
        return CompanyHelper::getGoogleDriveAccessToken();
    }
}
