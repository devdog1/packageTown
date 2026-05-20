<?php
require_once 'csv_helper.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $target_type = $_POST['type'];
    $file = $_FILES['csv_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = $file['tmp_name'];
        $newData = [];

        if (($handle = fopen($filename, "r")) !== FALSE) {
            $headers = fgetcsv($handle);
            if ($headers !== FALSE) {
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($headers) == count($data)) {
                        $newData[] = array_combine($headers, $data);
                    }
                }
            }
            fclose($handle);
        }

        if (!empty($newData)) {
            $target_file = '';
            $handle_special_nodes = false;
            switch ($target_type) {
                case 'towns': $target_file = 'data/towns_cities.csv'; break;
                case 'nodes_pons':
                    $target_file = 'data/nodes_pons.csv';
                    $handle_special_nodes = true;
                    break;
                case 'packages': $target_file = 'data/speed_packages.csv'; break;
                case 'profiles': $target_file = 'data/profiles.csv'; break;
                case 'profile_packages': $target_file = 'data/profile_package_mapping.csv'; break;
                case 'node_profiles': $target_file = 'data/node_profile_mapping.csv'; break;
            }

            if ($target_file) {
                $append = (isset($_POST['append']) && $_POST['append'] == '1');

                if ($handle_special_nodes) {
                    $nodesOnly = [];
                    $nodeProfileMappings = [];
                    foreach ($newData as $row) {
                        $profileId = $row['profile_id'] ?? null;
                        unset($row['profile_id']);
                        $nodesOnly[] = $row;
                        if ($profileId) {
                            $nodeProfileMappings[] = [
                                'node_pon_id' => $row['node_pon_id'],
                                'profile_id' => $profileId
                            ];
                        }
                    }

                    if ($append) {
                        $existingNodes = read_csv($target_file);
                        write_csv($target_file, array_merge($existingNodes, $nodesOnly));
                    } else {
                        write_csv($target_file, $nodesOnly);
                    }

                    if (!empty($nodeProfileMappings)) {
                        $mappingFile = 'data/node_profile_mapping.csv';
                        $existingMappings = read_csv($mappingFile);
                        write_csv($mappingFile, array_merge($existingMappings, $nodeProfileMappings));
                    }
                } else {
                    if ($append) {
                        $existingData = read_csv($target_file);
                        write_csv($target_file, array_merge($existingData, $newData));
                    } else {
                        write_csv($target_file, $newData);
                    }
                }
                $message = "Successfully imported records to " . $target_type;
            }
        } else {
            $error = "No data found in CSV or header mismatch.";
        }
    } else {
        $error = "File upload error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Import</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Network Infrastructure Management</h1>
        <nav>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="manage_towns.php">Towns & Cities</a></li>
                <li><a href="manage_nodes_pons.php">Nodes & PONs</a></li>
                <li><a href="manage_packages.php">Speed Packages</a></li>
                <li><a href="manage_profiles.php">Profiles</a></li>
                <li><a href="manage_mapping.php">Node Mapping</a></li>
                <li><a href="import.php">Bulk Import</a></li>
                <li><a href="api/docs.php" target="_blank">REST API</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>Bulk Import Data</h2>
        <?php if ($message) echo "<p class='message'>$message</p>"; ?>
        <?php if ($error) echo "<p class='message' style='background:#f8d7da; color:#721c24; border-color:#f5c6cb;'>$error</p>"; ?>

        <section>
            <h3>Upload CSV File</h3>
            <form method="post" enctype="multipart/form-data">
                <div>
                    <label>Import Type:</label>
                    <select name="type" required>
                        <option value="towns">Towns & Cities</option>
                        <option value="nodes_pons">Nodes & PONs (can include profile_id column)</option>
                        <option value="packages">Speed Packages</option>
                        <option value="profiles">Profiles</option>
                        <option value="profile_packages">Packages to Profile Mapping</option>
                        <option value="node_profiles">Profile to Node Mapping</option>
                    </select>
                </div>
                <div>
                    <label>CSV File:</label>
                    <input type="file" name="csv_file" accept=".csv" required>
                </div>
                <div>
                    <label>Append to existing data?</label>
                    <input type="checkbox" name="append" value="1" checked>
                </div>
                <button type="submit">Upload & Import</button>
            </form>
        </section>

        <section>
            <h3>Download Templates</h3>
            <p>Use these templates to format your data for import:</p>
            <ul>
                <li><a href="templates/towns_cities_template.csv" download>Towns & Cities Template</a></li>
                <li><a href="templates/nodes_pons_template.csv" download>Nodes & PONs Template</a></li>
                <li><a href="templates/speed_packages_template.csv" download>Speed Packages Template</a></li>
                <li><a href="templates/profiles_template.csv" download>Profiles Template</a></li>
                <li><a href="templates/profile_package_mapping_template.csv" download>Packages to Profile Template</a></li>
                <li><a href="templates/node_profile_mapping_template.csv" download>Profile to Node Template</a></li>
            </ul>
        </section>
    </main>
</body>
</html>
