<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductIntegration extends Model
{
    // Constantes para plataformas suportadas
    const PLATFORM_MERCADO_LIVRE = 'mercado_livre';
    const PLATFORM_SHOPEE = 'shopee';
    const PLATFORM_AMAZON = 'amazon';
    const PLATFORM_MAGALU = 'magalu';
    const PLATFORM_AMERICANAS = 'americanas';

    // Status possíveis
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_FAILED = 'failed';
    const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'product_id',
        'platform',
        'external_id',
        'status',
        'metadata',
        'last_sync_at',
        'published_at',
        'sync_errors'
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_sync_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * Relação com o produto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Verifica se a integração está ativa
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Verifica se foi publicado
     */
    public function isPublished(): bool
    {
        return !empty($this->external_id) && !empty($this->published_at);
    }

    /**
     * Retorna informações de display da plataforma
     */
    public function getPlatformInfo(): array
    {
        $platforms = [
            self::PLATFORM_MERCADO_LIVRE => [
                'name' => 'Mercado Livre',
                'color' => 'warning',
                'icon' => '🛒'
            ],
            self::PLATFORM_SHOPEE => [
                'name' => 'Shopee',
                'color' => 'danger',
                'icon' => '🛍️'
            ],
            self::PLATFORM_AMAZON => [
                'name' => 'Amazon',
                'color' => 'dark',
                'icon' => '📦'
            ],
            self::PLATFORM_MAGALU => [
                'name' => 'Magazine Luiza',
                'color' => 'primary',
                'icon' => '🏪'
            ],
            self::PLATFORM_AMERICANAS => [
                'name' => 'Americanas',
                'color' => 'danger',
                'icon' => '🏬'
            ],
        ];

        return $platforms[$this->platform] ?? [
            'name' => ucfirst($this->platform),
            'color' => 'secondary',
            'icon' => '🔗'
        ];
    }

    /**
     * Scope para filtrar por plataforma
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope para integrações ativas
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
