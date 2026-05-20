<?php
require_once 'router.php';

$filename = 'nodes_pons.csv';
$id_field = 'node_pon_id';

if ($method === 'GET') {
    $rows = read_csv($filename);
    if (isset($_GET['id'])) {
        $found = null;
        foreach ($rows as $row) {
            if ($row[$id_field] === $_GET['id']) {
                $found = $row;
                break;
            }
        }
        if ($found) send_response($found);
        else send_response(['error' => 'Not Found'], 404);
    }
    send_response($rows);
}

if ($method === 'POST') {
    $input = get_json_input();
    if (!isset($input[$id_field])) send_response(['error' => 'Missing ID field'], 400);
    $rows = read_csv($filename);
    $rows[] = $input;
    write_csv($filename, $rows);
    send_response($input, 201);
}

if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    if (!$id) send_response(['error' => 'ID required'], 400);
    $input = get_json_input();
    $rows = read_csv($filename);
    $found = false;
    foreach ($rows as &$row) {
        if ($row[$id_field] === $id) {
            $row = array_merge($row, $input);
            $found = true;
            break;
        }
    }
    if (!$found) send_response(['error' => 'Not Found'], 404);
    write_csv($filename, $rows);
    send_response(['status' => 'success']);
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) send_response(['error' => 'ID required'], 400);
    $rows = read_csv($filename);
    $initial_count = count($rows);
    $rows = array_filter($rows, function($row) use ($id, $id_field) {
        return $row[$id_field] !== $id;
    });
    if (count($rows) === $initial_count) send_response(['error' => 'Not Found'], 404);
    write_csv($filename, array_values($rows));
    send_response(['status' => 'success']);
}
