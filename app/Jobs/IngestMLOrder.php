<?php

namespace App\Jobs;

use App\Services\MercadoLivreService;
use App\Services\OrderIngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Busca um pedido do Mercado Livre e o ingere — fora da thread do webhook.
 * O ML exige resposta rápida no webhook; qualquer chamada de API roda aqui.
 */
class IngestMLOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(
        public int $companyId,
        public string $orderId,
    ) {}

    public function handle(MercadoLivreService $ml, OrderIngestionService $ingestion): void
    {
        $mlOrder = $ml->getOrder($this->companyId, $this->orderId);

        if (!$mlOrder) {
            // Pode ser consistência eventual do ML (pedido recém-criado). Deixa o
            // backoff reentregar; após esgotar tries vai para failed_jobs.
            Log::warning('IngestMLOrder: pedido indisponível, tentará novamente', [
                'company_id' => $this->companyId, 'order_id' => $this->orderId,
            ]);
            throw new \RuntimeException("Pedido {$this->orderId} indisponível na API do ML");
        }

        $ingestion->ingest($this->companyId, $mlOrder);
    }
}
