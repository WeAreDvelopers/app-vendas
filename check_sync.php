<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Verificando sincronização ===\n\n";

$listings = DB::table('mercado_livre_listings')->get();
echo "ML Listings:\n";
foreach($listings as $l) {
    echo "  Product ID: {$l->product_id}, Status: {$l->status}\n";
}

echo "\nProduct Integrations:\n";
$integrations = DB::table('product_integrations')->get();
foreach($integrations as $i) {
    echo "  Product ID: {$i->product_id}, Platform: {$i->platform}, Status: {$i->status}\n";
}

echo "\nProducts (all):\n";
$products = DB::table('products')->select('id', 'name')->get();
foreach($products as $p) {
    echo "  ID: {$p->id}, Name: {$p->name}\n";
}
