<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageSearchService;
use App\Services\ImageProcessingService;
use App\Services\AIDescriptionService;

class ProductUIController extends Controller {
    public function index(Request $r) {
        $search = trim($r->get('q',''));
        $query = DB::table('products');
        if ($search) {
            $query->where(function($q) use ($search){
                $q->where('name','like',"%$search%")
                  ->orWhere('sku','like',"%$search%")
                  ->orWhere('ean','like',"%$search%");
            });
        }
        $products = $query->orderByDesc('id')->paginate(24)->withQueryString();
        return view('panel.products.index', compact('products','search'));
    }

    public function show(int $id) {
        $product = DB::table('products')->find($id);
        abort_unless($product, 404);

        // Busca produto raw relacionado para ver dados da IA
        $productRaw = null;
        if ($product->product_raw_id) {
            $productRaw = DB::table('products_raw')->find($product->product_raw_id);
        }

        // Busca imagens do produto
        $images = DB::table('product_images')
            ->where('product_id', $id)
            ->orderBy('sort')
            ->get();

        return view('panel.products.show', compact('product', 'productRaw', 'images'));
    }

    public function edit(int $id) {
        $product = DB::table('products')->find($id);
        abort_unless($product, 404);

        $images = DB::table('product_images')
            ->where('product_id', $id)
            ->orderBy('sort')
            ->get();

        return view('panel.products.edit', compact('product', 'images'));
    }

    public function update(Request $r, int $id) {
        $r->validate([
            // Campos básicos
            'name' => 'required|string|max:255',
            'ean' => 'nullable|string|max:20',
            'brand' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',

            // Campos do Mercado Livre
            'title' => 'nullable|string|max:60',
            'condition' => 'required|in:new,used',
            'warranty' => 'nullable|string|max:50',
            'video_url' => 'nullable|url',

            // Descrição
            'description' => 'nullable|string',

            // Preço e estoque
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',

            // Dimensões e peso (obrigatórios para ML)
            'weight' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'length' => 'required|numeric|min:0',
        ], [
            // Mensagens customizadas em português
            'name.required' => 'O nome do produto é obrigatório.',
            'title.max' => 'O título do anúncio deve ter no máximo 60 caracteres.',
            'condition.required' => 'A condição do produto é obrigatória.',
            'condition.in' => 'A condição deve ser "novo" ou "usado".',
            'price.required' => 'O preço de venda é obrigatório.',
            'price.min' => 'O preço deve ser maior ou igual a zero.',
            'stock.required' => 'A quantidade em estoque é obrigatória.',
            'stock.min' => 'O estoque não pode ser negativo.',
            'weight.required' => 'O peso é obrigatório para o Mercado Livre.',
            'weight.min' => 'O peso deve ser maior que zero.',
            'width.required' => 'A largura é obrigatória para o Mercado Livre.',
            'width.min' => 'A largura deve ser maior que zero.',
            'height.required' => 'A altura é obrigatória para o Mercado Livre.',
            'height.min' => 'A altura deve ser maior que zero.',
            'length.required' => 'O comprimento é obrigatório para o Mercado Livre.',
            'length.min' => 'O comprimento deve ser maior que zero.',
            'video_url.url' => 'A URL do vídeo deve ser válida.',
        ]);

        // Atualiza o produto com todos os campos
        DB::table('products')->where('id', $id)->update([
            // Campos básicos
            'name' => $r->name,
            'ean' => $r->ean,
            'brand' => $r->brand,
            'category' => $r->category,

            // Campos do Mercado Livre
            'title' => $r->title,
            'condition' => $r->condition,
            'warranty' => $r->warranty,
            'video_url' => $r->video_url,

            // Descrição
            'description' => $r->description,

            // Preço e estoque
            'price' => $r->price,
            'cost_price' => $r->cost_price,
            'stock' => $r->stock,

            // Dimensões e peso
            'weight' => $r->weight,
            'width' => $r->width,
            'height' => $r->height,
            'length' => $r->length,

            // Atualiza timestamp
            'updated_at' => now()
        ]);

        return redirect()->route('panel.products.show', $id)
            ->with('ok', 'Produto atualizado com sucesso!');
    }

    public function uploadImages(Request $r, int $id, ImageProcessingService $imageProcessor) {
        $product = DB::table('products')->find($id);
        abort_unless($product, 404);

        $r->validate([
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'resize_images' => 'nullable|boolean'
        ]);

        $shouldResize = $r->boolean('resize_images', false);

        $currentMaxSort = DB::table('product_images')
            ->where('product_id', $id)
            ->max('sort') ?? 0;

        $uploadedCount = 0;

        foreach ($r->file('images') as $index => $file) {
            try {
                // Se o usuário marcou o checkbox, processa a imagem
                if ($shouldResize) {
                    // Processa: redimensiona, adiciona fundo branco e garante mínimo 500x500
                    $processedImage = $imageProcessor->processAndSaveProductImage($file, $id, 500, 500);
                    $imagePath = $processedImage['path'];
                } else {
                    // Upload normal sem processamento
                    $filename = uniqid('product_' . $id . '_') . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('product_images', $filename, 'public');
                    $imagePath = '/storage/' . $path;
                }

                // Insere no banco
                DB::table('product_images')->insert([
                    'product_id' => $id,
                    'path' => $imagePath,
                    'source_url' => $imagePath,
                    'sort' => $currentMaxSort + $index + 1,
                    'bg_removed' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $uploadedCount++;
            } catch (\Exception $e) {
                \Log::error("Erro ao processar imagem para produto {$id}: " . $e->getMessage());
                continue;
            }
        }

        if ($uploadedCount > 0) {
            $message = "{$uploadedCount} imagem(ns) adicionada(s) com sucesso!";
            if ($shouldResize) {
                $message .= " (Redimensionadas e otimizadas com fundo branco)";
            }
            return back()->with('ok', $message);
        } else {
            return back()->with('error', 'Erro ao fazer upload das imagens. Tente novamente.');
        }
    }

    public function uploadReferenceImage(Request $r, int $id) {
        $product = DB::table('products')->find($id);
        abort_unless($product, 404);

        $r->validate([
            'reference_image' => 'required|image|mimes:jpeg,jpg,png|max:5120',
            'similarity_threshold' => 'required|numeric|min:0|max:1'
        ]);

        // Remove a imagem de referência anterior se existir
        if ($product->reference_image_path) {
            $oldPath = str_replace('/storage/', '', $product->reference_image_path);
            Storage::disk('public')->delete($oldPath);
        }

        // Salva a nova imagem de referência
        $file = $r->file('reference_image');
        $filename = uniqid('reference_' . $id . '_') . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('product_references', $filename, 'public');

        // Atualiza o produto
        DB::table('products')->where('id', $id)->update([
            'reference_image_path' => '/storage/' . $path,
            'similarity_threshold' => $r->similarity_threshold,
            'updated_at' => now()
        ]);

        return back()->with('ok', 'Imagem de referência definida! A busca por similaridade está ativa.');
    }

    public function deleteReferenceImage(int $id) {
        $product = DB::table('products')->find($id);
        abort_unless($product, 404);

        // Remove a imagem de referência
        if ($product->reference_image_path) {
            $oldPath = str_replace('/storage/', '', $product->reference_image_path);
            Storage::disk('public')->delete($oldPath);
        }

        // Atualiza o produto
        DB::table('products')->where('id', $id)->update([
            'reference_image_path' => null,
            'similarity_threshold' => 0.7, // Reset para o padrão
            'updated_at' => now()
        ]);

        return back()->with('ok', 'Imagem de referência removida. A busca por similaridade foi desativada.');
    }

    public function searchImages(Request $r, int $id, ImageSearchService $imageService) {
        $product = DB::table('products')->find($id);
        abort_unless($product, 404);

        $r->validate([
            'limit' => 'required|integer|min:1|max:10',
        ]);

        try {
            $limit = $r->input('limit', 5);
            $useSimilarity = $r->boolean('use_similarity', false);

            \Log::info("Iniciando busca de imagens", [
                'product_id' => $id,
                'limit' => $limit,
                'use_similarity' => $useSimilarity,
                'has_reference' => !empty($product->reference_image_path)
            ]);

            // Verifica se tem as configurações necessárias
            if (!config('services.google_search.api_key') || !config('services.google_search.cx')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Custom Search não está configurado. Verifique as chaves GOOGLE_SEARCH_API_KEY e GOOGLE_SEARCH_CX no .env'
                ], 400);
            }

            if ($useSimilarity && !config('services.gemini.key')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gemini API não está configurada. Verifique a chave GEMINI_API_KEY no .env'
                ], 400);
            }

            // Busca imagens usando o serviço
            $searchResults = $imageService->searchForProduct($product, $useSimilarity);

            \Log::info("Busca retornou {count} resultados", ['count' => count($searchResults)]);

            if (empty($searchResults)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma imagem encontrada para este produto. Verifique se o produto tem EAN, nome ou marca preenchidos.'
                ]);
            }

            // Limita aos N melhores resultados
            $imagesToShow = array_slice($searchResults, 0, $limit);

            // Retorna as imagens para preview
            return response()->json([
                'success' => true,
                'images' => $imagesToShow,
                'total' => count($imagesToShow),
                'filtered_by_similarity' => $useSimilarity
            ]);

        } catch (\Exception $e) {
            \Log::error("Error searching images for product {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar imagens: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadSelectedImages(Request $r, int $id, ImageSearchService $imageService) {
        $product = DB::table('products')->find($id);
        abort_unless($product, 404);

        $r->validate([
            'images' => 'required|array|min:1',
            'images.*.url' => 'required|url',
        ]);

        try {
            $selectedImages = $r->input('images');

            \Log::info("Baixando imagens selecionadas", [
                'product_id' => $id,
                'count' => count($selectedImages)
            ]);

            // Pega o max sort atual
            $currentMaxSort = DB::table('product_images')
                ->where('product_id', $id)
                ->max('sort') ?? 0;

            $successCount = 0;
            $failedUrls = [];

            foreach ($selectedImages as $index => $imageData) {
                try {
                    // Faz download e otimiza a imagem
                    $downloadedImage = $imageService->downloadAndOptimize($imageData['url'], $id);

                    if ($downloadedImage) {
                        DB::table('product_images')->insert([
                            'product_id' => $id,
                            'path' => $downloadedImage['path'],
                            'source_url' => $downloadedImage['source_url'],
                            'sort' => $currentMaxSort + $index + 1,
                            'bg_removed' => false,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        $successCount++;
                    } else {
                        $failedUrls[] = $imageData['url'];
                    }
                } catch (\Exception $e) {
                    \Log::warning("Failed to download image for product {$id}: " . $e->getMessage());
                    $failedUrls[] = $imageData['url'];
                }
            }

            $message = "{$successCount} imagem(ns) baixada(s) com sucesso!";
            if (count($failedUrls) > 0) {
                $message .= " ({count($failedUrls)} falharam)";
            }

            return back()->with('ok', $message);

        } catch (\Exception $e) {
            \Log::error("Error downloading selected images for product {$id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao baixar imagens: ' . $e->getMessage());
        }
    }

    public function deleteImage(int $id, int $imageId) {
        $product = DB::table('products')->find($id);
        abort_unless($product, 404);

        $image = DB::table('product_images')->where('id', $imageId)->where('product_id', $id)->first();
        abort_unless($image, 404);

        try {
            // Remove o arquivo físico se existir
            if ($image->path) {
                $filePath = str_replace('/storage/', '', $image->path);
                Storage::disk('public')->delete($filePath);
            }

            // Remove do banco de dados
            DB::table('product_images')->where('id', $imageId)->delete();

            // Reorganiza a ordem das imagens restantes
            $remainingImages = DB::table('product_images')
                ->where('product_id', $id)
                ->orderBy('sort')
                ->get();

            foreach ($remainingImages as $index => $img) {
                DB::table('product_images')
                    ->where('id', $img->id)
                    ->update(['sort' => $index + 1]);
            }

            return back()->with('ok', 'Imagem removida com sucesso!');

        } catch (\Exception $e) {
            \Log::error("Error deleting image {$imageId}: " . $e->getMessage());
            return back()->with('error', 'Erro ao remover imagem: ' . $e->getMessage());
        }
    }

    public function deleteAllImages(int $id) {
        $product = DB::table('products')->find($id);
        abort_unless($product, 404);

        try {
            $images = DB::table('product_images')->where('product_id', $id)->get();
            $deletedCount = 0;

            foreach ($images as $image) {
                // Remove o arquivo físico se existir
                if ($image->path) {
                    $filePath = str_replace('/storage/', '', $image->path);
                    Storage::disk('public')->delete($filePath);
                }
                $deletedCount++;
            }

            // Remove todos do banco de dados
            DB::table('product_images')->where('product_id', $id)->delete();

            return back()->with('ok', "{$deletedCount} imagem(ns) removida(s) com sucesso!");

        } catch (\Exception $e) {
            \Log::error("Error deleting all images for product {$id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao remover imagens: ' . $e->getMessage());
        }
    }

    public function destroy(int $id) {
        $product = DB::table('products')->find($id);
        abort_unless($product, 404);

        try {
            // 1. Remove todas as imagens do storage
            $images = DB::table('product_images')->where('product_id', $id)->get();
            foreach ($images as $image) {
                if ($image->path) {
                    $filePath = str_replace('/storage/', '', $image->path);
                    Storage::disk('public')->delete($filePath);
                }
            }

            // 2. Remove imagem de referência se existir
            if ($product->reference_image_path) {
                $refPath = str_replace('/storage/', '', $product->reference_image_path);
                Storage::disk('public')->delete($refPath);
            }

            // 3. Remove as imagens do banco de dados
            DB::table('product_images')->where('product_id', $id)->delete();

            // 4. Remove o produto do banco de dados
            DB::table('products')->where('id', $id)->delete();

            \Log::info("Produto #{$id} excluído com sucesso, incluindo {$images->count()} imagens do storage");

            return redirect()->route('panel.products.index')
                ->with('ok', 'Produto excluído com sucesso!');

        } catch (\Exception $e) {
            \Log::error("Erro ao excluir produto {$id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao excluir produto: ' . $e->getMessage());
        }
    }

    public function regenerateDescription(Request $r, int $id, AIDescriptionService $aiService) {
        $product = DB::table('products')->find($id);
        abort_unless($product, 404);

        $r->validate([
            'context' => 'nullable|string|max:1000'
        ]);

        try {
            $context = $r->input('context', '');

            // Cria um objeto simulando ProductRaw para o serviço
            $productData = (object) [
                'id' => $product->id,
                'sku' => $product->sku,
                'ean' => $product->ean,
                'name' => $product->name,
                'brand' => $product->brand,
                'extra' => ['context' => $context]
            ];

            // Gera nova descrição com IA
            $aiResult = $aiService->generateDescription($productData);

            \Log::info("Descrição regenerada para produto #{$id}", [
                'provider' => $aiResult['provider'],
                'cost' => $aiResult['cost'] ?? 0,
                'has_context' => !empty($context)
            ]);

            return response()->json([
                'success' => true,
                'description' => $aiResult['description'],
                'provider' => $aiResult['provider'],
                'model' => $aiResult['model'] ?? null
            ]);

        } catch (\Exception $e) {
            \Log::error("Erro ao regerar descrição do produto {$id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar descrição: ' . $e->getMessage()
            ], 500);
        }
    }
}
