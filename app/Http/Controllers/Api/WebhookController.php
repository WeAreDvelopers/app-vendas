<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Support\MercadoLivre\CompanyResolver;

class WebhookController extends Controller
{
    protected CompanyResolver $companyResolver;

    public function __construct(CompanyResolver $companyResolver)
    {
        $this->companyResolver = $companyResolver;
    }

    /**
     * Recebe notificações do Mercado Livre
     *
     * Tipos de notificação:
     * - orders_v2: Novas vendas ou atualizações de pedidos
     * - items: Alterações em anúncios (pausado, finalizado, etc)
     * - questions: Novas perguntas
     * - claims: Reclamações
     */
    public function mercadoLivre(Request $request)
    {
        // Log da notificação recebida
        Log::info('ML Webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'ip' => $request->ip()
        ]);

        // Valida origem do Mercado Livre (IPs conhecidos)
        if (!$this->isValidMLSource($request)) {
            Log::warning('ML Webhook from invalid source', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Invalid source'], 403);
        }

        // Extrai dados da notificação
        $topic = $request->input('topic');
        $resource = $request->input('resource');
        $userId = $request->input('user_id');

        if (!$topic || !$resource) {
            Log::warning('ML Webhook missing required fields', $request->all());
            return response()->json(['message' => 'Missing required fields'], 400);
        }

        // Processa de acordo com o tipo
        try {
            switch ($topic) {
                case 'orders_v2':
                    $this->processOrder($resource, $userId);
                    break;

                case 'items':
                    $this->processItem($resource, $userId);
                    break;

                case 'questions':
                    $this->processQuestion($resource, $userId);
                    break;

                case 'claims':
                    $this->processClaim($resource, $userId);
                    break;

                default:
                    Log::info('ML Webhook topic not handled', ['topic' => $topic]);
            }

            return response()->json(['message' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::error('Error processing ML webhook', [
                'topic' => $topic,
                'resource' => $resource,
                'error' => $e->getMessage()
            ]);

            // Retorna 200 mesmo com erro para evitar re-tentativas do ML
            return response()->json(['message' => 'error'], 200);
        }
    }

    /**
     * Valida a origem da notificação por segredo compartilhado.
     *
     * O Mercado Livre não assina os webhooks (sem HMAC), então protegemos o
     * endpoint com um segredo configurado no próprio callback URL:
     *   .../api/webhooks/mercado-livre?secret=XXXX
     * ou via header X-Webhook-Secret. Se nenhum segredo estiver configurado,
     * a validação fica desligada (compatível com o setup atual).
     */
    private function isValidMLSource(Request $request): bool
    {
        $expected = config('services.mercado_livre.webhook_secret');

        // Sem segredo configurado → não valida origem.
        if (empty($expected)) {
            return true;
        }

        $provided = $request->header('X-Webhook-Secret') ?? $request->query('secret');

        return is_string($provided) && hash_equals((string) $expected, $provided);
    }

    /**
     * Processa notificação de pedido
     */
    private function processOrder(string $resource, ?int $mlUserId): void
    {
        Log::info('Processing ML order notification', [
            'resource' => $resource,
            'ml_user_id' => $mlUserId
        ]);

        // Extrai o order ID do resource. Formato: /orders/{order_id}
        preg_match('/\/orders\/([\w\-]+)/', $resource, $matches);
        $orderId = $matches[1] ?? null;

        if (!$orderId) {
            Log::warning('Could not extract order ID from resource', ['resource' => $resource]);
            return;
        }

        // Resolve a empresa dona da conta ML (vendedor -> empresa) — consulta
        // barata (indexada), sem chamar a API do ML.
        $companyId = $mlUserId !== null
            ? $this->companyResolver->companyIdForMlUser($mlUserId)
            : null;

        if (!$companyId) {
            Log::warning('No company resolved for ML user', ['ml_user_id' => $mlUserId]);
            return;
        }

        // Enfileira: o fetch na API do ML + ingestão rodam fora da thread do
        // webhook (o ML exige resposta rápida; timeouts geram reentrega).
        \App\Jobs\IngestMLOrder::dispatch($companyId, (string) $orderId);
    }

    /**
     * Processa notificação de item (anúncio)
     */
    private function processItem(string $resource, ?int $mlUserId): void
    {
        Log::info('Processing ML item notification', [
            'resource' => $resource,
            'ml_user_id' => $mlUserId
        ]);

        // Extrai o item ID do resource
        // Formato: /items/{item_id}
        preg_match('/\/items\/([A-Z0-9\-]+)/', $resource, $matches);
        $itemId = $matches[1] ?? null;

        if (!$itemId) {
            Log::warning('Could not extract item ID from resource', ['resource' => $resource]);
            return;
        }

        // Atualiza status do produto local se existir
        $listing = DB::table('mercado_livre_listings')
            ->where('ml_id', $itemId)
            ->first();

        if ($listing) {
            // Busca token para verificar status atual
            $token = DB::table('mercado_livre_tokens')
                ->where('ml_user_id', $mlUserId)
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($token) {
                try {
                    $response = Http::withToken($token->access_token)
                        ->get("https://api.mercadolibre.com{$resource}");

                    if ($response->successful()) {
                        $itemData = $response->json();

                        // Atualiza status do listing
                        DB::table('mercado_livre_listings')
                            ->where('id', $listing->id)
                            ->update([
                                'status' => $itemData['status'] ?? 'active',
                                'available_quantity' => $itemData['available_quantity'] ?? 0,
                                'sold_quantity' => $itemData['sold_quantity'] ?? 0,
                                'updated_at' => now()
                            ]);

                        Log::info('ML listing updated', [
                            'ml_id' => $itemId,
                            'status' => $itemData['status'] ?? 'unknown'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error updating item status', [
                        'item_id' => $itemId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Processa notificação de pergunta
     */
    private function processQuestion(string $resource, ?int $mlUserId): void
    {
        Log::info('Processing ML question notification', [
            'resource' => $resource,
            'ml_user_id' => $mlUserId
        ]);

        // Busca token do usuário
        $token = DB::table('mercado_livre_tokens')
            ->where('ml_user_id', $mlUserId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$token) {
            return;
        }

        try {
            $response = Http::withToken($token->access_token)
                ->get("https://api.mercadolibre.com{$resource}");

            if ($response->successful()) {
                $questionData = $response->json();

                // Cria notificação para o usuário
                if (!isset($questionData['answer'])) {
                    $this->createUserNotification(
                        $token->user_id,
                        'Nova pergunta no Mercado Livre',
                        $questionData['text'] ?? 'Você recebeu uma nova pergunta',
                        'info',
                        null // TODO: Link para responder pergunta
                    );
                }

                Log::info('ML question processed', [
                    'question_id' => $questionData['id'] ?? null
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing question', [
                'resource' => $resource,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Processa notificação de reclamação
     */
    private function processClaim(string $resource, ?int $mlUserId): void
    {
        Log::info('Processing ML claim notification', [
            'resource' => $resource,
            'ml_user_id' => $mlUserId
        ]);

        // Busca token do usuário
        $token = DB::table('mercado_livre_tokens')
            ->where('ml_user_id', $mlUserId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($token) {
            // Cria notificação urgente para o usuário
            $this->createUserNotification(
                $token->user_id,
                'Reclamação no Mercado Livre',
                'Você recebeu uma nova reclamação. Responda o mais rápido possível.',
                'warning',
                null
            );
        }
    }

    /**
     * Cria notificação para o usuário
     */
    private function createUserNotification(int $userId, string $title, string $message, string $type = 'info', ?string $actionUrl = null): void
    {
        try {
            DB::table('notifications')->insert([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'action_url' => $actionUrl,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('User notification created', [
                'user_id' => $userId,
                'title' => $title
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating user notification', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Webhook genérico para outras plataformas (Shopee, Shopify, etc)
     */
    public function shopee(Request $request)
    {
        Log::info('Shopee webhook received', $request->all());

        // TODO: Implementar quando integração Shopee estiver ativa

        return response()->json(['message' => 'ok'], 200);
    }

    public function shopify(Request $request)
    {
        Log::info('Shopify webhook received', $request->all());

        // TODO: Implementar quando integração Shopify estiver ativa

        return response()->json(['message' => 'ok'], 200);
    }
}
