<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class HandleMLWebhook implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $tries = 3;
  public $backoff = [30,120,300];

  public function __construct(public array $payload) {}

  public function handle(): void {
    // TODO: parse payload real do ML
    $orderId = DB::table('orders')->insertGetId([
      'ml_order_id' => $this->payload['id'] ?? ('DEMO-'.time()),
      'status'      => 'ready_to_print',
      'payload'     => json_encode($this->payload),
      'label_url'   => null,
      'created_at'  => now(),
      'updated_at'  => now(),
    ]);

    PrintJob::dispatch($orderId, 'label');
  }
}
