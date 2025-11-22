<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\MercadoLivreService;

echo "=== TESTE DE ESTRUTURA DE ATRIBUTOS ===\n\n";

$mlService = new MercadoLivreService();

// Testa com categoria de toalhas
$categoryId = 'MLB271709';
echo "Categoria de teste: {$categoryId} (Toalhas)\n\n";

echo "=== Buscando atributos da categoria ===\n";
$categoryAttrs = $mlService->getCategoryAttributes($categoryId);

echo "Tipo retornado: " . gettype($categoryAttrs) . "\n";

if (is_array($categoryAttrs)) {
    echo "Chaves do array: " . implode(', ', array_keys($categoryAttrs)) . "\n\n";

    echo "=== REQUIRED ===\n";
    if (isset($categoryAttrs['required']) && is_array($categoryAttrs['required'])) {
        echo "Total: " . count($categoryAttrs['required']) . "\n";
        foreach ($categoryAttrs['required'] as $attr) {
            if (isset($attr['name'])) {
                echo "✅ {$attr['id']}: {$attr['name']}\n";
            } else {
                echo "❌ {$attr['id']}: SEM CAMPO 'name'\n";
            }
        }
    } else {
        echo "Não é array ou não existe\n";
    }

    echo "\n=== OPTIONAL ===\n";
    if (isset($categoryAttrs['optional']) && is_array($categoryAttrs['optional'])) {
        echo "Total: " . count($categoryAttrs['optional']) . "\n";
        foreach ($categoryAttrs['optional'] as $attr) {
            if (isset($attr['name'])) {
                echo "✅ {$attr['id']}: {$attr['name']}\n";
            } else {
                echo "❌ {$attr['id']}: SEM CAMPO 'name'\n";
            }
        }
    } else {
        echo "Não é array ou não existe\n";
    }

    echo "\n=== AUTO_FILLED ===\n";
    if (isset($categoryAttrs['auto_filled']) && is_array($categoryAttrs['auto_filled'])) {
        echo "Total: " . count($categoryAttrs['auto_filled']) . "\n";
        foreach ($categoryAttrs['auto_filled'] as $attr) {
            if (isset($attr['name'])) {
                echo "✅ {$attr['id']}: {$attr['name']}\n";
            } else {
                echo "❌ {$attr['id']}: SEM CAMPO 'name'\n";
            }
        }
    } else {
        echo "Não é array ou não existe\n";
    }

    echo "\n=== SIMULANDO O QUE A VIEW FAZ ===\n";
    echo "Mesclando required e optional:\n";
    $merged = array_merge($categoryAttrs['required'] ?? [], $categoryAttrs['optional'] ?? []);
    echo "Total após merge: " . count($merged) . "\n";

    $hasError = false;
    foreach ($merged as $attr) {
        if (!isset($attr['name'])) {
            echo "❌ ERRO: Atributo sem 'name': " . json_encode($attr) . "\n";
            $hasError = true;
        }
    }

    if (!$hasError) {
        echo "✅ Todos os atributos têm o campo 'name'\n";
    }

    echo "\n=== RESULTADO ===\n";
    if (!$hasError) {
        echo "🎉 SUCESSO! A estrutura está correta.\n";
        echo "   O erro 'Undefined array key \"name\"' está CORRIGIDO!\n";
    } else {
        echo "❌ Ainda há atributos sem o campo 'name'\n";
    }
} else {
    echo "❌ Não retornou um array!\n";
}
