<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\MercadoLivreService;

echo "=== Teste de Payload do Mercado Livre ===\n\n";

// Busca um produto com listing para testar
$listing = DB::table('mercado_livre_listings')
    ->whereNotNull('product_id')
    ->first();

if (!$listing) {
    echo "Nenhum listing encontrado para teste.\n";
    exit;
}

$product = DB::table('products')->find($listing->product_id);
if (!$product) {
    echo "Produto não encontrado.\n";
    exit;
}

// Busca imagens do produto
$images = DB::table('product_images')
    ->where('product_id', $product->id)
    ->orderBy('sort')
    ->get();

echo "Produto: {$product->name}\n";
echo "SKU: {$product->sku}\n";
echo "Imagens: " . count($images) . "\n\n";

// Cria instância do serviço
$mlService = new MercadoLivreService();

// Prepara payload
$listingData = (array) $listing;
$payload = $mlService->prepareListingPayload($product, $listingData, $images);

echo "=== PAYLOAD GERADO ===\n\n";
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "\n\n";

// Verifica campos importantes
echo "=== VERIFICAÇÃO DE CAMPOS ===\n\n";

$checks = [
    'title' => 'Título',
    'description' => 'Descrição',
    'category_id' => 'Categoria',
    'price' => 'Preço',
    'pictures' => 'Imagens',
    'attributes' => 'Atributos',
    'shipping' => 'Configurações de envio',
    'warranty' => 'Garantia',
];

foreach ($checks as $key => $label) {
    $status = isset($payload[$key]) && !empty($payload[$key]) ? '✓' : '✗';
    echo "{$status} {$label}\n";

    if ($key === 'description' && isset($payload[$key])) {
        $descLength = strlen($payload[$key]['plain_text'] ?? '');
        echo "  → Tamanho: {$descLength} caracteres\n";
    }

    if ($key === 'pictures' && isset($payload[$key])) {
        echo "  → Quantidade: " . count($payload[$key]) . " imagens\n";
    }

    if ($key === 'attributes' && isset($payload[$key])) {
        echo "  → Quantidade: " . count($payload[$key]) . " atributos\n";
        foreach ($payload[$key] as $attr) {
            echo "    - {$attr['id']}: {$attr['value_name']}\n";
        }
    }

    if ($key === 'shipping' && isset($payload[$key])) {
        if (isset($payload[$key]['dimensions'])) {
            echo "  → Dimensões: {$payload[$key]['dimensions']}\n";
        }
        if (isset($payload[$key]['weight'])) {
            echo "  → Peso: {$payload[$key]['weight']}kg\n";
        }
    }
}

echo "\n";
