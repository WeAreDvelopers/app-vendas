<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageSearchService
{
    private const GOOGLE_CSE_ENDPOINT = 'https://www.googleapis.com/customsearch/v1';
    private const MIN_IMAGE_SIZE = 500; // Mínimo para ML
    private const RECOMMENDED_IMAGE_SIZE = 1200; // Recomendado para ML

    protected ImageSimilarityService $similarityService;

    public function __construct()
    {
        $this->similarityService = new ImageSimilarityService();
    }

    /**
     * Busca imagens usando Google Custom Search
     */
    public function searchImages(string $query, array $options = []): array
    {
        $apiKey = config('services.google_search.api_key');
        $cx = config('services.google_search.cx');

        if (empty($apiKey) || empty($cx)) {
            Log::warning('Google Custom Search not configured');
            return $this->fallbackImageSearch($query);
        }

        try {
            $response = Http::timeout(15)->get(self::GOOGLE_CSE_ENDPOINT, [
                'key' => $apiKey,
                'cx' => $cx,
                'q' => $query,
                'searchType' => 'image',
                'num' => $options['limit'] ?? 10,
                'imgSize' => 'large', // large, medium, small
                'imgType' => 'photo', // photo, clipart, lineart
                'safe' => 'active',
                'fileType' => 'jpg,png',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['items']) && count($data['items']) > 0) {
                    return $this->parseGoogleResults($data['items']);
                }

                Log::info("No images found for query: {$query}");
                return [];
            }

            Log::warning("Google Custom Search API error", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error("Error searching images: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca imagens para um produto específico
     */
    public function searchForProduct($product, bool $useSimilarityFilter = true): array
    {
        $queries = $this->buildSearchQueries($product);

        Log::info("Iniciando busca de imagens para produto", [
            'product_id' => $product->id ?? null,
            'product_name' => $product->name ?? null,
            'ean' => $product->ean ?? null,
            'sku' => $product->sku ?? null,
            'queries_count' => count($queries),
            'queries' => $queries,
            'use_similarity' => $useSimilarityFilter,
            'reference_image' => $product->reference_image_path ?? null
        ]);

        $images = [];

        foreach ($queries as $index => $query) {
            Log::info("Executando query " . ($index + 1) . "/" . count($queries) . ": {$query}");

            $results = $this->searchImages($query, ['limit' => 5]);

            if (!empty($results)) {
                Log::info("Query retornou " . count($results) . " imagens");
                $images = array_merge($images, $results);

                // Limita a 10 imagens no total
                if (count($images) >= 10) {
                    Log::info("Limite de 10 imagens atingido, parando busca");
                    break;
                }
            } else {
                Log::info("Query não retornou resultados");
            }
        }

        // Remove duplicatas
        $imagesBefore = count($images);
        $images = $this->removeDuplicates($images);
        $duplicatesRemoved = $imagesBefore - count($images);

        if ($duplicatesRemoved > 0) {
            Log::info("Removidas {$duplicatesRemoved} imagens duplicadas");
        }

        // Aplica filtro de similaridade se houver imagem de referência
        if ($useSimilarityFilter && !empty($product->reference_image_path)) {
            Log::info("Aplicando filtro de similaridade");

            $threshold = $product->similarity_threshold ?? 0.7;
            $images = $this->similarityService->filterBySimilarity(
                $product->reference_image_path,
                $images,
                $threshold
            );

            Log::info("Filtro de similaridade aplicado", [
                'remaining_images' => count($images),
                'threshold' => $threshold
            ]);
        } else {
            // Ordena por qualidade (tamanho) se não usar similaridade
            usort($images, function($a, $b) {
                $sizeA = ($a['width'] ?? 0) * ($a['height'] ?? 0);
                $sizeB = ($b['width'] ?? 0) * ($b['height'] ?? 0);
                return $sizeB - $sizeA;
            });
        }

        // Limita a 10 melhores
        $finalImages = array_slice($images, 0, 10);

        Log::info("Busca finalizada", [
            'total_encontradas' => count($images),
            'total_retornadas' => count($finalImages)
        ]);

        return $finalImages;
    }

    /**
     * Constrói queries de busca otimizadas
     */
    private function buildSearchQueries($product): array
    {
        $queries = [];

        // Query 1: EAN apenas números (mais preciso)
        // Remove tudo que não for número do EAN para maior precisão
        if (!empty($product->ean)) {
            $eanNumerico = preg_replace('/\D/', '', $product->ean);

            if (strlen($eanNumerico) >= 8) { // EAN válido tem no mínimo 8 dígitos
                // Busca com EAN + produto para filtrar melhor os resultados
                $queries[] = $eanNumerico . ' produto';

                // Se tiver marca, adiciona para maior precisão
                if (!empty($product->brand)) {
                    $queries[] = $eanNumerico . ' ' . $product->brand;
                }
            }
        }

        // Query 2: Marca + Nome (segunda mais precisa)
        if (!empty($product->brand) && !empty($product->name)) {
            // Limpa o nome de caracteres especiais que podem atrapalhar a busca
            $nomeLimpo = $this->cleanSearchTerm($product->name);
            $queries[] = $product->brand . ' ' . $nomeLimpo . ' produto';
        }

        // Query 3: Nome completo limpo
        if (!empty($product->name)) {
            $nomeLimpo = $this->cleanSearchTerm($product->name);
            $queries[] = $nomeLimpo . ' produto';
        }

        // Query 4: SKU apenas alfanuméricos (menos confiável, mas útil)
        if (!empty($product->sku)) {
            // Remove caracteres especiais do SKU
            $skuLimpo = preg_replace('/[^a-zA-Z0-9]/', '', $product->sku);

            if (strlen($skuLimpo) >= 3) { // SKU muito curto pode gerar resultados irrelevantes
                $queries[] = $skuLimpo . ' produto';

                // Se tiver marca, combina para melhor precisão
                if (!empty($product->brand)) {
                    $queries[] = $product->brand . ' ' . $skuLimpo;
                }
            }
        }

        return array_unique($queries);
    }

    /**
     * Limpa termos de busca removendo caracteres que podem atrapalhar
     */
    private function cleanSearchTerm(string $term): string
    {
        // Remove caracteres especiais mas mantém espaços e alfanuméricos
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $term);

        // Remove espaços múltiplos
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);

        // Remove stopwords comuns que não agregam na busca de imagens
        $stopwords = [
            'com', 'para', 'de', 'em', 'da', 'do', 'dos', 'das',
            'um', 'uma', 'uns', 'umas', 'o', 'a', 'os', 'as'
        ];

        $words = explode(' ', strtolower(trim($cleaned)));
        $filtered = array_filter($words, function($word) use ($stopwords) {
            return strlen($word) > 2 && !in_array($word, $stopwords);
        });

        return trim(implode(' ', $filtered));
    }

    /**
     * Faz parse dos resultados do Google
     */
    private function parseGoogleResults(array $items): array
    {
        $images = [];

        foreach ($items as $item) {
            $image = $item['image'] ?? [];

            // Filtra imagens muito pequenas
            $width = $image['width'] ?? 0;
            $height = $image['height'] ?? 0;

            if ($width < self::MIN_IMAGE_SIZE || $height < self::MIN_IMAGE_SIZE) {
                continue;
            }

            $images[] = [
                'url' => $item['link'],
                'thumbnail' => $image['thumbnailLink'] ?? null,
                'width' => $width,
                'height' => $height,
                'size' => $image['byteSize'] ?? null,
                'context' => $item['image']['contextLink'] ?? null,
                'title' => $item['title'] ?? null,
            ];
        }

        return $images;
    }

    /**
     * Remove imagens duplicadas
     */
    private function removeDuplicates(array $images): array
    {
        $seen = [];
        $unique = [];

        foreach ($images as $image) {
            $url = $image['url'];

            if (!in_array($url, $seen)) {
                $seen[] = $url;
                $unique[] = $image;
            }
        }

        return $unique;
    }

    /**
     * Download e otimização de imagem
     */
    public function downloadAndOptimize(string $url, int $productId): ?array
    {
        try {
            // Download da imagem
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                Log::warning("Failed to download image: {$url}");
                return null;
            }

            $imageContent = $response->body();

            // Detecta o tipo da imagem
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);

            if (!in_array($mimeType, ['image/jpeg', 'image/png'])) {
                Log::warning("Invalid image type: {$mimeType}");
                return null;
            }

            // Gera nome único
            $extension = $mimeType === 'image/png' ? 'png' : 'jpg';
            $filename = 'product_' . $productId . '_' . uniqid() . '.' . $extension;
            $path = 'product_images/' . $filename;

            // Salva temporariamente
            $tempPath = sys_get_temp_dir() . '/' . $filename;
            file_put_contents($tempPath, $imageContent);

            // Cria instância do ImageManager (Intervention Image v3)
            $manager = new ImageManager(new Driver());

            // Otimiza a imagem (redimensiona se necessário)
            $image = $manager->read($tempPath);

            // Se a imagem for muito grande, redimensiona mantendo proporção
            if ($image->width() > 2000 || $image->height() > 2000) {
                $image->scale(width: self::RECOMMENDED_IMAGE_SIZE);
            }

            // Se for menor que o mínimo, não usa
            if ($image->width() < self::MIN_IMAGE_SIZE || $image->height() < self::MIN_IMAGE_SIZE) {
                unlink($tempPath);
                return null;
            }

            // Converte para JPG se for PNG muito grande
            if ($extension === 'png' && filesize($tempPath) > 2000000) {
                $encoded = $image->toJpeg(quality: 90);
                $filename = str_replace('.png', '.jpg', $filename);
                $path = str_replace('.png', '.jpg', $path);
            } else {
                if ($extension === 'png') {
                    $encoded = $image->toPng();
                } else {
                    $encoded = $image->toJpeg(quality: 90);
                }
            }

            // Salva no storage
            Storage::disk('public')->put($path, (string) $encoded);

            // Remove temp
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return [
                'path' => '/storage/' . $path,
                'source_url' => $url,
                'width' => $image->width(),
                'height' => $image->height(),
                'size' => Storage::disk('public')->size($path),
            ];

        } catch (\Exception $e) {
            Log::error("Error downloading/optimizing image: " . $e->getMessage(), [
                'url' => $url
            ]);
            return null;
        }
    }

    /**
     * Busca alternativa quando Google Search não está disponível
     */
    private function fallbackImageSearch(string $query): array
    {
        // Poderia integrar com outras APIs:
        // - Bing Image Search
        // - Unsplash API
        // - Pexels API
        // - APIs de e-commerce (Amazon, etc)

        Log::info("Using fallback image search for: {$query}");
        return [];
    }

    /**
     * Valida se uma URL de imagem é acessível
     */
    public function validateImageUrl(string $url): bool
    {
        try {
            $response = Http::timeout(10)->head($url);
            return $response->successful() && str_starts_with($response->header('Content-Type'), 'image/');
        } catch (\Exception $e) {
            return false;
        }
    }
}
