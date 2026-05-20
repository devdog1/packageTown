<?php
require_once 'router.php';

$type = $_GET['type'] ?? '';
$filename = '';

if ($type === 'node_profile') {
    $filename = 'node_profile_mapping.csv';
} elseif ($type === 'profile_package') {
    $filename = 'profile_package_mapping.csv';
} else {
    send_response(['error' => 'Invalid or missing type parameter (node_profile or profile_package)'], 400);
}

if ($method === 'GET') {
    $rows = read_csv($filename);
    send_response($rows);
}

if ($method === 'POST') {
    $input = get_json_input();
    $rows = read_csv($filename);
    $rows[] = $input;
    write_csv($filename, $rows);
    send_response($input, 201);
}

if ($method === 'DELETE') {
    $input = get_json_input();
    $rows = read_csv($filename);
    $initial_count = count($rows);

    $rows = array_filter($rows, function($row) use ($input) {
        foreach ($input as $key => $value) {
            if (!isset($row[$key]) || $row[$key] !== $value) {
                return true;
            }
        }
        return false;
    });

    if (count($rows) === $initial_count) send_response(['error' => 'Not Found'], 404);
    write_csv($filename, array_values($rows));
    send_response(['status' => 'success']);
}
