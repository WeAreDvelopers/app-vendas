<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "=== Testando Products com Integrações ===\n\n";

$products = Product::with('integrations')->get();

echo "Total de produtos: " . $products->count() . "\n\n";

foreach($products as $p) {
    echo "Produto #{$p->id}: {$p->name}\n";
    echo "  Integrações: " . $p->integrations->count() . "\n";

    foreach($p->integrations as $integration) {
        $info = $integration->getPlatformInfo();
        echo "    - {$info['icon']} {$info['name']} (Status: {$integration->status})\n";
    }
    echo "\n";
}
