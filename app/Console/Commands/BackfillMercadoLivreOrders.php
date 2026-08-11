<?php

namespace App\Console\Commands;

use App\Models\CompanyIntegration;
use App\Services\MercadoLivreService;
use App\Services\OrderIngestionService;
use Illuminate\Console\Command;

class BackfillMercadoLivreOrders extends Command
{
    protected $signature = 'ml:backfill-orders
        {company? : ID da empresa (padrão: todas com integração ML ativa)}
        {--since= : Buscar pedidos criados a partir desta data (YYYY-MM-DD)}
        {--limit=50 : Tamanho da página (máx. 50 no ML)}
        {--max-pages=40 : Limite de páginas por empresa (trava de segurança)}';

    protected $description = 'Importa pedidos históricos do Mercado Livre para a base local (sem notificar).';

    public function handle(MercadoLivreService $ml, OrderIngestionService $ingestion): int
    {
        $companyArg = $this->argument('company');
        $limit = max(1, min(50, (int) $this->option('limit')));
        $maxPages = max(1, (int) $this->option('max-pages'));
        $since = $this->normalizeSince($this->option('since'));

        if ($since === false) {
            $this->error('--since inválido. Use o formato YYYY-MM-DD.');
            return self::FAILURE;
        }

        $companyIds = $this->resolveCompanyIds($companyArg);
        if ($companyIds->isEmpty()) {
            $this->warn('Nenhuma empresa com integração Mercado Livre ativa encontrada.');
            return self::SUCCESS;
        }

        $grandTotal = 0;

        foreach ($companyIds as $companyId) {
            $this->line("Empresa #{$companyId}: importando pedidos...");
            $offset = 0;
            $page = 0;
            $imported = 0;

            do {
                $data = $ml->searchOrders($companyId, $offset, $limit, $since);
                if ($data === null) {
                    $this->warn("  Empresa #{$companyId}: sem token válido ou falha na API. Pulando.");
                    break;
                }

                $results = $data['results'] ?? [];
                foreach ($results as $order) {
                    try {
                        $ingestion->ingest($companyId, $order, notify: false);
                        $imported++;
                    } catch (\Throwable $e) {
                        $this->warn("  Falha ao importar pedido {$order['id']}: {$e->getMessage()}");
                    }
                }

                $total = (int) ($data['paging']['total'] ?? 0);
                $offset += $limit;
                $page++;
            } while (count($results) === $limit && $offset < $total && $page < $maxPages);

            $this->info("  Empresa #{$companyId}: {$imported} pedido(s) processado(s).");
            $grandTotal += $imported;
        }

        $this->info("Backfill concluído: {$grandTotal} pedido(s) no total.");
        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function resolveCompanyIds(?string $companyArg)
    {
        $query = CompanyIntegration::query()
            ->where('integration_type', 'mercado_livre')
            ->where('active', true);

        if ($companyArg !== null) {
            $query->where('company_id', (int) $companyArg);
        }

        return $query->orderBy('company_id')->pluck('company_id')->unique()->values();
    }

    /**
     * @return string|null|false  string normalizada, null se ausente, false se inválida.
     */
    private function normalizeSince(?string $since)
    {
        if (empty($since)) {
            return null;
        }

        $ts = strtotime($since);
        if ($ts === false) {
            return false;
        }

        // Formato ISO 8601 aceito pelo ML (ex.: 2026-01-01T00:00:00.000-00:00).
        return gmdate('Y-m-d\TH:i:s.000-00:00', $ts);
    }
}
