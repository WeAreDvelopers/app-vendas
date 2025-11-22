<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Teste de Formato de Atributos ML ===\n\n";

$categoryId = 'MLB271709'; // Toalhas

// Busca os atributos da categoria
$response = Http::timeout(10)->get("https://api.mercadolibre.com/categories/{$categoryId}/attributes");

if ($response->successful()) {
    $attributes = $response->json();

    // Procura TOWEL_TYPE
    foreach ($attributes as $attr) {
        if ($attr['id'] === 'TOWEL_TYPE') {
            echo "=== Atributo: TOWEL_TYPE ===\n\n";
            echo "Nome: {$attr['name']}\n";
            echo "Tipo: {$attr['value_type']}\n";
            echo "\nValores possíveis:\n";

            foreach ($attr['values'] as $value) {
                echo "\nValor #{$value['id']}:\n";
                echo "  ID: {$value['id']}\n";
                echo "  Nome: {$value['name']}\n";

                if (isset($value['struct'])) {
                    echo "  Struct: " . json_encode($value['struct']) . "\n";
                }
            }

            echo "\n\n=== Documentação da API ===\n";
            echo "Para enviar este atributo, você deve usar:\n\n";

            if ($attr['value_type'] === 'list') {
                echo "Opção 1 - Enviar apenas o ID:\n";
                echo json_encode([
                    'id' => 'TOWEL_TYPE',
                    'value_id' => '53803221' // ID do valor
                ], JSON_PRETTY_PRINT) . "\n\n";

                echo "Opção 2 - Enviar apenas o nome:\n";
                echo json_encode([
                    'id' => 'TOWEL_TYPE',
                    'value_name' => 'Toalha de banho' // Nome do valor
                ], JSON_PRETTY_PRINT) . "\n\n";

                echo "Opção 3 - Enviar ambos (recomendado):\n";
                echo json_encode([
                    'id' => 'TOWEL_TYPE',
                    'value_id' => '53803221',
                    'value_name' => 'Toalha de banho'
                ], JSON_PRETTY_PRINT) . "\n";
            }

            break;
        }
    }
}
