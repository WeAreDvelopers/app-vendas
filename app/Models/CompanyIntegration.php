<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class CompanyIntegration extends Model
{
    protected $fillable = [
        'company_id',
        'integration_type',
        'active',
        'credentials',
        'settings',
        'connected_at',
        'expires_at'
    ];

    protected $casts = [
        'active' => 'boolean',
        'credentials' => 'array',
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
     * Obtém credenciais descriptografadas
     */
    public function getDecryptedCredentials(): ?array
    {
        if (!$this->credentials) {
            return null;
        }

        try {
            // Se já for array, retorna direto
            if (is_array($this->credentials)) {
                return $this->credentials;
            }

            return json_decode(Crypt::decryptString($this->credentials), true);
        } catch (\Exception $e) {
            \Log::error("Erro ao descriptografar credenciais: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Define credenciais criptografadas
     */
    public function setEncryptedCredentials(array $credentials): void
    {
        $this->credentials = Crypt::encryptString(json_encode($credentials));
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
