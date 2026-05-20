<?php
ob_start();
require_once '../csv_helper.php';

header('Content-Type: application/json');

function send_response($data, $status = 200) {
    if (ob_get_length()) ob_end_clean();
    http_response_code($status);
    $json = json_encode($data);
    if ($json === false) {
        echo json_encode(['error' => 'JSON encoding failed: ' . json_last_error_msg()]);
    } else {
        echo $json;
    }
    exit;
}

function get_json_input() {
    return json_decode(file_get_contents('php://input'), true);
}

$method = $_SERVER['REQUEST_METHOD'];
