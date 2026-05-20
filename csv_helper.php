<?php

function read_csv($filename) {
    ensure_csv_exists($filename);
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
        return get_default_headers($filename);
    }
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $headers = fgetcsv($handle);
        fclose($handle);
        return $headers ? $headers : get_default_headers($filename);
    }
    return get_default_headers($filename);
}

function ensure_csv_exists($filename) {
    if (!file_exists($filename)) {
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $headers = get_default_headers($filename);
        if (($handle = fopen($filename, "w")) !== FALSE) {
            fputcsv($handle, $headers);
            fclose($handle);
        }
    }
}

function get_default_headers($filename) {
    $base = basename($filename);
    switch ($base) {
        case 'towns_cities.csv':
            return ['city_name', 'state'];
        case 'nodes_pons.csv':
            return ['city', 'node_pon_id', 'node_pon_name', 'type'];
        case 'speed_packages.csv':
            return ['State', 'Current Plan', 'CSG CODE', 'Download Speed', 'Upload Speed', 'Provisioning System Name'];
        case 'profiles.csv':
            return ['profile_id', 'profile_name'];
        case 'profile_package_mapping.csv':
            return ['profile_id', 'package_id'];
        case 'node_profile_mapping.csv':
            return ['node_pon_id', 'profile_id'];
        default:
            return [];
    }
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
