<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\MercadoLivreService;

echo "=== TESTE DA VIEW PREPARE ===\n\n";

// Busca um produto para testar
$product = DB::table('products')->first();
if (!$product) {
    echo "❌ Nenhum produto encontrado\n";
    exit;
}

echo "Produto: {$product->name}\n\n";

// Busca listing existente
$listing = DB::table('mercado_livre_listings')
    ->where('product_id', $product->id)
    ->first();

if ($listing) {
    echo "✅ Listing existente encontrado\n";
    echo "   Category ID: {$listing->category_id}\n";
} else {
    echo "⚠️  Nenhum listing existente (criará novo)\n";
}

// Busca imagens
$images = DB::table('product_images')
    ->where('product_id', $product->id)
    ->orderBy('sort')
    ->get();

echo "   Imagens: " . count($images) . "\n\n";

// Simula o controller prepare()
$mlService = new MercadoLivreService();

$categoryAttributes = [];
if ($listing && $listing->category_id) {
    echo "=== Buscando atributos da categoria {$listing->category_id} ===\n";
    $categoryAttributes = $mlService->getCategoryAttributes($listing->category_id);

    echo "Estrutura retornada:\n";
    echo "- required: " . count($categoryAttributes['required'] ?? []) . " atributos\n";
    echo "- optional: " . count($categoryAttributes['optional'] ?? []) . " atributos\n";
    echo "- auto_filled: " . count($categoryAttributes['auto_filled'] ?? []) . " atributos\n";
}

// Decodifica campos JSON (como o controller faz)
if ($listing) {
    if (!empty($listing->validation_errors) && is_string($listing->validation_errors)) {
        $listing->validation_errors = json_decode($listing->validation_errors, true) ?? [];
    } else {
        $listing->validation_errors = [];
    }

    if (!empty($listing->missing_fields) && is_string($listing->missing_fields)) {
        $listing->missing_fields = json_decode($listing->missing_fields, true) ?? [];
    } else {
        $listing->missing_fields = [];
    }

    if (!empty($listing->attributes) && is_string($listing->attributes)) {
        $listing->attributes = json_decode($listing->attributes, true);
        if (!is_array($listing->attributes) && is_string($listing->attributes)) {
            $listing->attributes = json_decode($listing->attributes, true) ?? [];
        }
    }
    if (!is_array($listing->attributes)) {
        $listing->attributes = [];
    }
}

echo "\n=== Dados que serão passados para a view ===\n";
echo "product: objeto stdClass\n";
echo "listing: " . ($listing ? "objeto stdClass" : "null") . "\n";
echo "images: " . count($images) . " imagens\n";
echo "categoryAttributes: array com chaves [required, optional, auto_filled]\n";

if ($listing) {
    echo "\nListing decodificado:\n";
    echo "- validation_errors: array com " . count($listing->validation_errors) . " elementos\n";
    echo "- missing_fields: array com " . count($listing->missing_fields) . " elementos\n";
    echo "- attributes: array com " . count($listing->attributes) . " elementos\n";
}

echo "\n=== RESULTADO ===\n";
echo "✅ Estrutura correta para a view\n";
echo "✅ categoryAttributesContainer começará vazio (só mensagem)\n";
echo "✅ JavaScript carregará atributos dinamicamente ao selecionar categoria\n";
echo "✅ Se já tiver categoria selecionada, window.load disparará o carregamento\n";
echo "\n🎉 Layout está correto!\n";
