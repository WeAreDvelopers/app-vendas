<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Verificando dados ===\n\n";

// ML Listings
$listings = DB::table('mercado_livre_listings')->get();
echo "Total ML Listings: " . $listings->count() . "\n";
foreach($listings as $l) {
    echo sprintf(
        "  ID: %d, Product: %d, ML_ID: %s, Status: %s\n",
        $l->id,
        $l->product_id,
        $l->ml_id ?? 'NULL',
        $l->status
    );
}

echo "\n";

// Product Integrations
$integrations = DB::table('product_integrations')->get();
echo "Total Integrations: " . $integrations->count() . "\n";
foreach($integrations as $i) {
    echo sprintf(
        "  ID: %d, Product: %d, Platform: %s, External_ID: %s, Status: %s\n",
        $i->id,
        $i->product_id,
        $i->platform,
        $i->external_id ?? 'NULL',
        $i->status
    );
}
