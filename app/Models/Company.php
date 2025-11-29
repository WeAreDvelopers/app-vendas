<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name',
        'document',
        'email',
        'phone',
        'active',
        'settings'
    ];

    protected $casts = [
        'active' => 'boolean',
        'settings' => 'array'
    ];

    /**
     * Usuários que têm acesso a esta empresa
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('is_admin')
            ->withTimestamps();
    }

    /**
     * Integrações desta empresa
     */
    public function integrations(): HasMany
    {
        return $this->hasMany(CompanyIntegration::class);
    }

    /**
     * Importações desta empresa
     */
    public function imports(): HasMany
    {
        return $this->hasMany(\App\Models\SupplierImport::class);
    }

    /**
     * Produtos desta empresa
     */
    public function products(): HasMany
    {
        return $this->hasMany(\App\Models\Product::class);
    }

    /**
     * Fornecedores desta empresa
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(\App\Models\Supplier::class);
    }

    /**
     * Obtém a integração do Mercado Livre
     */
    public function mercadoLivreIntegration()
    {
        return $this->integrations()->where('integration_type', 'mercado_livre')->first();
    }

    /**
     * Verifica se tem integração ativa do Mercado Livre
     */
    public function hasMercadoLivreConnected(): bool
    {
        $integration = $this->mercadoLivreIntegration();
        return $integration && $integration->active && $integration->credentials;
    }
}
