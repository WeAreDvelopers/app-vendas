<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ProductRaw;
use App\Services\AIDescriptionService;
use App\Services\ImageSearchService;
use App\Helpers\NotificationHelper;

class ProcessProductWithAI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900];

    public function __construct(public int $productRawId)
    {
    }

    public function handle(AIDescriptionService $aiService, ImageSearchService $imageService): void
    {
        $product = ProductRaw::find($this->productRawId);

        if (!$product) {
            Log::warning("Product raw ID {$this->productRawId} not found");
            return;
        }

        try {
            // Atualiza status para processando
            $product->update(['status' => 'processing_ai']);

            // 1. Gera descrição com IA (Gemini first, OpenAI fallback)
            $aiResult = $aiService->generateDescription($product);
            $description = $aiResult['description'];

            // 2. Busca imagens online - DESATIVADO
            // Agora a busca de imagens é feita manualmente usando similaridade visual
            // $images = $this->fetchImages($product, $imageService);
            $images = []; // Sem busca automática

            // 3. Salva o produto processado
            $productId = $this->saveProcessedProduct($product, $description, $images);

            // 4. Atualiza status
            $product->update([
                'status' => 'ai_processed',
                'extra' => array_merge($product->extra ?? [], [
                    'ai_description' => $description,
                    'ai_provider' => $aiResult['provider'],
                    'ai_model' => $aiResult['model'] ?? null,
                    'ai_cost' => $aiResult['cost'] ?? 0,
                    'ai_images' => $images,
                    'processed_at' => now()->toIso8601String(),
                    'product_id' => $productId
                ])
            ]);

            Log::info("Product {$this->productRawId} processed successfully", [
                'provider' => $aiResult['provider'],
                'cost' => $aiResult['cost'] ?? 0
            ]);

            // Envia notificação de sucesso
            NotificationHelper::success(
                'Produto Processado com IA',
                "'{$product->name}' foi processado com sucesso! Descrição gerada e produto pronto para edição.",
                "/panel/products/{$productId}",
                'Ver Produto'
            );

        } catch (\Exception $e) {
            $product->update([
                'status' => 'ai_failed',
                'extra' => array_merge($product->extra ?? [], [
                    'ai_error' => $e->getMessage(),
                    'failed_at' => now()->toIso8601String()
                ])
            ]);

            Log::error("Failed to process product {$this->productRawId}: " . $e->getMessage());

            // Envia notificação de erro
            NotificationHelper::error(
                'Erro no Processamento IA',
                "Falha ao processar '{$product->name}': {$e->getMessage()}",
                '/panel/imports',
                'Ver Importações'
            );

            throw $e;
        }
    }

    private function fetchImages(ProductRaw $product, ImageSearchService $imageService): array
    {
        $imageUrls = [];

        try {
            // Busca imagens usando Google Custom Search
            $searchResults = $imageService->searchForProduct($product);

            if (empty($searchResults)) {
                Log::info("No images found via search for product {$product->id}");
                return [];
            }

            Log::info("Found {count} images for product {$product->id}", [
                'count' => count($searchResults),
                'product' => $product->name
            ]);

            // Limita a 3-5 melhores imagens para não sobrecarregar
            $limit = min(5, count($searchResults));

            for ($i = 0; $i < $limit; $i++) {
                $imageData = $searchResults[$i];
                $imageUrls[] = $imageData['url'];
            }

            return $imageUrls;

        } catch (\Exception $e) {
            Log::error("Error fetching images for product {$product->id}: " . $e->getMessage());
            return [];
        }
    }

    private function saveProcessedProduct(ProductRaw $productRaw, string $description, array $imageUrls): int
    {
        // Obtém company_id do product_raw via import
        $import = DB::table('supplier_imports')->find($productRaw->supplier_import_id);

        // Salva na tabela de produtos processados prontos para publicação
        $productId = DB::table('products')->insertGetId([
            'company_id' => $import->company_id,
            'product_raw_id' => $productRaw->id,
            'sku' => $productRaw->sku,
            'ean' => $productRaw->ean,
            'name' => $productRaw->name,
            'brand' => $productRaw->brand,
            'description' => $description,
            'price' => $productRaw->sale_price,
            'cost_price' => $productRaw->cost_price,
            'status' => 'ready',
            'stock' => 0,
            'attributes' => json_encode([
                'ai_generated' => true,
                'source' => 'import',
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Download e otimização das imagens
        if (!empty($imageUrls)) {
            $imageService = app(ImageSearchService::class);
            $successCount = 0;

            foreach ($imageUrls as $index => $imageUrl) {
                try {
                    // Faz download e otimiza a imagem
                    $imageData = $imageService->downloadAndOptimize($imageUrl, $productId);

                    if ($imageData) {
                        DB::table('product_images')->insert([
                            'product_id' => $productId,
                            'path' => $imageData['path'],
                            'source_url' => $imageData['source_url'],
                            'sort' => $index + 1,
                            'bg_removed' => false,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        $successCount++;
                        Log::info("Image {$index} downloaded successfully for product {$productId}");
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to download image {$index} for product {$productId}: " . $e->getMessage());
                }
            }

            Log::info("Downloaded {$successCount}/{count} images for product {$productId}", [
                'count' => count($imageUrls)
            ]);
        }

        return $productId;
    }
}
