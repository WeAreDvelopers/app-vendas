<?php

namespace Tests\Feature;

use App\Jobs\PrintJob;
use App\Models\Company;
use App\Models\CompanyIntegration;
use App\Models\Order;
use App\Services\MercadoLivreService;
use App\Services\OrderIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class PrintPipelineTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function payload(array $over = []): array
    {
        return array_merge([
            'id' => 'ORD-1',
            'status' => 'paid',
            'shipping' => ['id' => 'SHP-1', 'status' => 'ready_to_ship'],
            'order_items' => [],
        ], $over);
    }

    // ---- Gatilho na ingestão ----

    public function test_new_order_with_shipment_dispatches_print_job(): void
    {
        Bus::fake();
        $a = $this->makeCompany('A');

        app(OrderIngestionService::class)->ingest($a->id, $this->payload());

        Bus::assertDispatched(PrintJob::class, function (PrintJob $job) use ($a) {
            return $job->companyId === $a->id && $job->shipmentId === 'SHP-1';
        });
    }

    public function test_order_without_shipment_does_not_print(): void
    {
        Bus::fake();
        $a = $this->makeCompany('A');

        app(OrderIngestionService::class)->ingest($a->id, $this->payload(['shipping' => null]));

        Bus::assertNotDispatched(PrintJob::class);
    }

    public function test_backfill_does_not_print(): void
    {
        Bus::fake();
        $a = $this->makeCompany('A');

        app(OrderIngestionService::class)->ingest($a->id, $this->payload(), notify: false);

        Bus::assertNotDispatched(PrintJob::class);
    }

    public function test_redelivery_does_not_print_again(): void
    {
        $a = $this->makeCompany('A');
        app(OrderIngestionService::class)->ingest($a->id, $this->payload()); // cria
        Bus::fake();
        app(OrderIngestionService::class)->ingest($a->id, $this->payload()); // update

        Bus::assertNotDispatched(PrintJob::class);
    }

    public function test_auto_print_disabled_does_not_dispatch(): void
    {
        config(['services.mercado_livre.auto_print' => false]);
        Bus::fake();
        $a = $this->makeCompany('A');

        app(OrderIngestionService::class)->ingest($a->id, $this->payload());

        Bus::assertNotDispatched(PrintJob::class);
    }

    // ---- Conteúdo da etiqueta (real vs fallback) ----

    private function makeOrder(int $companyId, ?string $mlOrderId = null): int
    {
        return Order::withoutCompanyScope()->create([
            'company_id' => $companyId,
            'ml_order_id' => $mlOrderId ?? 'ORD-' . uniqid(),
            'status' => 'paid',
            'payload' => [],
        ])->id;
    }

    private function integrate(Company $c): void
    {
        CompanyIntegration::create([
            'company_id' => $c->id, 'integration_type' => 'mercado_livre', 'active' => true,
            'ml_user_id' => '777',
            'credentials' => ['access_token' => 'valid-token', 'user_id' => '777'],
            'expires_at' => now()->addHour(),
        ]);
    }

    public function test_prints_real_ml_label_when_available(): void
    {
        config(['services.mercado_livre.label_mode' => 'auto']);
        $a = $this->makeCompany('A');
        $this->integrate($a);
        Http::fake([
            'api.mercadolibre.com/shipment_labels*' => Http::response('^XA-REAL-LABEL^XZ', 200),
        ]);
        $orderId = $this->makeOrder($a->id);

        (new PrintJob(orderId: $orderId, companyId: $a->id, shipmentId: 'SHP-1'))
            ->handle(app(MercadoLivreService::class));

        $row = DB::table('print_jobs')->where('order_id', $orderId)->first();
        $this->assertNotNull($row);
        $this->assertSame($a->id, $row->company_id);
        $this->assertStringContainsString('REAL-LABEL', $row->payload_raw);
    }

    public function test_falls_back_to_simple_label_when_real_unavailable(): void
    {
        config(['services.mercado_livre.label_mode' => 'auto']);
        $a = $this->makeCompany('A');
        $this->integrate($a);
        Http::fake([
            'api.mercadolibre.com/shipment_labels*' => Http::response('not found', 404),
        ]);
        $orderId = $this->makeOrder($a->id);

        (new PrintJob(orderId: $orderId, companyId: $a->id, shipmentId: 'SHP-1'))
            ->handle(app(MercadoLivreService::class));

        $row = DB::table('print_jobs')->where('order_id', $orderId)->first();
        $this->assertStringContainsString('Pedido ' . $orderId, $row->payload_raw);
    }

    public function test_simple_mode_never_calls_ml(): void
    {
        config(['services.mercado_livre.label_mode' => 'simple']);
        $a = $this->makeCompany('A');
        $this->integrate($a);
        Http::fake();
        $orderId = $this->makeOrder($a->id);

        (new PrintJob(orderId: $orderId, companyId: $a->id, shipmentId: 'SHP-1'))
            ->handle(app(MercadoLivreService::class));

        $row = DB::table('print_jobs')->where('order_id', $orderId)->first();
        $this->assertStringContainsString('Pedido ' . $orderId, $row->payload_raw);
        Http::assertNothingSent();
    }

    // ---- Escopo do agente de impressão ----

    private function queueJob(int $companyId): int
    {
        $orderId = $this->makeOrder($companyId);
        DB::table('print_jobs')->insert([
            'company_id' => $companyId, 'order_id' => $orderId, 'type' => 'label',
            'driver' => 'zpl', 'payload_raw' => 'z', 'status' => 'queued',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $orderId;
    }

    public function test_company_token_only_gets_own_jobs(): void
    {
        $a = Company::create(['name' => 'A', 'print_agent_token' => 'tok-A']);
        $b = Company::create(['name' => 'B', 'print_agent_token' => 'tok-B']);
        $this->queueJob($b->id);        // job da B primeiro (id menor)
        $ownId = $this->queueJob($a->id);

        $res = $this->getJson('/api/print/next?token=tok-A');

        $res->assertStatus(200);
        $this->assertSame($ownId, $res->json('job.order_id'));
        $this->assertSame($a->id, $res->json('job.company_id'));
    }

    public function test_master_token_sees_any_company(): void
    {
        config(['printagent.token' => 'MASTER']);
        $b = Company::create(['name' => 'B', 'print_agent_token' => 'tok-B']);
        $bId = $this->queueJob($b->id);

        $res = $this->getJson('/api/print/next?token=MASTER');

        $res->assertStatus(200);
        $this->assertSame($bId, $res->json('job.order_id'));
    }

    public function test_bad_token_is_unauthorized(): void
    {
        $this->getJson('/api/print/next?token=nope')->assertStatus(401);
    }
}
