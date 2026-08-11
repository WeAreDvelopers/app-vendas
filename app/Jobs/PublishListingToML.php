<?php

namespace App\Jobs;

use App\Services\MercadoLivreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\MLPublishCompleted;
use App\Notifications\MLPublishFailed;

class PublishListingToML implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min
    public $timeout = 120; // 2 minutos

    public function __construct(
        public int $productId,
        public int $userId
    ) {}

    public function handle(MercadoLivreService $mlService): void
    {
        // Atualiza status para 'processing'
        DB::table('mercado_livre_listings')
            ->where('product_id', $this->productId)
            ->update([
                'status' => 'processing',
                'updated_at' => now()
            ]);

        try {
            // Busca produto
            $product = DB::table('products')->find($this->productId);
            if (!$product) {
                throw new \Exception('Produto não encontrado');
            }

            // Busca listing
            $listing = DB::table('mercado_livre_listings')
                ->where('product_id', $this->productId)
                ->first();

            if (!$listing) {
                throw new \Exception('Rascunho não encontrado');
            }

            // Busca imagens
            $images = DB::table('product_images')
                ->where('product_id', $this->productId)
                ->orderBy('sort')
                ->get();

            // Busca token ativo
            $token = $mlService->getActiveToken($this->userId);
            if (!$token) {
                throw new \Exception('Token do Mercado Livre não encontrado ou expirado');
            }

            // Prepara payload
            $listingData = (array) $listing;
            $payload = $mlService->prepareListingPayload($product, $listingData, $images);

            // Publica no Mercado Livre
            $result = $mlService->publishListing($token->access_token, $payload);

            if (!$result || isset($result['error'])) {
                $errorMsg = $result['message'] ?? 'Erro desconhecido ao publicar';
                throw new \Exception($errorMsg);
            }

            // Atualiza listing com sucesso
            DB::table('mercado_livre_listings')
                ->where('id', $listing->id)
                ->update([
                    'ml_id' => $result['id'] ?? null,
                    'status' => $result['status'] ?? 'active',
                    'published_at' => now(),
                    'last_sync_at' => now(),
                    'updated_at' => now(),
                ]);

            // Log de sucesso
            Log::info('Anúncio publicado no ML com sucesso', [
                'product_id' => $this->productId,
                'ml_id' => $result['id'] ?? null,
            ]);

            // Envia notificação de sucesso
            $user = DB::table('users')->find($this->userId);
            if ($user && $user->email) {
                try {
                    Notification::route('mail', $user->email)
                        ->notify(new MLPublishCompleted($product, $result));
                } catch (\Exception $e) {
                    Log::warning('Erro ao enviar notificação de sucesso', ['error' => $e->getMessage()]);
                }
            }

        } catch (\Exception $e) {
            // Atualiza status para 'failed'
            DB::table('mercado_livre_listings')
                ->where('product_id', $this->productId)
                ->update([
                    'status' => 'failed',
                    'updated_at' => now()
                ]);

            // Log de erro
            Log::error('Erro ao publicar no ML', [
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Envia notificação de erro
            $user = DB::table('users')->find($this->userId);
            $product = DB::table('products')->find($this->productId);

            if ($user && $user->email && $product) {
                try {
                    Notification::route('mail', $user->email)
                        ->notify(new MLPublishFailed($product, $e->getMessage()));
                } catch (\Exception $notifError) {
                    Log::warning('Erro ao enviar notificação de falha', ['error' => $notifError->getMessage()]);
                }
            }

            // Re-lança exceção para que o Laravel registre a falha do job
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Marca como falha definitiva após todas as tentativas
        DB::table('mercado_livre_listings')
            ->where('product_id', $this->productId)
            ->update([
                'status' => 'failed',
                'updated_at' => now()
            ]);

        Log::error('Job PublishListingToML falhou definitivamente', [
            'product_id' => $this->productId,
            'error' => $exception->getMessage()
        ]);
    }
}
