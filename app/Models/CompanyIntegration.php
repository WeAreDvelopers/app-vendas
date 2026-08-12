<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyIntegration extends Model
{
    protected $fillable = [
        'company_id',
        'integration_type',
        'ml_user_id',
        'active',
        'credentials',
        'settings',
        'connected_at',
        'expires_at'
    ];

    protected $casts = [
        'active' => 'boolean',
        // Criptografado em repouso: access_token/refresh_token do ML não ficam em
        // texto puro no banco. O cast cuida de (des)criptografar de forma transparente.
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'connected_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    /**
     * Empresa dona desta integração
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Obtém as credenciais (o cast 'encrypted:array' já descriptografa).
     */
    public function getDecryptedCredentials(): ?array
    {
        return $this->credentials ?: null;
    }

    /**
     * Verifica se a integração está expirada
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verifica se está conectado e válido
     */
    public function isConnected(): bool
    {
        return $this->active
            && $this->credentials
            && !$this->isExpired();
    }
}
