<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MercadoLivreService
{
    private const API_BASE_URL = 'https://api.mercadolibre.com';
    private const AUTH_URL = 'https://auth.mercadolivre.com.br/authorization';
    private const TOKEN_URL = 'https://api.mercadolibre.com/oauth/token';

    /**
     * Gera URL de autorização para OAuth
     */
    public function getAuthorizationUrl(): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => config('services.mercado_livre.app_id'),
            'redirect_uri' => config('services.mercado_livre.redirect_uri'),
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Troca o código de autorização por tokens de acesso
     */
    public function getAccessToken(string $code): ?array
    {
        try {
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'authorization_code',
                'client_id' => config('services.mercado_livre.app_id'),
                'client_secret' => config('services.mercado_livre.secret_key'),
                'code' => $code,
                'redirect_uri' => config('services.mercado_livre.redirect_uri'),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error getting ML access token', ['response' => $response->json()]);
            return null;

        } catch (\Exception $e) {
            Log::error('Exception getting ML access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Renova o access token usando refresh token
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        try {
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'refresh_token',
                'client_id' => config('services.mercado_livre.app_id'),
                'client_secret' => config('services.mercado_livre.secret_key'),
                'refresh_token' => $refreshToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error refreshing ML token', ['response' => $response->json()]);
            return null;

        } catch (\Exception $e) {
            Log::error('Exception refreshing ML token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Salva tokens de autenticação no banco
     */
    public function saveToken(?int $userId, array $tokenData): int
    {
        $expiresAt = now()->addSeconds($tokenData['expires_in']);

        // Remove tokens antigos do mesmo usuário
        if ($userId) {
            DB::table('mercado_livre_tokens')
                ->where('user_id', $userId)
                ->delete();
        }

        return DB::table('mercado_livre_tokens')->insertGetId([
            'user_id' => $userId,
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'expires_in' => $tokenData['expires_in'],
            'expires_at' => $expiresAt,
            'ml_user_id' => $tokenData['user_id'] ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Busca token ativo (renova automaticamente se expirado)
     */
    public function getActiveToken(?int $userId = null): ?object
    {
        $query = DB::table('mercado_livre_tokens')
            ->where('is_active', true);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $token = $query->orderBy('created_at', 'desc')->first();

        if (!$token) {
            return null;
        }

        // Verifica se o token expirou ou está próximo de expirar (5 min)
        if (now()->addMinutes(5)->greaterThan($token->expires_at)) {
            $newTokenData = $this->refreshAccessToken($token->refresh_token);

            if ($newTokenData) {
                // Atualiza token no banco
                DB::table('mercado_livre_tokens')
                    ->where('id', $token->id)
                    ->update([
                        'access_token' => $newTokenData['access_token'],
                        'refresh_token' => $newTokenData['refresh_token'],
                        'expires_in' => $newTokenData['expires_in'],
                        'expires_at' => now()->addSeconds($newTokenData['expires_in']),
                        'last_refresh_at' => now(),
                        'updated_at' => now(),
                    ]);

                // Recarrega token atualizado
                $token = DB::table('mercado_livre_tokens')->find($token->id);
            }
        }

        return $token;
    }

    /**
     * Busca informações do usuário ML autenticado
     */
    public function getUserInfo(string $accessToken): ?array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get(self::API_BASE_URL . '/users/me');

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error getting ML user info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Valida e prepara dados do produto para publicação no ML
     * Retorna análise de qualidade (score 0-100)
     */
    public function validateProduct($product, $images = []): array
    {
        $score = 0;
        $maxScore = 100;
        $missingFields = [];
        $warnings = [];
        $errors = [];

        // 1. Título (20 pontos)
        if (!empty($product->name)) {
            $titleLength = mb_strlen($product->name);
            if ($titleLength <= 60) {
                $score += 20;
            } else {
                $warnings[] = "Título muito longo ({$titleLength} caracteres). Máximo: 60.";
                $score += 10; // Pontos parciais
            }
        } else {
            $errors[] = 'Título é obrigatório';
            $missingFields[] = 'title';
        }

        // 2. Preço (15 pontos)
        if (!empty($product->price) && $product->price > 0) {
            $score += 15;
        } else {
            $errors[] = 'Preço é obrigatório e deve ser maior que zero';
            $missingFields[] = 'price';
        }

        // 3. Descrição (15 pontos)
        if (!empty($product->description)) {
            $descLength = mb_strlen($product->description);
            if ($descLength >= 100) {
                $score += 15;
            } else {
                $warnings[] = "Descrição muito curta ({$descLength} caracteres). Recomendado: 100+.";
                $score += 8;
            }
        } else {
            $warnings[] = 'Descrição não preenchida. Recomendado para melhor conversão.';
            $missingFields[] = 'description';
        }

        // 4. Imagens (20 pontos)
        $imageCount = count($images);
        if ($imageCount >= 6) {
            $score += 20;
        } elseif ($imageCount >= 3) {
            $score += 15;
            $warnings[] = "Apenas {$imageCount} imagens. Recomendado: 6+ para melhor conversão.";
        } elseif ($imageCount >= 1) {
            $score += 10;
            $warnings[] = "Apenas {$imageCount} imagem(ns). Mínimo: 1, Recomendado: 6+.";
        } else {
            $errors[] = 'Pelo menos 1 imagem é obrigatória';
            $missingFields[] = 'pictures';
        }

        // Valida tamanho das imagens
        foreach ($images as $index => $image) {
            // Verifica se a imagem existe no storage
            $imagePath = str_replace('/storage/', '', $image->path);
            $fullPath = storage_path('app/public/' . $imagePath);

            if (file_exists($fullPath)) {
                $imageSize = getimagesize($fullPath);
                if ($imageSize) {
                    [$width, $height] = $imageSize;

                    if ($width < 500 || $height < 500) {
                        $warnings[] = "Imagem #{$index} muito pequena ({$width}x{$height}px). Mínimo: 500x500px.";
                    }

                    if ($width < 1200 || $height < 1200) {
                        $warnings[] = "Imagem #{$index} abaixo do recomendado. Ideal: 1200x1200px para zoom.";
                    }
                }
            }
        }

        // 5. EAN/GTIN (10 pontos)
        if (!empty($product->ean)) {
            $score += 10;
        } else {
            $warnings[] = 'EAN/GTIN não preenchido. Recomendado para categorias obrigatórias.';
            $missingFields[] = 'ean';
        }

        // 6. Marca (10 pontos)
        if (!empty($product->brand)) {
            $score += 10;
        } else {
            $warnings[] = 'Marca não preenchida. Importante para posicionamento.';
            $missingFields[] = 'brand';
        }

        // 7. Estoque (5 pontos)
        if (isset($product->stock) && $product->stock > 0) {
            $score += 5;
        } else {
            $warnings[] = 'Estoque zero ou não definido.';
        }

        // 8. SKU (5 pontos)
        if (!empty($product->sku)) {
            $score += 5;
        } else {
            $warnings[] = 'SKU não preenchido.';
        }

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => round(($score / $maxScore) * 100),
            'missing_fields' => $missingFields,
            'warnings' => $warnings,
            'errors' => $errors,
            'can_publish' => count($errors) === 0,
            'quality_level' => $score >= 80 ? 'excellent' : ($score >= 60 ? 'good' : ($score >= 40 ? 'fair' : 'poor'))
        ];
    }

    /**
     * Prepara payload para criação de anúncio no ML
     */
    public function prepareListingPayload($product, $listingData, $images = []): array
    {
        // Prepara título otimizado (máx 60 chars)
        // Prioriza o título do listing se disponível
        $productName = $listingData['title'] ?? $product->name;
        $title = $this->optimizeTitle($productName, $product->brand);

        // Prepara imagens (máximo 10)
        $pictures = [];
        $imageList = is_array($images) ? $images : $images->all();
        foreach (array_slice($imageList, 0, 10) as $image) {
            // URL pública da imagem
            $imageUrl = url($image->path);
            $pictures[] = ['source' => $imageUrl];
        }

        // Prepara atributos da categoria
        // IMPORTANTE: Estes são atributos básicos do produto que serão
        // sobrescritos se o usuário preencher manualmente no formulário
        $attributes = [];

        // Atributo BRAND (obrigatório em muitas categorias)
        if (!empty($product->brand)) {
            $attributes[] = [
                'id' => 'BRAND',
                'value_name' => $product->brand
            ];
        }

        // Atributo MODEL (obrigatório em muitas categorias)
        // Usa o nome do produto ou SKU como modelo se não tiver um campo específico
        $model = $product->model ?? $product->sku ?? $product->name;
        if (!empty($model)) {
            $attributes[] = [
                'id' => 'MODEL',
                'value_name' => substr($model, 0, 255) // Limite de 255 caracteres
            ];
        }

        // Atributo GTIN (EAN)
        if (!empty($product->ean)) {
            $attributes[] = [
                'id' => 'GTIN',
                'value_name' => $product->ean
            ];
        }

        // Atributo SKU do seller
        if (!empty($product->sku)) {
            $attributes[] = [
                'id' => 'SELLER_SKU',
                'value_name' => $product->sku
            ];
        }

        // Condição do item (novo/usado)
        // Usa 'new' como padrão se não tiver o campo condition
        $condition = $product->condition ?? 'new';
        $attributes[] = [
            'id' => 'ITEM_CONDITION',
            'value_name' => $condition === 'used' ? 'Usado' : 'Novo'
        ];

        // Dimensões e peso do pacote (se disponíveis)
        if (!empty($product->weight)) {
            $attributes[] = [
                'id' => 'PACKAGE_WEIGHT',
                'value_name' => (string) $product->weight,
                'value_struct' => [
                    'number' => (float) $product->weight,
                    'unit' => 'kg'
                ]
            ];
        }

        if (!empty($product->length)) {
            $attributes[] = [
                'id' => 'PACKAGE_LENGTH',
                'value_name' => (string) $product->length,
                'value_struct' => [
                    'number' => (float) $product->length,
                    'unit' => 'cm'
                ]
            ];
        }

        if (!empty($product->width)) {
            $attributes[] = [
                'id' => 'PACKAGE_WIDTH',
                'value_name' => (string) $product->width,
                'value_struct' => [
                    'number' => (float) $product->width,
                    'unit' => 'cm'
                ]
            ];
        }

        if (!empty($product->height)) {
            $attributes[] = [
                'id' => 'PACKAGE_HEIGHT',
                'value_name' => (string) $product->height,
                'value_struct' => [
                    'number' => (float) $product->height,
                    'unit' => 'cm'
                ]
            ];
        }

        // Adiciona atributos customizados do listingData se disponíveis
        if (!empty($listingData['attributes'])) {
            $customAttributes = is_string($listingData['attributes'])
                ? json_decode($listingData['attributes'], true)
                : $listingData['attributes'];

            // Se ainda for string (JSON duplo), decodifica novamente
            if (!is_array($customAttributes) && is_string($customAttributes)) {
                $customAttributes = json_decode($customAttributes, true);
            }

            if (is_array($customAttributes)) {
                // Mescla atributos customizados, evitando duplicatas
                foreach ($customAttributes as $attr) {
                    // Aceita se tiver ID do atributo e pelo menos value_name OU value_id
                    if (isset($attr['id']) && (isset($attr['value_name']) || isset($attr['value_id']))) {
                        // Remove atributo existente com mesmo ID se houver
                        $attributes = array_filter($attributes, fn($a) => $a['id'] !== $attr['id']);
                        $attributes[] = $attr;
                    }
                }
            }
        }

        // Adiciona atributos com valores padrão automáticos da categoria
        if (!empty($listingData['category_id'])) {
            $categoryAttrs = $this->getCategoryAttributes($listingData['category_id']);

            if (!empty($categoryAttrs['auto_filled'])) {
                foreach ($categoryAttrs['auto_filled'] as $attr) {
                    // Apenas adiciona se não foi preenchido manualmente
                    $attrExists = false;
                    foreach ($attributes as $existingAttr) {
                        if ($existingAttr['id'] === $attr['id']) {
                            $attrExists = true;
                            break;
                        }
                    }

                    if (!$attrExists && !empty($attr['default_value'])) {
                        $attributes[] = [
                            'id' => $attr['id'],
                            'value_name' => $attr['default_value']
                        ];
                    }
                }
            }
        }

        // Payload base
        $payload = [
            'title' => $title,
            'category_id' => $listingData['category_id'],
            'price' => (float) $listingData['price'],
            'currency_id' => 'BRL',
            'available_quantity' => (int) ($listingData['available_quantity'] ?? $product->stock ?? 1),
            'buying_mode' => 'buy_it_now',
            'condition' => $listingData['condition'] ?? 'new',
            'listing_type_id' => $listingData['listing_type_id'] ?? 'gold_special',
            'pictures' => $pictures,
            'attributes' => $attributes,
        ];

        // Adiciona descrição se disponível
        // Prioriza a descrição do listing (plain_text_description), depois a do produto
        $description = $listingData['plain_text_description'] ?? $product->description ?? null;
        if (!empty($description)) {
            $payload['description'] = [
                'plain_text' => is_string($description) ? $this->stripMarkdown($description) : $description
            ];
        }

        // Adiciona video_id se disponível
        if (!empty($listingData['video_id'])) {
            $payload['video_id'] = $listingData['video_id'];
        }

        // Configurações de envio
        // Nota: Frete grátis requer modo 'me1' (Mercado Envios Full)
        // Se usar 'me2' (Mercado Envios normal), não pode oferecer frete grátis
        $shippingMode = $listingData['shipping_mode'] ?? 'me2';
        $freeShipping = (bool) ($listingData['free_shipping'] ?? false);

        // Desabilita frete grátis se não estiver usando me1
        if ($shippingMode !== 'me1' && $freeShipping) {
            $freeShipping = false;
            Log::warning('Frete grátis desabilitado: requer modo me1 (Mercado Envios Full)');
        }

        $payload['shipping'] = [
            'mode' => $shippingMode,
            'free_shipping' => $freeShipping,
            'local_pick_up' => $listingData['shipping_local_pick_up'] !== 'false',
        ];

        // Adiciona dimensões se disponíveis (importante para cálculo de frete)
        if (!empty($product->width) && !empty($product->height) && !empty($product->length)) {
            $payload['shipping']['dimensions'] = implode('x', [
                (float) $product->width,
                (float) $product->height,
                (float) $product->length
            ]);
        }

        // Adiciona peso se disponível (importante para cálculo de frete)
        if (!empty($product->weight)) {
            $payload['shipping']['weight'] = (float) $product->weight;
        }

        // Garantia (se disponível)
        if (!empty($listingData['warranty_type'])) {
            $payload['warranty'] = [
                'type' => $listingData['warranty_type'],
                'time' => $listingData['warranty_time'] ?? '90 dias'
            ];
        }

        return $payload;
    }

    /**
     * Otimiza título para ML (máx 60 caracteres)
     * Formato: Marca + Produto + Principais características
     */
    private function optimizeTitle(string $name, ?string $brand = null): string
    {
        $title = '';

        // Se tiver marca e não estiver no início do nome
        if ($brand && stripos($name, $brand) !== 0) {
            $title = $brand . ' ';
        }

        $title .= $name;

        // Trunca em 60 caracteres se necessário
        if (mb_strlen($title) > 60) {
            $title = mb_substr($title, 0, 57) . '...';
        }

        return $title;
    }

    /**
     * Remove markdown da descrição (ML aceita apenas texto puro)
     */
    private function stripMarkdown(string $markdown): string
    {
        // Remove headers (##)
        $text = preg_replace('/^#{1,6}\s+/m', '', $markdown);

        // Remove bold (**texto**)
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);

        // Remove italic (*texto*)
        $text = preg_replace('/\*(.*?)\*/', '$1', $text);

        // Remove links [texto](url)
        $text = preg_replace('/\[(.*?)\]\(.*?\)/', '$1', $text);

        // Remove bullets (- ou *)
        $text = preg_replace('/^[\*\-]\s+/m', '• ', $text);

        return trim($text);
    }

    /**
     * Cria ou atualiza rascunho do anúncio no banco
     */
    public function saveDraft($productId, array $data): int
    {
        // Busca rascunho existente
        $existing = DB::table('mercado_livre_listings')
            ->where('product_id', $productId)
            ->first();

        $listingData = [
            'product_id' => $productId,
            'title' => $data['title'] ?? '',
            'category_id' => $data['category_id'] ?? '',
            'price' => $data['price'] ?? 0,
            'currency_id' => 'BRL',
            'available_quantity' => $data['available_quantity'] ?? 1,
            'condition' => $data['condition'] ?? 'new',
            'listing_type_id' => $data['listing_type_id'] ?? 'gold_special',
            'plain_text_description' => $data['plain_text_description'] ?? null,
            'video_id' => $data['video_id'] ?? null,
            'attributes' => json_encode($data['attributes'] ?? []),
            'shipping_mode' => $data['shipping_mode'] ?? 'me2',
            'free_shipping' => $data['free_shipping'] ?? false,
            'shipping_local_pick_up' => $data['shipping_local_pick_up'] ?? 'true',
            'warranty_type' => $data['warranty_type'] ?? null,
            'warranty_time' => $data['warranty_time'] ?? null,
            'quality_score' => $data['quality_score'] ?? 0,
            'missing_fields' => json_encode($data['missing_fields'] ?? []),
            'validation_errors' => json_encode($data['validation_errors'] ?? []),
            'status' => 'draft',
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('mercado_livre_listings')
                ->where('id', $existing->id)
                ->update($listingData);
            return $existing->id;
        } else {
            $listingData['created_at'] = now();
            return DB::table('mercado_livre_listings')->insertGetId($listingData);
        }
    }

    /**
     * Busca categorias sugeridas pelo ML baseado no título
     */
    public function predictCategory(string $title): array
    {
        try {
            $response = Http::timeout(10)->get(self::API_BASE_URL . '/sites/MLB/domain_discovery/search', [
                'q' => $title,
                'limit' => 5
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $categories = [];
                foreach ($data as $item) {
                    if (isset($item['category_id'])) {
                        $categories[] = [
                            'id' => $item['category_id'],
                            'name' => $item['category_name'] ?? $item['category_id'],
                            'domain_id' => $item['domain_id'] ?? null,
                            'domain_name' => $item['domain_name'] ?? null,
                        ];
                    }
                }

                return $categories;
            }

            return [];

        } catch (\Exception $e) {
            Log::error("Error predicting ML category: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca atributos obrigatórios de uma categoria
     * Retorna apenas os atributos necessários para publicação
     */
    public function getCategoryAttributes(string $categoryId): array
    {
        try {
            $response = Http::timeout(10)->get(self::API_BASE_URL . "/categories/{$categoryId}/attributes");

            if ($response->successful()) {
                $attributes = $response->json();

                // Atributos que devem ser exibidos para o usuário preencher
                $userFillableAttributes = [
                    // Identificação
                    'BRAND', 'MODEL', 'GTIN', 'SELLER_SKU', 'MPN',
                    // Características físicas
                    'COLOR', 'MAIN_COLOR', 'SIZE', 'MATERIAL', 'FABRIC', 'COMPOSITION',
                    // Específicos por categoria
                    'TOWEL_TYPE', 'PATTERN_NAME', 'GENDER', 'AGE_GROUP', 'CAPACITY',
                    // Embalagem e quantidade
                    'UNITS_PER_PACK', 'PACKAGE_LENGTH', 'PACKAGE_WIDTH', 'PACKAGE_HEIGHT',
                    'PACKAGE_WEIGHT', 'UNIT_WEIGHT',
                    // Toalhas específicas
                    'BATH_TOWELS_PER_PACKAGE', 'BATH_TOWELS_NUMBER', 'BATH_TOWEL_WIDTH',
                    'TOWEL_LENGTH',
                    // Condição e motivos
                    'ITEM_CONDITION', 'EMPTY_GTIN_REASON',
                    // Dados do seller
                    'SELLER_PACKAGE_DATA_SOURCE', 'SELLER_PACKAGE_WIDTH', 'SELLER_PACKAGE_LENGTH',
                    'SELLER_PACKAGE_HEIGHT', 'SELLER_PACKAGE_TYPE', 'SELLER_PACKAGE_WEIGHT',
                ];

                // Atributos que terão valores padrão automáticos
                // IMPORTANTE: Removemos atributos "not modifiable" que o ML ignora
                $defaultValues = [
                    'ITEM_CONDITION' => 'Novo',
                    'IS_KIT' => 'Não',
                    // Removidos atributos não modificáveis:
                    // WITH_POSITIVE_IMPACT, IS_FLAMMABLE, IS_SUITABLE_FOR_SHIPMENT,
                    // HAS_COMPATIBILITIES, IS_NEW_OFFER, HAZMAT_TRANSPORTABILITY,
                    // SHIPMENT_PACKING, IMPORT_DUTY, FOODS_AND_DRINKS, MEDICINES,
                    // BATTERIES_FEATURES, ADDITIONAL_INFO_REQUIRED, EXCLUDED_PLATFORMS,
                    // PRODUCT_CHEMICAL_FEATURES, PRODUCT_FEATURES, LIMITED_MARKETPLACE_VISIBILITY_REASONS,
                    // VERTICAL_TAGS
                ];

                // Filtra e organiza atributos
                $organized = [
                    'required' => [],
                    'optional' => [],
                    'auto_filled' => [],
                ];

                foreach ($attributes as $attr) {
                    $attribute = [
                        'id' => $attr['id'],
                        'name' => $attr['name'],
                        'value_type' => $attr['value_type'] ?? 'string',
                        'values' => $attr['values'] ?? [],
                        'tags' => $attr['tags'] ?? [],
                        'hint' => $attr['hint'] ?? null,
                        'tooltip' => $attr['tooltip'] ?? null,
                        'allowed_units' => $attr['allowed_units'] ?? [],
                        'default_value' => $defaultValues[$attr['id']] ?? null,
                    ];

                    // Verifica se é obrigatório
                    // Nota: A API do ML retorna tags como objeto com chaves booleanas
                    $tags = $attr['tags'] ?? [];
                    $isRequired = !empty($tags['required']) || !empty($tags['catalog_required']);

                    // Verifica se o atributo é modificável
                    // Atributos com 'read_only' ou 'hidden' NÃO devem ser enviados
                    $isModifiable = empty($tags['read_only']) && empty($tags['hidden']);

                    if ($isRequired && $isModifiable) {
                        // Se tem valor padrão, adiciona aos auto_filled
                        if (isset($defaultValues[$attr['id']])) {
                            $organized['auto_filled'][] = $attribute;
                        }
                        // Se é atributo preenchível pelo usuário, adiciona aos required
                        elseif (in_array($attr['id'], $userFillableAttributes)) {
                            $organized['required'][] = $attribute;
                        }
                        // Atributos obrigatórios mas não na lista são ignorados
                        // (geralmente são calculados pelo ML automaticamente)
                    } elseif (!$isRequired && $isModifiable) {
                        // Apenas opcionais relevantes e modificáveis para o usuário
                        if (in_array($attr['id'], $userFillableAttributes)) {
                            $organized['optional'][] = $attribute;
                        }
                    }
                }

                return $organized;
            }

            return ['required' => [], 'optional' => [], 'auto_filled' => []];

        } catch (\Exception $e) {
            Log::error("Error fetching category attributes: " . $e->getMessage());
            return ['required' => [], 'optional' => [], 'auto_filled' => []];
        }
    }

    /**
     * Busca valores possíveis de um atributo específico
     */
    public function getAttributeValues(string $categoryId, string $attributeId): array
    {
        try {
            $attributes = $this->getCategoryAttributes($categoryId);

            // Procura o atributo específico
            foreach (array_merge($attributes['required'], $attributes['optional']) as $attr) {
                if ($attr['id'] === $attributeId) {
                    return $attr['values'] ?? [];
                }
            }

            return [];

        } catch (\Exception $e) {
            Log::error("Error fetching attribute values: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Publica anúncio no Mercado Livre
     */
    public function publishListing(string $accessToken, array $payload): ?array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->post(self::API_BASE_URL . '/items', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            // Retorna erro da API
            $error = $response->json();
            Log::error('Error publishing to ML', [
                'status' => $response->status(),
                'error' => $error
            ]);

            return [
                'error' => true,
                'message' => $error['message'] ?? 'Erro ao publicar anúncio',
                'details' => $error
            ];

        } catch (\Exception $e) {
            Log::error('Exception publishing to ML: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Atualiza anúncio existente no Mercado Livre
     */
    public function updateListing(string $accessToken, string $mlId, array $payload): ?array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->put(self::API_BASE_URL . "/items/{$mlId}", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            $error = $response->json();
            Log::error('Error updating ML listing', [
                'ml_id' => $mlId,
                'status' => $response->status(),
                'error' => $error
            ]);

            return [
                'error' => true,
                'message' => $error['message'] ?? 'Erro ao atualizar anúncio',
                'details' => $error
            ];

        } catch (\Exception $e) {
            Log::error('Exception updating ML listing: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Pausa/ativa anúncio no Mercado Livre
     */
    public function toggleListingStatus(string $accessToken, string $mlId, string $status): ?array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->put(self::API_BASE_URL . "/items/{$mlId}", [
                    'status' => $status // paused, active, closed
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Exception toggling ML listing status: ' . $e->getMessage());
            return null;
        }
    }
}
