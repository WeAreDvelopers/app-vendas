<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== STATUS DOS LISTINGS ===\n\n";

$listings = DB::table('mercado_livre_listings')
    ->get(['id', 'product_id', 'status', 'ml_id', 'updated_at']);

foreach ($listings as $listing) {
    $emoji = match($listing->status) {
        'queued' => '🕒',
        'processing' => '🔄',
        'active' => '✅',
        'failed' => '❌',
        'draft' => '📝',
        'paused' => '⏸️',
        default => '⚪'
    };

    echo "$emoji Listing ID: {$listing->id} | Product: {$listing->product_id} | Status: {$listing->status} | ML ID: " . ($listing->ml_id ?? 'N/A') . " | Updated: {$listing->updated_at}\n";
}

echo "\n=== CONTAGEM POR STATUS ===\n\n";
$counts = DB::table('mercado_livre_listings')
    ->select('status', DB::raw('count(*) as total'))
    ->groupBy('status')
    ->get();

foreach ($counts as $count) {
    echo "{$count->status}: {$count->total}\n";
}

echo "\n";
