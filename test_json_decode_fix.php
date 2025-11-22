<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TESTE DE DECODIFICAÇÃO JSON ===\n\n";

// Busca um listing para testar
$listing = DB::table('mercado_livre_listings')
    ->whereNotNull('validation_errors')
    ->orWhereNotNull('missing_fields')
    ->orWhereNotNull('attributes')
    ->first();

if (!$listing) {
    echo "⚠️  Nenhum listing encontrado com dados para testar\n";
    exit;
}

echo "Listing ID: {$listing->id}\n";
echo "Product ID: {$listing->product_id}\n\n";

// Testa cada campo JSON
echo "=== 1. VALIDATION_ERRORS ===\n";
echo "Tipo original: " . gettype($listing->validation_errors) . "\n";
if (is_string($listing->validation_errors)) {
    echo "Valor (string): {$listing->validation_errors}\n";
    $decoded = json_decode($listing->validation_errors, true);
    echo "Tipo após decode: " . gettype($decoded) . "\n";
    if (is_array($decoded)) {
        echo "✅ Array com " . count($decoded) . " elementos\n";
    } else {
        echo "❌ Não é array após decode\n";
    }
} else {
    echo "Já é: " . gettype($listing->validation_errors) . "\n";
}

echo "\n=== 2. MISSING_FIELDS ===\n";
echo "Tipo original: " . gettype($listing->missing_fields) . "\n";
if (is_string($listing->missing_fields)) {
    echo "Valor (string): {$listing->missing_fields}\n";
    $decoded = json_decode($listing->missing_fields, true);
    echo "Tipo após decode: " . gettype($decoded) . "\n";
    if (is_array($decoded)) {
        echo "✅ Array com " . count($decoded) . " elementos\n";
    } else {
        echo "❌ Não é array após decode\n";
    }
} else {
    echo "Já é: " . gettype($listing->missing_fields) . "\n";
}

echo "\n=== 3. ATTRIBUTES ===\n";
echo "Tipo original: " . gettype($listing->attributes) . "\n";
if (is_string($listing->attributes)) {
    echo "Tamanho da string: " . strlen($listing->attributes) . " caracteres\n";

    // Primeiro decode
    $decoded = json_decode($listing->attributes, true);
    echo "Tipo após 1º decode: " . gettype($decoded) . "\n";

    // Se ainda for string, tenta segundo decode
    if (is_string($decoded)) {
        echo "⚠️  Ainda é string após 1º decode (JSON duplo)\n";
        $decoded = json_decode($decoded, true);
        echo "Tipo após 2º decode: " . gettype($decoded) . "\n";
    }

    if (is_array($decoded)) {
        echo "✅ Array com " . count($decoded) . " atributos\n";
        foreach ($decoded as $attr) {
            if (isset($attr['id'])) {
                $valueStr = isset($attr['value_id']) ? "[{$attr['value_id']}] {$attr['value_name']}" : ($attr['value_name'] ?? 'N/A');
                echo "   - {$attr['id']}: {$valueStr}\n";
            }
        }
    } else {
        echo "❌ Não é array após decodificação\n";
    }
} else {
    echo "Já é: " . gettype($listing->attributes) . "\n";
}

echo "\n=== SIMULANDO O QUE O CONTROLLER FAZ ===\n";

// Simula a lógica do controller
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

echo "validation_errors após processamento: " . gettype($listing->validation_errors) . " (" . count($listing->validation_errors) . " elementos)\n";
echo "missing_fields após processamento: " . gettype($listing->missing_fields) . " (" . count($listing->missing_fields) . " elementos)\n";
echo "attributes após processamento: " . gettype($listing->attributes) . " (" . count($listing->attributes) . " elementos)\n";

echo "\n=== RESULTADO ===\n";
if (is_array($listing->validation_errors) && is_array($listing->missing_fields) && is_array($listing->attributes)) {
    echo "✅ SUCESSO! Todos os campos estão como arrays\n";
    echo "   O erro 'foreach() argument must be of type array|object, string given' está CORRIGIDO!\n";
} else {
    echo "❌ ERRO: Ainda há campos que não são arrays\n";
}
