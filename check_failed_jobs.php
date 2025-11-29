<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== JOBS FALHADOS ===\n\n";

$failedJobs = DB::table('failed_jobs')
    ->orderBy('failed_at', 'desc')
    ->get();

if ($failedJobs->isEmpty()) {
    echo "✅ Nenhum job falhado encontrado!\n";
} else {
    foreach ($failedJobs as $job) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "ID: {$job->id}\n";
        echo "UUID: {$job->uuid}\n";
        echo "Connection: {$job->connection}\n";
        echo "Queue: {$job->queue}\n";
        echo "Failed at: {$job->failed_at}\n\n";

        // Decodifica payload para ver qual job falhou
        $payload = json_decode($job->payload, true);
        if ($payload) {
            echo "Job Class: " . ($payload['displayName'] ?? 'Unknown') . "\n";
            if (isset($payload['data']['command'])) {
                // Tenta extrair informações do comando
                $command = unserialize($payload['data']['command']);
                if ($command && method_exists($command, '__toString')) {
                    echo "Details: " . $command . "\n";
                } elseif (is_object($command)) {
                    echo "Command: " . get_class($command) . "\n";
                    // Tenta exibir propriedades públicas
                    $props = get_object_vars($command);
                    if (!empty($props)) {
                        echo "Properties:\n";
                        foreach ($props as $key => $value) {
                            if (!is_object($value) && !is_array($value)) {
                                echo "  - $key: $value\n";
                            }
                        }
                    }
                }
            }
        }

        echo "\n❌ Exception:\n";
        // Mostra primeiras 500 caracteres do erro
        $exception = substr($job->exception, 0, 800);
        $lines = explode("\n", $exception);
        foreach (array_slice($lines, 0, 10) as $line) {
            echo "   " . $line . "\n";
        }
        echo "\n";
    }
}

echo "\n=== ESTATÍSTICAS ===\n";
echo "Total de jobs falhados: " . $failedJobs->count() . "\n\n";
