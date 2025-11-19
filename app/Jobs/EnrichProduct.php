<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnrichProduct implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $tries = 3;
  public $backoff = [60, 180, 600];

  public function __construct(public int $importId, public string $sku) {}

  public function handle(): void {
    // TODO: normalizar -> products, gerar título/descrição, baixar imagens, etc.
    PublishListingToML::dispatch($this->sku);
  }
}
