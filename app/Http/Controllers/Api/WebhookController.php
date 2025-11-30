<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\MercadoLivreService;

class WebhookController extends Controller
{
    protected MercadoLivreService $mlService;

    public function __construct(MercadoLivreService $mlService)
    {
        $this->mlService = $mlService;
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
     * Valida se a requisição vem de um IP do Mercado Livre
     */
    private function isValidMLSource(Request $request): bool
    {
        $ip = $request->ip();

        // IPs conhecidos do Mercado Livre (adicione conforme necessário)
        $allowedIPs = [
            '209.225.49.0/24',  // Range do ML
            '200.221.0.0/16',   // Range do ML
            '127.0.0.1',        // Localhost para testes
            '::1'               // IPv6 localhost
        ];

        // Em produção, você pode validar também por user-agent
        // ou por assinatura HMAC se o ML fornecer

        // Por enquanto, aceita todas as requisições
        // TODO: Implementar validação mais rigorosa em produção
        return true;
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

        // Extrai o order ID do resource
        // Formato: /orders/{order_id}
        preg_match('/\/orders\/(\d+)/', $resource, $matches);
        $orderId = $matches[1] ?? null;

        if (!$orderId) {
            Log::warning('Could not extract order ID from resource', ['resource' => $resource]);
            return;
        }

        // Busca token do usuário
        $token = DB::table('mercado_livre_tokens')
            ->where('ml_user_id', $mlUserId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$token) {
            Log::warning('Token not found for ML user', ['ml_user_id' => $mlUserId]);
            return;
        }

        // Busca detalhes do pedido via API
        try {
            $response = Http::withToken($token->access_token)
                ->get("https://api.mercadolibre.com{$resource}");

            if (!$response->successful()) {
                Log::error('Failed to fetch order details', [
                    'order_id' => $orderId,
                    'status' => $response->status()
                ]);
                return;
            }

            $orderData = $response->json();

            // Salva/atualiza o pedido no banco
            $this->saveOrder($orderData, $token->user_id);

            // Cria notificação para o usuário
            $this->createUserNotification(
                $token->user_id,
                'Nova venda no Mercado Livre',
                "Pedido #{$orderId} recebido. Total: R$ " . number_format($orderData['total_amount'] ?? 0, 2, ',', '.'),
                'success',
                route('panel.orders.index') // Assumindo que existe essa rota
            );

        } catch (\Exception $e) {
            Log::error('Error processing order', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
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
     * Salva ou atualiza um pedido no banco de dados
     */
    private function saveOrder(array $orderData, int $userId): void
    {
        $orderId = $orderData['id'] ?? null;

        if (!$orderId) {
            return;
        }

        // Verifica se o pedido já existe
        $existingOrder = DB::table('orders')
            ->where('ml_order_id', $orderId)
            ->first();

        $orderInfo = [
            'ml_order_id' => $orderId,
            'user_id' => $userId,
            'status' => $orderData['status'] ?? 'pending',
            'total_amount' => $orderData['total_amount'] ?? 0,
            'paid_amount' => $orderData['paid_amount'] ?? 0,
            'currency_id' => $orderData['currency_id'] ?? 'BRL',
            'buyer_id' => $orderData['buyer']['id'] ?? null,
            'buyer_nickname' => $orderData['buyer']['nickname'] ?? null,
            'payment_type' => $orderData['payments'][0]['payment_type'] ?? null,
            'shipping_status' => $orderData['shipping']['status'] ?? null,
            'data' => json_encode($orderData),
            'updated_at' => now()
        ];

        if ($existingOrder) {
            // Atualiza pedido existente
            DB::table('orders')
                ->where('id', $existingOrder->id)
                ->update($orderInfo);

            Log::info('Order updated', ['order_id' => $orderId]);
        } else {
            // Cria novo pedido
            $orderInfo['created_at'] = now();

            DB::table('orders')->insert($orderInfo);

            Log::info('Order created', ['order_id' => $orderId]);
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
