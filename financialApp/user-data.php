<?php
header('Content-Type: application/json');

// Percorso del file JSON
$filePath = __DIR__ . '/data.json';

// Controlla se il file esiste
if (! file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(["error" => "File not found"]);
    exit;
}

// Leggi e decodifica il JSON
$json = file_get_contents($filePath);
$data = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(["error" => "Error decoding JSON"]);
    exit;
}

// Restituisci i dati
echo json_encode($data);
?>