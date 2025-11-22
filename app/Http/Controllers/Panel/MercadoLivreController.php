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
     * Redireciona para a tela de configuração do Mercado Livre
     */
    public function index()
    {
        $token = $this->mlService->getActiveToken(auth()->id());

        return view('panel.mercado_livre.index', compact('token'));
    }

    /**
     * Inicia o fluxo de conexão com o Mercado Livre (OAuth)
     */
    public function connect()
    {
        $authUrl = $this->mlService->getAuthUrl();

        return redirect()->away($authUrl);
    }

    /**
     * Callback de OAuth do Mercado Livre
     */
    public function callback(Request $request)
    {
        $code  = $request->get('code');
        $error = $request->get('error');

        if ($error || !$code) {
            return redirect()->route('panel.mercado-livre.index')
                ->with('error', 'Não foi possível conectar ao Mercado Livre. Tente novamente.');
        }

        try {
            $tokens = $this->mlService->exchangeCodeForToken($code, auth()->id());

            DB::table('mercado_livre_tokens')
                ->updateOrInsert(
                    ['user_id' => auth()->id()],
                    [
                        'access_token'  => $tokens['access_token'],
                        'refresh_token' => $tokens['refresh_token'],
                        'expires_at'    => now()->addSeconds($tokens['expires_in']),
                        'is_active'     => true,
                        'updated_at'    => now(),
                        'created_at'    => now(),
                    ]
                );

            return redirect()->route('panel.mercado-livre.index')
                ->with('ok', 'Conta Mercado Livre conectada com sucesso!');
        } catch (\Exception $e) {
            report($e);

            return redirect()->route('panel.mercado-livre.index')
                ->with('error', 'Erro ao conectar com o Mercado Livre: ' . $e->getMessage());
        }
    }

    /**
     * Desconecta da conta Mercado Livre
     */
    public function disconnect()
    {
        DB::table('mercado_livre_tokens')
            ->where('user_id', auth()->id())
            ->update([
                'is_active'   => false,
                'updated_at'  => now(),
            ]);

        return redirect()->route('panel.dashboard')
            ->with('ok', 'Conta Mercado Livre desconectada com sucesso.');
    }

    /**
     * Tela de preparação do anúncio
     */
    public function prepare(int $productId)
    {
        $product = DB::table('products')->find($productId);
        abort_unless($product, 404);

        $listing = DB::table('mercado_livre_listings')
            ->where('product_id', $productId)
            ->first();

        $images = DB::table('product_images')
            ->where('product_id', $productId)
            ->orderBy('sort')
            ->get();

        // Pega token ativo
        $token = $this->mlService->getActiveToken(auth()->id());

        if (!$token) {
            return redirect()->route('panel.mercado-livre.index')
                ->with('error', 'Você precisa conectar sua conta do Mercado Livre antes de preparar o anúncio.');
        }

        // Se já existir category_id, busca atributos obrigatórios da categoria
        $categoryAttributes = [];
        if ($listing && $listing->category_id) {
            $categoryAttributes = $this->mlService->getCategoryAttributes($listing->category_id);
        }

        return view('panel.mercado_livre.prepare', compact(
            'product',
            'listing',
            'images',
            'categoryAttributes'
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
            'title'                  => 'nullable|string|max:60',
            'category_id'            => 'required|string|max:20',
            'price'                  => 'required|numeric|min:0.01',
            'available_quantity'     => 'required|integer|min:1',
            'condition'              => 'required|in:new,used',
            'listing_type_id'        => 'required|in:gold_special,gold_pro,free',
            'plain_text_description' => 'nullable|string',
            'video_id'               => 'nullable|string|max:20',
            'shipping_mode'          => 'required|in:me2,custom',
            'free_shipping'          => 'boolean',
            'shipping_local_pick_up' => 'required|in:true,false',
            'warranty_type'          => 'nullable|string|max:50',
            'warranty_time'          => 'nullable|string|max:50',
            'ml_attr'                => 'nullable|array',
            'ml_attr.*'              => 'nullable|string',
        ]);

        // Busca imagens do produto
        $images = DB::table('product_images')
            ->where('product_id', $productId)
            ->orderBy('sort')
            ->get();

        // Monta attributes customizados (incluindo TOWEL_TYPE, etc.)
        $customAttributes = [];
        if (!empty($validated['ml_attr'])) {
            foreach ($validated['ml_attr'] as $attrId => $attrValue) {
                if (!empty($attrValue)) {
                    $attribute = ['id' => $attrId];

                    // Se o valor contém "|", separa em ID e nome
                    if (strpos($attrValue, '|') !== false) {
                        [$valueId, $valueName] = explode('|', $attrValue, 2);
                        $attribute['value_id']   = $valueId;
                        $attribute['value_name'] = $valueName;
                    } else {
                        // Se não tem "|", usa como value_name apenas
                        $attribute['value_name'] = $attrValue;
                    }

                    $customAttributes[] = $attribute;
                }
            }
        }

        // Salva em JSON para ser reaproveitado na publicação
        $validated['attributes'] = json_encode($customAttributes);

        // Cria objeto de produto com os dados do formulário para validação
        $productForValidation              = clone $product;
        $productForValidation->name        = $validated['title'] ?? $product->name;
        $productForValidation->price       = $validated['price'];
        $productForValidation->stock       = $validated['available_quantity'];
        $productForValidation->description = $validated['plain_text_description'] ?? $product->description;

        // Revalida com os novos dados
        $validation = $this->mlService->validateProduct($productForValidation, $images);

        $validated['quality_score']      = $validation['percentage'];
        $validated['missing_fields']     = $validation['missing_fields'];
        $validated['validation_errors']  = $validation['errors'];

        // Salva rascunho
        $listingId = $this->mlService->saveDraft($productId, $validated);

        // Se o usuário clicou em "Publicar Agora", redireciona para a rota de publicação
        if ($request->boolean('publish_now')) {
            return redirect()
                ->route('panel.mercado-livre.publish', $productId);
        }

        return back()->with('ok', 'Rascunho salvo com sucesso! Score de qualidade: ' . $validation['percentage'] . '%');
    }

    /**
     * Publica o anúncio no Mercado Livre
     */
    public function publish(Request $request, int $productId)
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

        // Usa dados do listing para validação (mesma lógica do prepare)
        $productForValidation              = clone $product;
        $productForValidation->price       = $listing->price ?? $product->price;
        $productForValidation->name        = $listing->title ?? $product->name;
        $productForValidation->stock       = $listing->available_quantity ?? $product->stock;
        $productForValidation->description = $listing->plain_text_description ?? $product->description;

        // Valida antes de publicar
        $validation = $this->mlService->validateProduct($productForValidation, $images);

        if (!$validation['can_publish']) {
            return back()->with(
                'error',
                'Não é possível publicar o anúncio. Corrija os erros: ' . implode(', ', $validation['errors'])
            );
        }

        // Verifica se está conectado ao ML
        $token = $this->mlService->getActiveToken(auth()->id());

        if (!$token) {
            return redirect()->route('panel.mercado-livre.index')
                ->with('error', 'Você precisa conectar sua conta do Mercado Livre antes de publicar o anúncio.');
        }

        // Monta payload final usando o listing (rascunho) + imagens
        $listingData = (array) $listing;
        $payload     = $this->mlService->prepareListingPayload($product, $listingData, $images);

        try {
            $result = $this->mlService->publishListing($payload);

            if (!empty($result['error'])) {
                $errorMsg = $result['error'];

                if (!empty($result['details']['cause'])) {
                    $causes = collect($result['details']['cause'])->pluck('message')->implode('; ');
                    return back()->with('error', 'Erro ao publicar no ML: ' . $errorMsg . ' - ' . $causes);
                }

                return back()->with('error', 'Erro ao publicar no ML: ' . $errorMsg);
            }

            // Atualiza listing com dados do ML
            DB::table('mercado_livre_listings')
                ->where('id', $listing->id)
                ->update([
                    'ml_id'        => $result['id'],
                    'status'       => $result['status'],
                    'published_at' => now(),
                    'last_sync_at' => now(),
                    'updated_at'   => now(),
                ]);

            return back()->with('ok', 'Anúncio publicado no Mercado Livre com sucesso! ID: ' . $result['id']);
        } catch (\Exception $e) {
            report($e);

            return back()->with('error', 'Erro inesperado ao publicar no Mercado Livre: ' . $e->getMessage());
        }
    }

    /**
     * Busca atributos da categoria selecionada (AJAX)
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
