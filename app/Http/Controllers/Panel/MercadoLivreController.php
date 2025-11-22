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
     * Redireciona para autorização do Mercado Livre
     */
    public function connect()
    {
        $authUrl = $this->mlService->getAuthorizationUrl();
        return redirect($authUrl);
    }

    /**
     * Callback OAuth - recebe código de autorização
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error) {
            return redirect()->route('panel.dashboard')
                ->with('error', 'Autorização negada pelo Mercado Livre.');
        }

        if (!$code) {
            return redirect()->route('panel.dashboard')
                ->with('error', 'Código de autorização não recebido.');
        }

        // Troca código por tokens
        $tokenData = $this->mlService->getAccessToken($code);

        if (!$tokenData) {
            return redirect()->route('panel.dashboard')
                ->with('error', 'Erro ao obter token de acesso do Mercado Livre.');
        }

        // Salva token no banco
        $userId = auth()->id();
        $this->mlService->saveToken($userId, $tokenData);

        // Busca informações do usuário ML
        $userInfo = $this->mlService->getUserInfo($tokenData['access_token']);

        if ($userInfo) {
            // Atualiza nickname do usuário ML
            DB::table('mercado_livre_tokens')
                ->where('user_id', $userId)
                ->update([
                    'ml_nickname' => $userInfo['nickname'] ?? null,
                    'updated_at' => now()
                ]);
        }

        return redirect()->route('panel.dashboard')
            ->with('ok', 'Conectado ao Mercado Livre com sucesso!');
    }

    /**
     * Desconecta da conta Mercado Livre
     */
    public function disconnect()
    {
        DB::table('mercado_livre_tokens')
            ->where('user_id', auth()->id())
            ->update([
                'is_active' => false,
                'updated_at' => now()
            ]);

        return redirect()->route('panel.dashboard')
            ->with('ok', 'Desconectado do Mercado Livre.');
    }

    /**
     * Verifica status da conexão
     */
    public function status()
    {
        $token = $this->mlService->getActiveToken(auth()->id());

        if ($token) {
            return response()->json([
                'connected' => true,
                'ml_nickname' => $token->ml_nickname,
                'expires_at' => $token->expires_at,
            ]);
        }

        return response()->json(['connected' => false]);
    }

    /**
     * Recebe notificações do Mercado Livre
     */
    public function notifications(Request $request)
    {
        // Log da notificação para debug
        \Log::info('ML Notification received', $request->all());

        // TODO: Processar notificações (vendas, perguntas, etc)

        return response()->json(['status' => 'ok']);
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

        // Se tiver listing, usa os dados do listing para validação
        // Isso garante que campos preenchidos no form sejam considerados
        $productForValidation = clone $product;
        if ($listing) {
            // Sobrescreve com dados do listing quando disponíveis
            $productForValidation->price = $listing->price ?? $product->price;
            $productForValidation->name = $listing->title ?? $product->name;
            $productForValidation->stock = $listing->available_quantity ?? $product->stock;
            $productForValidation->description = $listing->plain_text_description ?? $product->description;
        }

        // Valida qualidade do produto (usando dados do listing se existir)
        $validation = $this->mlService->validateProduct($productForValidation, $images);

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

        // Cria objeto de produto com os dados do formulário para validação
        $productForValidation = clone $product;
        $productForValidation->name = $validated['title'] ?? $product->name;
        $productForValidation->price = $validated['price'];
        $productForValidation->stock = $validated['available_quantity'];
        $productForValidation->description = $validated['plain_text_description'] ?? $product->description;

        // Revalida com os novos dados
        $validation = $this->mlService->validateProduct($productForValidation, $images);

        $validated['quality_score'] = $validation['percentage'];
        $validated['missing_fields'] = $validation['missing_fields'];
        $validated['validation_errors'] = $validation['errors'];

        // Salva rascunho
        $listingId = $this->mlService->saveDraft($productId, $validated);

        return back()->with('ok', 'Rascunho salvo com sucesso! Score de qualidade: ' . $validation['percentage'] . '%');
    }

    /**
     * Publica o anúncio no Mercado Livre
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

        // Usa dados do listing para validação (mesma lógica do prepare)
        $productForValidation = clone $product;
        $productForValidation->price = $listing->price ?? $product->price;
        $productForValidation->name = $listing->title ?? $product->name;
        $productForValidation->stock = $listing->available_quantity ?? $product->stock;
        $productForValidation->description = $listing->plain_text_description ?? $product->description;

        // Valida antes de publicar
        $validation = $this->mlService->validateProduct($productForValidation, $images);

        if (!$validation['can_publish']) {
            return back()->with('error', 'Não é possível publicar. Corrija os erros: ' . implode(', ', $validation['errors']));
        }

        // Verifica se está conectado ao ML
        $token = $this->mlService->getActiveToken(auth()->id());

        if (!$token) {
            return back()->with('error', 'Você precisa conectar sua conta do Mercado Livre primeiro.');
        }

        // Prepara payload
        $listingData = (array) $listing;
        $payload = $this->mlService->prepareListingPayload($product, $listingData, $images);

        // Publica no Mercado Livre
        $result = $this->mlService->publishListing($token->access_token, $payload);

        if (!$result || isset($result['error'])) {
            $errorMsg = $result['message'] ?? 'Erro desconhecido ao publicar';

            // Log detalhado do erro
            \Log::error('Erro ao publicar no ML', [
                'product_id' => $productId,
                'error' => $result,
                'payload' => $payload
            ]);

            // Mostra detalhes do erro se houver
            if (isset($result['details']['cause'])) {
                $causes = collect($result['details']['cause'])->pluck('message')->implode('; ');
                return back()->with('error', 'Erro ao publicar no ML: ' . $errorMsg . ' - ' . $causes);
            }

            return back()->with('error', 'Erro ao publicar no ML: ' . $errorMsg);
        }

        // Atualiza listing com dados do ML
        DB::table('mercado_livre_listings')
            ->where('id', $listing->id)
            ->update([
                'ml_id' => $result['id'],
                'status' => $result['status'],
                'published_at' => now(),
                'last_sync_at' => now(),
                'updated_at' => now()
            ]);

        return back()->with('ok', 'Anúncio publicado no Mercado Livre com sucesso! ID: ' . $result['id']);
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
