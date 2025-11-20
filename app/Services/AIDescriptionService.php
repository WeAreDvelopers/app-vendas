<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ProductRaw;

class AIDescriptionService
{
    private const GEMINI_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private const OPENAI_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /**
     * Gera descrição usando Google Gemini primeiro, depois OpenAI como fallback
     */
    public function generateDescription(object $product): array
    {
        $prompt = $this->buildPrompt($product);

        // Tenta Gemini primeiro
        $geminiResult = $this->tryGemini($prompt, $product);
        if ($geminiResult['success']) {
            return $geminiResult;
        }

        Log::warning("Gemini failed, trying OpenAI fallback", [
            'product_id' => $product->id,
            'gemini_error' => $geminiResult['error']
        ]);

        // Fallback para OpenAI
        $openaiResult = $this->tryOpenAI($prompt, $product);
        if ($openaiResult['success']) {
            return $openaiResult;
        }

        Log::warning("Both AI services failed, using fallback description", [
            'product_id' => $product->id,
            'openai_error' => $openaiResult['error']
        ]);

        // Fallback final: descrição básica
        return [
            'success' => true,
            'description' => $this->fallbackDescription($product),
            'provider' => 'fallback',
            'cost' => 0
        ];
    }

    /**
     * Tenta gerar descrição usando Google Gemini
     */
    private function tryGemini(string $prompt, object $product): array
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'Gemini API key not configured'
            ];
        }

        try {
            $model = config('services.gemini.model', 'gemini-1.5-flash');
            $url = self::GEMINI_ENDPOINT . $model . ':generateContent?key=' . $apiKey;

            $response = Http::timeout(30)
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $this->buildGeminiSystemPrompt() . "\n\n" . $prompt
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 1024,
                    ],
                    'safetySettings' => [
                        [
                            'category' => 'HARM_CATEGORY_HARASSMENT',
                            'threshold' => 'BLOCK_NONE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_HATE_SPEECH',
                            'threshold' => 'BLOCK_NONE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                            'threshold' => 'BLOCK_NONE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                            'threshold' => 'BLOCK_NONE'
                        ],
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $description = $data['candidates'][0]['content']['parts'][0]['text'];

                    Log::info("Gemini generated description successfully", [
                        'product_id' => $product->id,
                        'model' => $model,
                        'tokens' => $data['usageMetadata'] ?? null
                    ]);

                    return [
                        'success' => true,
                        'description' => $description,
                        'provider' => 'gemini',
                        'model' => $model,
                        'cost' => 0 // Gemini tem tier gratuito generoso
                    ];
                }
            }

            // Verifica se estourou o rate limit
            if ($response->status() === 429) {
                return [
                    'success' => false,
                    'error' => 'Gemini rate limit exceeded',
                    'response' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Gemini API returned unsuccessful response',
                'status' => $response->status(),
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Tenta gerar descrição usando OpenAI
     */
    private function tryOpenAI(string $prompt, object $product): array
    {
        $apiKey = config('services.openai.key');

        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'OpenAI API key not configured'
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post(self::OPENAI_ENDPOINT, [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->buildOpenAISystemPrompt()
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 500
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $description = $data['choices'][0]['message']['content'] ?? null;

                if ($description) {
                    // Calcula custo estimado (GPT-4o-mini)
                    $inputTokens = $data['usage']['prompt_tokens'] ?? 0;
                    $outputTokens = $data['usage']['completion_tokens'] ?? 0;
                    $cost = ($inputTokens * 0.00015 / 1000) + ($outputTokens * 0.0006 / 1000);

                    Log::info("OpenAI generated description successfully", [
                        'product_id' => $product->id,
                        'model' => 'gpt-4o-mini',
                        'tokens' => $data['usage'],
                        'cost_usd' => $cost
                    ]);

                    return [
                        'success' => true,
                        'description' => $description,
                        'provider' => 'openai',
                        'model' => 'gpt-4o-mini',
                        'cost' => $cost
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'OpenAI API returned unsuccessful response',
                'status' => $response->status(),
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function buildGeminiSystemPrompt(): string
    {
        return "Você é um especialista em criar descrições de produtos para e-commerce, especialmente para o Mercado Livre. " .
               "Crie descrições atrativas, detalhadas e otimizadas para SEO. " .
               "Use formatação em markdown (títulos com ##, listas com -, negrito com **)." .
               "Seja persuasivo e focado em conversão.";
    }

    private function buildOpenAISystemPrompt(): string
    {
        return "Você é um especialista em criar descrições de produtos para e-commerce, especialmente para o Mercado Livre. " .
               "Crie descrições atrativas, detalhadas e otimizadas para SEO.";
    }

    private function buildPrompt(object $product): string
    {
        // Extrai contexto adicional se fornecido
        $additionalContext = '';
        if (isset($product->extra) && is_array($product->extra) && !empty($product->extra['context'])) {
            $additionalContext = "\n\nContexto Adicional: {$product->extra['context']}\n" .
                                "Leve em consideração este contexto ao criar a descrição.\n";
        }

        // Tenta acessar sale_price ou price dependendo do tipo de objeto
        $price = $product->sale_price ?? $product->price ?? 0;

        return "Crie uma descrição atrativa e completa para o seguinte produto:\n\n" .
               "Nome: {$product->name}\n" .
               "Marca: " . ($product->brand ?? 'N/A') . "\n" .
               "SKU: {$product->sku}\n" .
               "EAN: " . ($product->ean ?? 'N/A') . "\n" .
               "Preço: R$ " . number_format($price, 2, ',', '.') . "\n" .
               $additionalContext . "\n" .
               "A descrição deve:\n" .
               "- Ter entre 200-400 palavras\n" .
               "- Destacar os principais benefícios do produto\n" .
               "- Incluir informações técnicas relevantes quando aplicável\n" .
               "- Ser persuasiva e otimizada para conversão\n" .
               "- Usar formatação em markdown (títulos, listas, negrito)\n" .
               "- Incluir chamadas para ação sutis\n\n" .
               "Formato esperado:\n" .
               "## [Título Atrativo]\n\n" .
               "[Parágrafo introdutório]\n\n" .
               "## Características Principais\n" .
               "- [Característica 1]\n" .
               "- [Característica 2]\n" .
               "...\n\n" .
               "## Especificações\n" .
               "[Detalhes técnicos se aplicável]\n\n" .
               "[Parágrafo de fechamento com CTA]";
    }

    private function fallbackDescription(object $product): string
    {
        $brand = $product->brand ? " da marca {$product->brand}" : '';
        $ean = $product->ean ? " (EAN: {$product->ean})" : '';

        return "## {$product->name}\n\n" .
               "Produto{$brand}{$ean}\n\n" .
               "### Características\n\n" .
               "- **SKU**: {$product->sku}\n" .
               ($product->brand ? "- **Marca**: {$product->brand}\n" : '') .
               ($product->ean ? "- **Código de Barras (EAN)**: {$product->ean}\n" : '') .
               "\n### Informações de Compra\n\n" .
               "Produto original e com garantia. Entre em contato para mais informações sobre especificações técnicas e disponibilidade.\n\n" .
               "**Aproveite esta oferta!**";
    }
}
