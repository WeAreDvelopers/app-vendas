<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\MercadoLivreService;

echo "=== Teste de Atributos de Categoria ===\n\n";

$mlService = new MercadoLivreService();

// Testa categoria MLB271709 (Toalhas - que estava dando erro)
$categoryId = 'MLB271709';
echo "Categoria: {$categoryId}\n\n";

$attributes = $mlService->getCategoryAttributes($categoryId);

echo "=== ATRIBUTOS OBRIGATÓRIOS ===\n";
if (!empty($attributes['required'])) {
    foreach ($attributes['required'] as $attr) {
        echo "✓ {$attr['id']} - {$attr['name']}\n";
        echo "  Tipo: {$attr['value_type']}\n";

        if (!empty($attr['values'])) {
            echo "  Valores possíveis: " . count($attr['values']) . "\n";
            // Mostra primeiros 3 valores
            foreach (array_slice($attr['values'], 0, 3) as $value) {
                echo "    - {$value['name']}\n";
            }
            if (count($attr['values']) > 3) {
                echo "    ... e mais " . (count($attr['values']) - 3) . " opções\n";
            }
        }

        if (!empty($attr['hint'])) {
            echo "  Dica: {$attr['hint']}\n";
        }

        echo "\n";
    }
} else {
    echo "Nenhum atributo obrigatório encontrado.\n\n";
}

echo "=== ATRIBUTOS OPCIONAIS (primeiros 5) ===\n";
if (!empty($attributes['optional'])) {
    foreach (array_slice($attributes['optional'], 0, 5) as $attr) {
        echo "• {$attr['id']} - {$attr['name']}\n";
    }
    echo "\nTotal de opcionais: " . count($attributes['optional']) . "\n";
} else {
    echo "Nenhum atributo opcional encontrado.\n";
}
