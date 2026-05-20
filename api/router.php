<?php
require_once '../csv_helper.php';

header('Content-Type: application/json');

function send_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function get_json_input() {
    return json_decode(file_get_contents('php://input'), true);
}

$method = $_SERVER['REQUEST_METHOD'];
?>
