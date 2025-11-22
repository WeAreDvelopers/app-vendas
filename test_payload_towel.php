<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\MercadoLivreService;

echo "=== Teste de Payload - Categoria Toalhas ===\n\n";

// Busca o listing com categoria de toalhas
$listing = DB::table('mercado_livre_listings')
    ->where('category_id', 'MLB271709')
    ->first();

if (!$listing) {
    echo "❌ Nenhum listing encontrado com categoria MLB271709\n";
    exit;
}

$product = DB::table('products')->find($listing->product_id);

echo "Produto: {$product->name}\n";
echo "Categoria: {$listing->category_id}\n\n";

echo "=== Atributos Salvos no Listing ===\n";
echo "Raw: {$listing->attributes}\n\n";

// Decodifica
$savedAttrs = json_decode($listing->attributes, true);
if (!is_array($savedAttrs) && is_string($savedAttrs)) {
    $savedAttrs = json_decode($savedAttrs, true);
}

if (is_array($savedAttrs)) {
    echo "Atributos decodificados:\n";
    foreach ($savedAttrs as $attr) {
        $hasId = isset($attr['value_id']) ? "[ID: {$attr['value_id']}]" : '';
        $hasName = isset($attr['value_name']) ? "{$attr['value_name']}" : '';
        echo "  - {$attr['id']}: {$hasId} {$hasName}\n";
    }
} else {
    echo "❌ Não é um array válido\n";
}

echo "\n=== Buscando Imagens ===\n";
$images = DB::table('product_images')
    ->where('product_id', $product->id)
    ->orderBy('sort')
    ->get();
echo "Imagens: " . count($images) . "\n\n";

echo "=== Preparando Payload ===\n";
$mlService = new MercadoLivreService();
$listingData = (array) $listing;
$payload = $mlService->prepareListingPayload($product, $listingData, $images);

echo "\n=== ATRIBUTOS NO PAYLOAD ===\n";
foreach ($payload['attributes'] as $attr) {
    $hasId = isset($attr['value_id']) ? "[ID: {$attr['value_id']}]" : '';
    $hasName = isset($attr['value_name']) ? "{$attr['value_name']}" : '';
    echo "  ✓ {$attr['id']}: {$hasId} {$hasName}\n";
}

echo "\n=== Verificando TOWEL_TYPE ===\n";
$hasTowelType = false;
foreach ($payload['attributes'] as $attr) {
    if ($attr['id'] === 'TOWEL_TYPE') {
        $hasTowelType = true;
        echo "✅ TOWEL_TYPE encontrado!\n";
        echo "   value_id: " . ($attr['value_id'] ?? 'N/A') . "\n";
        echo "   value_name: " . ($attr['value_name'] ?? 'N/A') . "\n";
        break;
    }
}

if (!$hasTowelType) {
    echo "❌ TOWEL_TYPE NÃO está no payload!\n";
    echo "\n=== Debug: Verificando por que não foi incluído ===\n";

    // Verifica se estava nos atributos salvos
    $inSaved = false;
    if (is_array($savedAttrs)) {
        foreach ($savedAttrs as $attr) {
            if ($attr['id'] === 'TOWEL_TYPE') {
                $inSaved = true;
                echo "- Estava nos atributos salvos: SIM\n";
                echo "  Conteúdo: " . json_encode($attr) . "\n";

                // Verifica a condição
                $hasValidValue = isset($attr['value_name']) || isset($attr['value_id']);
                echo "  Tem value_name ou value_id? " . ($hasValidValue ? 'SIM' : 'NÃO') . "\n";
                break;
            }
        }
    }

    if (!$inSaved) {
        echo "- Estava nos atributos salvos: NÃO\n";
    }
}
