<?php

// Simula o que o formulário envia quando você seleciona "Toalha de banho"
// Agora com formato "ID|Nome"

$formData = [
    'ml_attr' => [
        'BRAND' => 'Western', // Campo texto (sem ID)
        'MODEL' => 'CR22', // Campo texto (sem ID)
        'TOWEL_TYPE' => '53803222|Toalha de banho', // ✅ Select (ID|Nome)
        'PATTERN_NAME' => '930483|Lisa', // ✅ Select (ID|Nome)
        'UNITS_PER_PACK' => '3', // Campo numérico (sem ID)
        'MAIN_COLOR' => '46671867|Azul', // ✅ Select (ID|Nome)
    ]
];

echo "=== Dados do Formulário ===\n\n";
print_r($formData);

echo "\n=== Processamento (como no Controller) ===\n\n";

// NOVO processamento (igual ao controller)
$customAttributes = [];
foreach ($formData['ml_attr'] as $attrId => $attrValue) {
    if (!empty($attrValue)) {
        $attribute = ['id' => $attrId];

        // Se o valor contém "|", separa em ID e nome
        if (strpos($attrValue, '|') !== false) {
            [$valueId, $valueName] = explode('|', $attrValue, 2);
            $attribute['value_id'] = $valueId;
            $attribute['value_name'] = $valueName;
        } else {
            // Se não tem "|", usa como value_name apenas
            $attribute['value_name'] = $attrValue;
        }

        $customAttributes[] = $attribute;
    }
}

echo "Atributos processados:\n";
echo json_encode($customAttributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n\n=== JSON que será salvo no banco ===\n";
$jsonToSave = json_encode($customAttributes);
echo $jsonToSave;

echo "\n\n=== Verificação ===\n";
foreach ($customAttributes as $attr) {
    $hasId = isset($attr['value_id']);
    $hasName = isset($attr['value_name']);

    $status = $hasId && $hasName ? '✅ ID + Nome' : ($hasName ? '⚪ Só Nome' : '❌ Inválido');

    if ($hasId && $hasName) {
        echo "{$status} - {$attr['id']}: [{$attr['value_id']}] {$attr['value_name']}\n";
    } else {
        echo "{$status} - {$attr['id']}: {$attr['value_name']}\n";
    }
}
