<?php
require_once '../csv_helper.php';

header('Content-Type: application/json');

$json = file_get_contents('swagger.json');
$spec = json_decode($json, true);

$base_url = get_setting('base_url', '');

if (!empty($base_url)) {
    $spec['servers'] = [
        ['url' => rtrim($base_url, '/')]
    ];
}

echo json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
