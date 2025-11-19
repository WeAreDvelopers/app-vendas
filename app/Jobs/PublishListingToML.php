<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class PublishListingToML implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $tries = 5;
  public $backoff = [60, 120, 300, 900, 1800]; // inclui 30min

  public function __construct(public string $sku) {}

  public function handle(): void {
    $hash = hash('sha256', 'publish:'.$this->sku);
    $exists = DB::table('jobs_log')->where('payload_hash',$hash)->exists();
    if ($exists) return;

    // TODO: montar payload e chamar API do Mercado Livre
    DB::table('jobs_log')->insert([
      'type' => 'PublishListingToML',
      'payload_hash' => $hash,
      'status'=>'done',
      'created_at'=>now(),'updated_at'=>now()
    ]);
  }
}
