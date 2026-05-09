<?php

// Génère un numéro de ticket unique basé sur la date et la commune
function generateTicketNumber($commune) {
    $date = date('Ymd');
    $prefix = strtoupper($commune);
    $indexFile = "../data/ticket_index.csv";
    $index = 1;
    $indexes = [];

    // Lire les index existants
    if (file_exists($indexFile)) {
        $lines = file($indexFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            list($savedDate, $savedCommune, $savedIndex) = explode(',', $line);
            if ($savedDate === $date && $savedCommune === $prefix) {
                $index = max($index, intval($savedIndex) + 1);
            }
            $indexes[] = [$savedDate, $savedCommune, $savedIndex];
        }
    }

    // Ajouter le nouvel index du jour
    $indexes[] = [$date, $prefix, $index];

    // Réécriture complète
    $lines = array_map(fn($arr) => implode(',', $arr), $indexes);
    file_put_contents($indexFile, implode("\n", $lines));

    return "$date-$prefix-" . str_pad($index, 3, '0', STR_PAD_LEFT);
}

// Enregistre une ligne de données dans le buffer (fichier CSV)
function saveToBuffer($data) {
    $filename = "../data/buffer.csv";
    $handle = fopen($filename, 'a');
    if ($handle !== false) {
        fputcsv($handle, $data);
        fclose($handle);
        return true;
    }
    return false;
}

// Charge toutes les lignes du buffer (pour statistiques par exemple)
function loadBuffer() {
    $filename = "../data/buffer.csv";
    $data = [];

    if (file_exists($filename)) {
        $rows = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($rows as $row) {
            $data[] = str_getcsv($row);
        }
    }

    return $data;
}
