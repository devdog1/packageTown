<?php

function read_csv($filename) {
    if (!file_exists($filename)) {
        return [];
    }
    $rows = [];
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $headers = fgetcsv($handle);
        if ($headers === FALSE) {
            fclose($handle);
            return [];
        }
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($headers) == count($data)) {
                $rows[] = array_combine($headers, $data);
            }
        }
        fclose($handle);
    }
    return $rows;
}

function write_csv($filename, $rows) {
    if (empty($rows)) {
        // If we want to clear the file but keep headers, we need to know what headers were.
        // For simplicity, let's assume we always have at least one row or we handle it.
        // Actually, let's keep headers if they exist.
        $headers = get_csv_headers($filename);
        if (($handle = fopen($filename, "w")) !== FALSE) {
            fputcsv($handle, $headers);
            fclose($handle);
        }
        return;
    }
    $headers = array_keys($rows[0]);
    if (($handle = fopen($filename, "w")) !== FALSE) {
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }
        fclose($handle);
    }
}

function get_csv_headers($filename) {
    if (!file_exists($filename)) {
        return [];
    }
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $headers = fgetcsv($handle);
        fclose($handle);
        return $headers ? $headers : [];
    }
    return [];
}

function generate_id($rows, $id_key) {
    $max_id = 0;
    foreach ($rows as $row) {
        if (isset($row[$id_key]) && is_numeric($row[$id_key])) {
            $max_id = max($max_id, (int)$row[$id_key]);
        }
    }
    return $max_id + 1;
}
?>
