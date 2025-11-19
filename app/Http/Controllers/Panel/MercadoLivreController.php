<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\MercadoLivreService;

class MercadoLivreController extends Controller
{
    protected MercadoLivreService $mlService;

    public function __construct(MercadoLivreService $mlService)
    {
        $this->mlService = $mlService;
    }

    /**
     * Mostra tela de análise e preparação do anúncio
     */
    public function prepare(int $productId)
    {
        $product = DB::table('products')->find($productId);
        abort_unless($product, 404);

        // Busca imagens do produto
        $images = DB::table('product_images')
            ->where('product_id', $productId)
            ->orderBy('sort')
            ->get();

        // Busca rascunho existente se houver
        $listing = DB::table('mercado_livre_listings')
            ->where('product_id', $productId)
            ->first();

        // Valida qualidade do produto
        $validation = $this->mlService->validateProduct($product, $images);

        // Prediz categoria baseada no título
        $suggestedCategories = $this->mlService->predictCategory($product->name);

        return view('panel.mercado_livre.prepare', compact(
            'product',
            'images',
            'listing',
            'validation',
            'suggestedCategories'
        ));
    }

    /**
     * Salva rascunho do anúncio
     */
    public function saveDraft(Request $request, int $productId)
    {
        $product = DB::table('products')->find($productId);
        abort_unless($product, 404);

        $validated = $request->validate([
            'title' => 'nullable|string|max:60',
            'category_id' => 'required|string|max:20',
            'price' => 'required|numeric|min:0.01',
            'available_quantity' => 'required|integer|min:1',
            'condition' => 'required|in:new,used',
            'listing_type_id' => 'required|in:gold_special,gold_pro,free',
            'plain_text_description' => 'nullable|string',
            'video_id' => 'nullable|string|max:20',
            'shipping_mode' => 'required|in:me2,custom',
            'free_shipping' => 'boolean',
            'shipping_local_pick_up' => 'required|in:true,false',
            'warranty_type' => 'nullable|string|max:50',
            'warranty_time' => 'nullable|string|max:50',
        ]);

        // Usa título do produto se não informado
        if (empty($validated['title'])) {
            $validated['title'] = mb_substr($product->name, 0, 60);
        }

        // Busca imagens para validação
        $images = DB::table('product_images')
            ->where('product_id', $productId)
            ->orderBy('sort')
            ->get();

        // Revalida com os novos dados
        $validation = $this->mlService->validateProduct($product, $images);

        $validated['quality_score'] = $validation['percentage'];
        $validated['missing_fields'] = $validation['missing_fields'];
        $validated['validation_errors'] = $validation['errors'];

        // Salva rascunho
        $listingId = $this->mlService->saveDraft($productId, $validated);

        return back()->with('ok', 'Rascunho salvo com sucesso! Score de qualidade: ' . $validation['percentage'] . '%');
    }

    /**
     * Publica o anúncio no Mercado Livre (futuro)
     */
    public function publish(int $productId)
    {
        $product = DB::table('products')->find($productId);
        abort_unless($product, 404);

        $listing = DB::table('mercado_livre_listings')
            ->where('product_id', $productId)
            ->first();

        abort_unless($listing, 404, 'Rascunho não encontrado. Prepare o anúncio primeiro.');

        // Busca imagens
        $images = DB::table('product_images')
            ->where('product_id', $productId)
            ->orderBy('sort')
            ->get();

        // Valida antes de publicar
        $validation = $this->mlService->validateProduct($product, $images);

        if (!$validation['can_publish']) {
            return back()->with('error', 'Não é possível publicar. Corrija os erros: ' . implode(', ', $validation['errors']));
        }

        // TODO: Integração real com a API do ML
        // Por enquanto, apenas atualiza o status para pending_review

        DB::table('mercado_livre_listings')
            ->where('id', $listing->id)
            ->update([
                'status' => 'pending_review',
                'updated_at' => now()
            ]);

        return back()->with('ok', 'Anúncio enviado para revisão! A integração com a API do ML será implementada em breve.');
    }

    /**
     * Busca atributos da categoria selecionada
     */
    public function getCategoryAttributes(Request $request)
    {
        $categoryId = $request->get('category_id');

        if (!$categoryId) {
            return response()->json(['error' => 'category_id é obrigatório'], 400);
        }

        $attributes = $this->mlService->getCategoryAttributes($categoryId);

        return response()->json($attributes);
    }
}
