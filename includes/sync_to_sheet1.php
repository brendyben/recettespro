<?php
require_once __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

function syncTicketsToGoogleSheet($bufferPath, $spreadsheetId, $sheetName, $jsonKeyPath)
{
    $client = new Client();
    $client->setApplicationName('TicketsKin Sync');
    $client->setScopes([Sheets::SPREADSHEETS]);
    $client->setAuthConfig($jsonKeyPath);
    $client->setAccessType('offline');

    $service = new Sheets($client);

    // Vérifier l'existence du buffer
    if (!file_exists($bufferPath)) {
        return ['success' => false, 'message' => 'Buffer not found.'];
    }

    $rows = array_map('str_getcsv', file($bufferPath));
    if (count($rows) === 0) {
        return ['success' => true, 'message' => 'Aucun ticket à synchroniser.'];
    }

    $dataToSend = [];

    foreach ($rows as $row) {
        if (count($row) < 5) continue;
        list($date, $agent, $commune, $montant, $ticket) = $row;

        $dataToSend[] = [
            $ticket,          // Numéro du Ticket
            '',               // Marché (placeholder)
            $commune,
            $montant,
            $date,
            date('H:i:s'),
            $agent,
            ''                // ID Vendeur (placeholder)
        ];
    }

    $body = new ValueRange([
        'values' => $dataToSend
    ]);

    try {
        $range = "$sheetName!A2";
        $response = $service->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );

        // Vider le buffer
        file_put_contents($bufferPath, '');

        return ['success' => true, 'message' => count($dataToSend) . " tickets synchronisés avec succès."];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
    }
}

function syncBufferToGoogleSheet($csvPath, $jsonKeyPath, $spreadsheetId) {
    return syncTicketsToGoogleSheet($csvPath, $spreadsheetId, 'Sheet1', $jsonKeyPath);
}
