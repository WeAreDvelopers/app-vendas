<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Popula a tabela product_integrations com dados existentes do Mercado Livre
     */
    public function up(): void
    {
        // Insere registros de integração para todos os produtos que têm listings no ML
        // (incluindo drafts que ainda não foram publicados)
        DB::statement("
            INSERT INTO product_integrations (product_id, platform, external_id, status, metadata, last_sync_at, published_at, created_at, updated_at)
            SELECT
                product_id,
                'mercado_livre' as platform,
                ml_id as external_id,
                CASE
                    WHEN status = 'active' AND ml_id IS NOT NULL THEN 'active'
                    WHEN status = 'paused' THEN 'paused'
                    WHEN status = 'closed' THEN 'removed'
                    ELSE 'pending'
                END as status,
                JSON_OBJECT(
                    'listing_type_id', listing_type_id,
                    'title', title,
                    'category_id', category_id,
                    'quality_score', quality_score
                ) as metadata,
                last_sync_at,
                published_at,
                created_at,
                updated_at
            FROM mercado_livre_listings
            ON DUPLICATE KEY UPDATE
                external_id = VALUES(external_id),
                status = VALUES(status),
                metadata = VALUES(metadata),
                last_sync_at = VALUES(last_sync_at),
                published_at = VALUES(published_at),
                updated_at = VALUES(updated_at)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove apenas as integrações do Mercado Livre
        DB::table('product_integrations')
            ->where('platform', 'mercado_livre')
            ->delete();
    }
};
