<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Debug de Atributos da Categoria ===\n\n";

$categoryId = 'MLB271709';
$response = Http::timeout(10)->get("https://api.mercadolibre.com/categories/{$categoryId}/attributes");

if ($response->successful()) {
    $attributes = $response->json();

    echo "Total de atributos: " . count($attributes) . "\n\n";

    // Mostra primeiros 10 atributos com suas tags
    foreach (array_slice($attributes, 0, 10) as $attr) {
        echo "=== {$attr['id']} - {$attr['name']} ===\n";
        echo "Tipo: {$attr['value_type']}\n";

        $tags = $attr['tags'] ?? [];
        echo "Tags (estrutura): " . json_encode($tags) . "\n";

        // Extrai IDs das tags
        $tagIds = array_column($tags, 'id');
        echo "Tag IDs: " . implode(', ', $tagIds) . "\n";

        if (!empty($attr['hint'])) {
            echo "Hint: {$attr['hint']}\n";
        }

        $isRequired = in_array('required', $tagIds) || in_array('catalog_required', $tagIds);
        $isReadOnly = in_array('read_only', $tagIds);
        $isHidden = in_array('hidden', $tagIds);

        echo "Obrigatório: " . ($isRequired ? 'SIM' : 'NÃO') . "\n";
        echo "Read-only: " . ($isReadOnly ? 'SIM' : 'NÃO') . "\n";
        echo "Hidden: " . ($isHidden ? 'SIM' : 'NÃO') . "\n";
        echo "\n";
    }
}
