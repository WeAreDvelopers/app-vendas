<?php
namespace App\Jobs;

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

  public function __construct(public int $orderId, public string $type = 'label') {}

  public function handle(): void {
    DB::table('print_jobs')->insert([
      'order_id' => $this->orderId,
      'type' => $this->type,
      'driver' => 'zpl',
      'payload_raw' => '^XA^FO50,50^ADN,36,20^FDPedido '.$this->orderId.'^FS^XZ',
      'status' => 'queued',
      'created_at'=>now(),'updated_at'=>now()
    ]);
  }
}
