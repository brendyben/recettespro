<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/sync_to_sheet.php';

$result = syncTicketsToGoogleSheet(
    realpath(__DIR__ . '/../data/buffer.csv'),
    '1aUP3-D7L57AzSp_XGedWdlqBXhhgXPTBwkjP9Afbf4g',
    'Sheet1',  // ✅ Nom réel de ta feuille
    realpath(__DIR__ . '/client_secret.json')
);

echo $result['success'] ? "✅ SYNC OK: {$result['message']}" : "❌ ERREUR: {$result['message']}";
