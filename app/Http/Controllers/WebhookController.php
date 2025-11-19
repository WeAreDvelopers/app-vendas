<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\HandleMLWebhook;

class WebhookController extends Controller {
  public function meli(Request $r) {
    HandleMLWebhook::dispatch($r->all());
    return response()->json(['ok'=>true]);
  }
}
