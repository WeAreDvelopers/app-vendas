<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\MercadoLivreService;

echo "=== TESTE COMPLETO DO WORKFLOW ===\n\n";

// 1. Busca um listing existente
$listing = DB::table('mercado_livre_listings')
    ->where('category_id', 'MLB271709')
    ->first();

if (!$listing) {
    echo "❌ Nenhum listing encontrado\n";
    exit;
}

$product = DB::table('products')->find($listing->product_id);

echo "📦 Produto: {$product->name}\n";
echo "🏷️  Categoria: {$listing->category_id}\n";
echo "💰 Preço: R$ {$listing->price}\n\n";

// 2. Verifica atributos salvos no banco
echo "=== 1. ATRIBUTOS SALVOS NO BANCO ===\n";
$savedAttrs = json_decode($listing->attributes, true);
if (!is_array($savedAttrs) && is_string($savedAttrs)) {
    $savedAttrs = json_decode($savedAttrs, true);
}

if (is_array($savedAttrs) && count($savedAttrs) > 0) {
    echo "✅ Atributos encontrados no banco: " . count($savedAttrs) . "\n";
    foreach ($savedAttrs as $attr) {
        $valueStr = isset($attr['value_id']) ? "[{$attr['value_id']}] {$attr['value_name']}" : $attr['value_name'];
        echo "   - {$attr['id']}: {$valueStr}\n";
    }
} else {
    echo "⚠️  Nenhum atributo salvo\n";
}

// 3. Simula o que o controller publish() faz
echo "\n=== 2. PREPARANDO PAYLOAD (como controller publish) ===\n";

$images = DB::table('product_images')
    ->where('product_id', $product->id)
    ->orderBy('sort')
    ->get();

$mlService = new MercadoLivreService();
$listingData = (array) $listing;
$payload = $mlService->prepareListingPayload($product, $listingData, $images);

echo "✅ Payload preparado\n";
echo "   - Título: {$payload['title']}\n";
echo "   - Preço: {$payload['price']}\n";
echo "   - Quantidade: {$payload['available_quantity']}\n";
echo "   - Total de atributos no payload: " . count($payload['attributes']) . "\n\n";

// 4. Lista todos os atributos no payload
echo "=== 3. ATRIBUTOS NO PAYLOAD FINAL ===\n";
foreach ($payload['attributes'] as $attr) {
    $valueStr = isset($attr['value_id']) ? "[{$attr['value_id']}] {$attr['value_name']}" : $attr['value_name'];
    echo "   ✓ {$attr['id']}: {$valueStr}\n";
}

// 5. Verifica atributos obrigatórios
echo "\n=== 4. VERIFICAÇÃO DE ATRIBUTOS OBRIGATÓRIOS ===\n";
$categoryAttrs = $mlService->getCategoryAttributes($listing->category_id);

if (!empty($categoryAttrs['required'])) {
    $currentAttrIds = array_column($payload['attributes'], 'id');
    $missingRequired = [];

    foreach ($categoryAttrs['required'] as $requiredAttr) {
        if (!in_array($requiredAttr['id'], $currentAttrIds)) {
            $missingRequired[] = $requiredAttr['name'] . ' (' . $requiredAttr['id'] . ')';
        }
    }

    if (empty($missingRequired)) {
        echo "✅ Todos os atributos obrigatórios estão presentes!\n";
        echo "   Total de obrigatórios: " . count($categoryAttrs['required']) . "\n";
        foreach ($categoryAttrs['required'] as $req) {
            echo "   ✓ {$req['name']} ({$req['id']})\n";
        }
    } else {
        echo "❌ FALTANDO atributos obrigatórios:\n";
        foreach ($missingRequired as $missing) {
            echo "   ✗ {$missing}\n";
        }
    }
} else {
    echo "⚠️  Nenhum atributo obrigatório encontrado para esta categoria\n";
}

// 6. Validação final
echo "\n=== 5. VALIDAÇÃO FINAL ===\n";
$productForValidation = clone $product;
$productForValidation->price = $listing->price ?? $product->price;
$productForValidation->name = $listing->title ?? $product->name;
$productForValidation->stock = $listing->available_quantity ?? $product->stock;
$productForValidation->description = $listing->plain_text_description ?? $product->description;

$validation = $mlService->validateProduct($productForValidation, $images);

echo "Score de qualidade: {$validation['percentage']}%\n";
echo "Pode publicar: " . ($validation['can_publish'] ? '✅ SIM' : '❌ NÃO') . "\n";

if (!empty($validation['errors'])) {
    echo "Erros:\n";
    foreach ($validation['errors'] as $error) {
        echo "   ❌ {$error}\n";
    }
}

if (!empty($validation['missing_fields'])) {
    echo "Campos faltando:\n";
    foreach ($validation['missing_fields'] as $field) {
        echo "   ⚠️  {$field}\n";
    }
}

echo "\n=== RESUMO ===\n";
echo "Atributos no banco: " . (is_array($savedAttrs) ? count($savedAttrs) : 0) . "\n";
echo "Atributos no payload: " . count($payload['attributes']) . "\n";
echo "Pronto para publicar: " . ($validation['can_publish'] ? '✅ SIM' : '❌ NÃO') . "\n";

if ($validation['can_publish'] && empty($missingRequired)) {
    echo "\n🎉 SUCESSO! O workflow está funcionando corretamente!\n";
    echo "   - Dados salvos no banco ✅\n";
    echo "   - Atributos incluídos no payload ✅\n";
    echo "   - Atributos obrigatórios presentes ✅\n";
    echo "   - Validação aprovada ✅\n";
} else {
    echo "\n⚠️  ATENÇÃO: Ainda há problemas a corrigir\n";
}
