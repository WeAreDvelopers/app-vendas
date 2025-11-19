<?php
namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageSearchService;

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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
        ]);

        DB::table('products')->where('id', $id)->update([
            'name' => $r->name,
            'description' => $r->description,
            'price' => $r->price,
            'cost_price' => $r->cost_price,
            'stock' => $r->stock ?? 0,
            'updated_at' => now()
        ]);

        return redirect()->route('panel.products.show', $id)
            ->with('ok', 'Produto atualizado com sucesso!');
    }

    public function uploadImages(Request $r, int $id) {
        $product = DB::table('products')->find($id);
        abort_unless($product, 404);

        $r->validate([
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,jpg,png|max:5120|dimensions:min_width=500,min_height=500'
        ]);

        $currentMaxSort = DB::table('product_images')
            ->where('product_id', $id)
            ->max('sort') ?? 0;

        $uploadedCount = 0;

        foreach ($r->file('images') as $index => $file) {
            // Gera nome único para o arquivo
            $filename = uniqid('product_' . $id . '_') . '.' . $file->getClientOriginalExtension();

            // Salva o arquivo
            $path = $file->storeAs('product_images', $filename, 'public');

            // Insere no banco
            DB::table('product_images')->insert([
                'product_id' => $id,
                'path' => '/storage/' . $path,
                'source_url' => '/storage/' . $path,
                'sort' => $currentMaxSort + $index + 1,
                'bg_removed' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $uploadedCount++;
        }

        return back()->with('ok', "{$uploadedCount} imagem(ns) adicionada(s) com sucesso!");
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
}
