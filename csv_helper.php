<?php

function get_data_path($filename) {
    // Always resolve relative to the directory containing csv_helper.php (the root)
    if (strpos($filename, '/') === false) {
        return __DIR__ . '/data/' . $filename;
    }
    if (strpos($filename, 'data/') === 0) {
        return __DIR__ . '/' . $filename;
    }
    if (strpos($filename, '../data/') === 0) {
        return __DIR__ . '/data/' . substr($filename, 8);
    }
    return $filename;
}

function read_csv($filename) {
    $filename = get_data_path($filename);
    ensure_csv_exists($filename);
    $rows = [];
    if (($handle = fopen($filename, "r")) !== FALSE) {
        // Fix for potentially malformed UTF-8 from older CSVs
        $headers = fgetcsv($handle);
        if ($headers !== FALSE) {
            foreach ($headers as &$h) $h = mb_convert_encoding($h, 'UTF-8', 'UTF-8');
        }
        if ($headers === FALSE) {
            fclose($handle);
            return [];
        }
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($headers) == count($data)) {
                foreach ($data as &$val) $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8');
                $rows[] = array_combine($headers, $data);
            }
        }
        fclose($handle);
    }
    return $rows;
}

function write_csv($filename, $rows) {
    $filename = get_data_path($filename);
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
    $filename = get_data_path($filename);
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
            return ['Geographic Area', '2LA', '3LA', 'CLLI', 'Location'];
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
        case 'settings.csv':
            return ['setting_key', 'setting_value'];
        default:
            return [];
    }
}

function get_setting($key, $default = '') {
    $settings = read_csv('data/settings.csv');
    foreach ($settings as $s) {
        if ($s['setting_key'] === $key) {
            return $s['setting_value'];
        }
    }
    return $default;
}

function update_setting($key, $value) {
    $settings = read_csv('data/settings.csv');
    $found = false;
    foreach ($settings as &$s) {
        if ($s['setting_key'] === $key) {
            $s['setting_value'] = $value;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $settings[] = ['setting_key' => $key, 'setting_value' => $value];
    }
    write_csv('data/settings.csv', $settings);
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
