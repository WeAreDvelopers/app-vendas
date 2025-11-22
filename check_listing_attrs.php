<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Verificando Listings ===\n\n";

$listings = DB::table('mercado_livre_listings')->get();

foreach ($listings as $listing) {
    echo "Listing ID: {$listing->id}\n";
    echo "Product ID: {$listing->product_id}\n";
    echo "Category: {$listing->category_id}\n";
    echo "Attributes: {$listing->attributes}\n";

    // Tenta decodificar (pode estar com JSON duplo)
    $attrs = json_decode($listing->attributes, true);

    // Se não for array, tenta decodificar novamente
    if (!is_array($attrs) && is_string($attrs)) {
        $attrs = json_decode($attrs, true);
    }

    if (is_array($attrs) && count($attrs) > 0) {
        echo "Atributos decodificados (" . count($attrs) . "):\n";
        foreach ($attrs as $attr) {
            if (isset($attr['id']) && isset($attr['value_name'])) {
                echo "  - {$attr['id']}: {$attr['value_name']}\n";
            }
        }
    } else {
        echo "Nenhum atributo ou formato inválido\n";
    }

    echo "\n---\n\n";
}
