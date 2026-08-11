<?php
namespace App\Jobs;

use App\Services\MercadoLivreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class PrintJob implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $tries = 5;
  public $backoff = [10, 30, 60, 120, 300];

  public function __construct(
    public int $orderId,
    public int $companyId,
    public ?string $shipmentId = null,
    public string $type = 'label',
  ) {}

  public function handle(MercadoLivreService $ml): void {
    $mode = config('services.mercado_livre.label_mode', 'auto');

    $zpl = null;

    // Modo 'auto': tenta a etiqueta real do Mercado Envios pelo shipment.
    if ($mode !== 'simple' && $this->shipmentId) {
      $zpl = $ml->getShipmentLabel($this->companyId, $this->shipmentId);
    }

    // Fallback: etiqueta simples (número do pedido) quando a real não veio.
    if (!$zpl) {
      $zpl = $this->simpleLabel();
    }

    DB::table('print_jobs')->insert([
      'company_id' => $this->companyId,
      'order_id' => $this->orderId,
      'type' => $this->type,
      'driver' => 'zpl',
      'payload_raw' => $zpl,
      'status' => 'queued',
      'created_at' => now(),
      'updated_at' => now(),
    ]);
  }

  private function simpleLabel(): string {
    return '^XA^FO50,50^ADN,36,20^FDPedido '.$this->orderId.'^FS^XZ';
  }
}
