<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MercadoLivreListing extends Model
{
    protected $table = 'mercado_livre_listings';

    protected $fillable = [
        'product_id', 'ml_id', 'status', 'listing_type_id',
        'title', 'category_id', 'price', 'currency_id',
        'available_quantity', 'buying_mode', 'condition',
        'plain_text_description', 'video_id', 'attributes',
        'shipping_mode', 'free_shipping', 'shipping_local_pick_up',
        'warranty_type', 'warranty_time', 'quality_score',
        'missing_fields', 'validation_errors', 'last_sync_at',
        'published_at'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'available_quantity' => 'integer',
        'free_shipping' => 'boolean',
        'quality_score' => 'integer',
        'attributes' => 'array',
        'missing_fields' => 'array',
        'validation_errors' => 'array',
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
     * Verifica se o listing está ativo no ML
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && !empty($this->ml_id);
    }

    /**
     * Verifica se foi publicado
     */
    public function isPublished(): bool
    {
        return !empty($this->ml_id) && !empty($this->published_at);
    }
}
