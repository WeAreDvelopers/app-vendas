<?php

namespace App\Observers;

use App\Models\MercadoLivreListing;
use App\Models\ProductIntegration;

class MercadoLivreListingObserver
{
    /**
     * Handle the MercadoLivreListing "created" event.
     * Cria automaticamente um registro de integração quando um listing é criado
     */
    public function created(MercadoLivreListing $listing): void
    {
        $this->syncIntegration($listing);
    }

    /**
     * Handle the MercadoLivreListing "updated" event.
     * Atualiza o registro de integração quando o listing é atualizado
     */
    public function updated(MercadoLivreListing $listing): void
    {
        $this->syncIntegration($listing);
    }

    /**
     * Handle the MercadoLivreListing "deleted" event.
     * Remove ou marca como removida a integração quando o listing é deletado
     */
    public function deleted(MercadoLivreListing $listing): void
    {
        ProductIntegration::where('product_id', $listing->product_id)
            ->where('platform', ProductIntegration::PLATFORM_MERCADO_LIVRE)
            ->update(['status' => ProductIntegration::STATUS_REMOVED]);
    }

    /**
     * Sincroniza o registro de integração com o listing do ML
     */
    private function syncIntegration(MercadoLivreListing $listing): void
    {
        // Determina o status da integração baseado no status do listing
        $integrationStatus = match($listing->status) {
            'active' => $listing->ml_id ? ProductIntegration::STATUS_ACTIVE : ProductIntegration::STATUS_PENDING,
            'paused' => ProductIntegration::STATUS_PAUSED,
            'closed' => ProductIntegration::STATUS_REMOVED,
            default => ProductIntegration::STATUS_PENDING,
        };

        // Atualiza ou cria a integração
        ProductIntegration::updateOrCreate(
            [
                'product_id' => $listing->product_id,
                'platform' => ProductIntegration::PLATFORM_MERCADO_LIVRE,
            ],
            [
                'external_id' => $listing->ml_id,
                'status' => $integrationStatus,
                'metadata' => [
                    'listing_type_id' => $listing->listing_type_id,
                    'title' => $listing->title,
                    'category_id' => $listing->category_id,
                    'quality_score' => $listing->quality_score,
                ],
                'last_sync_at' => $listing->last_sync_at,
                'published_at' => $listing->published_at,
            ]
        );
    }
}
