<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'sku', 'ean', 'name', 'brand', 'category',
        'title', 'condition', 'warranty', 'video_url',
        'description', 'price', 'cost_price', 'stock',
        'weight', 'width', 'height', 'length',
        'status', 'reference_image_path', 'similarity_threshold',
        'product_raw_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock' => 'integer',
        'weight' => 'decimal:3',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'length' => 'decimal:2',
        'similarity_threshold' => 'decimal:2',
    ];

    /**
     * Relação com produto raw (origem)
     */
    public function productRaw()
    {
        return $this->belongsTo(ProductRaw::class);
    }

    /**
     * Relação com imagens do produto
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    /**
     * Relação com integrações do produto
     */
    public function integrations()
    {
        return $this->hasMany(ProductIntegration::class);
    }

    /**
     * Relação com listing do Mercado Livre
     */
    public function mercadoLivreListing()
    {
        return $this->hasOne(MercadoLivreListing::class);
    }

    /**
     * Verifica se o produto está integrado com uma plataforma específica
     */
    public function isIntegratedWith(string $platform): bool
    {
        return $this->integrations()
            ->where('platform', $platform)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Retorna todas as plataformas ativas em que o produto está integrado
     */
    public function getActivePlatforms(): array
    {
        return $this->integrations()
            ->where('status', 'active')
            ->pluck('platform')
            ->toArray();
    }

    /**
     * Scope para produtos integrados com determinada plataforma
     */
    public function scopeIntegratedWith($query, string $platform)
    {
        return $query->whereHas('integrations', function ($q) use ($platform) {
            $q->where('platform', $platform)
              ->where('status', 'active');
        });
    }
}
