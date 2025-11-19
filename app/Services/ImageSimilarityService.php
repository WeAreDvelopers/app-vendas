<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageSimilarityService
{
    private const GEMINI_VISION_ENDPOINT = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash-latest:generateContent';
    private const DEFAULT_SIMILARITY_THRESHOLD = 0.7;

    /**
     * Compara duas imagens usando Gemini Vision
     * Retorna um score de 0 a 1 (quanto maior, mais similar)
     */
    public function compareImages(string $referenceImagePath, string $candidateImageUrl): ?float
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            Log::warning('Gemini API key not configured for image similarity');
            return null;
        }

        try {
            // Converte ambas as imagens para base64
            $referenceBase64 = $this->imageToBase64($referenceImagePath);
            $candidateBase64 = $this->imageUrlToBase64($candidateImageUrl);

            if (!$referenceBase64 || !$candidateBase64) {
                return null;
            }

            // Monta o prompt para comparação
            $prompt = "Compare estas duas imagens de produtos e determine o quão similares elas são. " .
                     "Analise: cores, formato, tipo de produto, embalagem, e características visuais gerais. " .
                     "Responda APENAS com um número decimal entre 0.0 (completamente diferentes) e 1.0 (idênticas). " .
                     "Exemplos: 0.9 para muito similares, 0.5 para parcialmente similares, 0.1 para muito diferentes. " .
                     "Responda SOMENTE o número, sem texto adicional.";

            $response = Http::timeout(30)->post(self::GEMINI_VISION_ENDPOINT . '?key=' . $apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => 'image/jpeg',
                                    'data' => $referenceBase64
                                ]
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => 'image/jpeg',
                                    'data' => $candidateBase64
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1, // Baixa temperatura para respostas mais consistentes
                    'maxOutputTokens' => 10,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Extrai o número da resposta
                preg_match('/\d+\.?\d*/', $text, $matches);

                if (!empty($matches)) {
                    $similarity = floatval($matches[0]);

                    // Garante que está entre 0 e 1
                    $similarity = max(0, min(1, $similarity));

                    Log::info("Image similarity calculated", [
                        'similarity' => $similarity,
                        'candidate_url' => $candidateImageUrl
                    ]);

                    return $similarity;
                }
            }

            $responseBody = $response->json();
            $errorMessage = $responseBody['error']['message'] ?? 'Unknown error';

            Log::error("Failed to calculate image similarity", [
                'status' => $response->status(),
                'error_message' => $errorMessage,
                'full_response' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error("Error comparing images: " . $e->getMessage(), [
                'candidate_url' => $candidateImageUrl
            ]);
            return null;
        }
    }

    /**
     * Filtra uma lista de imagens mantendo apenas as similares à referência
     */
    public function filterBySimilarity(string $referenceImagePath, array $candidateImages, float $threshold = null): array
    {
        $threshold = $threshold ?? self::DEFAULT_SIMILARITY_THRESHOLD;

        Log::info("Filtering images by similarity", [
            'total_candidates' => count($candidateImages),
            'threshold' => $threshold
        ]);

        $filteredImages = [];

        foreach ($candidateImages as $index => $image) {
            $similarity = $this->compareImages($referenceImagePath, $image['url']);

            if ($similarity !== null) {
                $image['similarity_score'] = $similarity;

                if ($similarity >= $threshold) {
                    $filteredImages[] = $image;
                    Log::info("Image passed similarity filter", [
                        'index' => $index,
                        'similarity' => $similarity,
                        'url' => $image['url']
                    ]);
                } else {
                    Log::info("Image rejected by similarity filter", [
                        'index' => $index,
                        'similarity' => $similarity,
                        'threshold' => $threshold
                    ]);
                }
            }
        }

        // Ordena por similaridade (maior primeiro)
        usort($filteredImages, function($a, $b) {
            return ($b['similarity_score'] ?? 0) <=> ($a['similarity_score'] ?? 0);
        });

        Log::info("Similarity filtering completed", [
            'original_count' => count($candidateImages),
            'filtered_count' => count($filteredImages)
        ]);

        return $filteredImages;
    }

    /**
     * Converte imagem local para base64
     */
    private function imageToBase64(string $path): ?string
    {
        try {
            // Remove /storage/ do início se existir
            $storagePath = str_replace('/storage/', '', $path);

            if (Storage::disk('public')->exists($storagePath)) {
                $content = Storage::disk('public')->get($storagePath);
                return base64_encode($content);
            }

            // Tenta caminho absoluto
            if (file_exists($path)) {
                $content = file_get_contents($path);
                return base64_encode($content);
            }

            Log::warning("Reference image not found", ['path' => $path]);
            return null;

        } catch (\Exception $e) {
            Log::error("Error converting image to base64: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Baixa imagem de URL e converte para base64
     */
    private function imageUrlToBase64(string $url): ?string
    {
        try {
            $response = Http::timeout(15)->get($url);

            if ($response->successful()) {
                return base64_encode($response->body());
            }

            Log::warning("Failed to download candidate image", ['url' => $url]);
            return null;

        } catch (\Exception $e) {
            Log::error("Error downloading image: " . $e->getMessage(), ['url' => $url]);
            return null;
        }
    }

    /**
     * Analisa uma imagem e retorna características descritivas
     * Útil para debug e entender por que imagens são ou não similares
     */
    public function analyzeImage(string $imagePath): ?array
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            return null;
        }

        try {
            $base64 = $this->imageToBase64($imagePath);

            if (!$base64) {
                return null;
            }

            $prompt = "Descreva este produto de forma objetiva em português. " .
                     "Inclua: tipo de produto, cor principal, formato/tamanho aparente, " .
                     "se tem embalagem visível, marca (se identificável). " .
                     "Seja conciso (máximo 3 linhas).";

            $response = Http::timeout(30)->post(self::GEMINI_VISION_ENDPOINT . '?key=' . $apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => 'image/jpeg',
                                    'data' => $base64
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 200,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $description = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                return [
                    'description' => trim($description),
                    'path' => $imagePath
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Error analyzing image: " . $e->getMessage());
            return null;
        }
    }
}
