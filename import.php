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
            switch ($target_type) {
                case 'nodes_pons': $target_file = 'data/nodes_pons.csv'; break;
                case 'packages': $target_file = 'data/speed_packages.csv'; break;
                case 'mappings': $target_file = 'data/package_mapping.csv'; break;
            }

            if ($target_file) {
                if (isset($_POST['append']) && $_POST['append'] == '1') {
                    $existingData = read_csv($target_file);
                    $combinedData = array_merge($existingData, $newData);
                    // Optional: remove duplicates if needed
                    write_csv($target_file, $combinedData);
                } else {
                    write_csv($target_file, $newData);
                }
                $message = "Successfully imported " . count($newData) . " records to " . $target_type;
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
                <li><a href="manage_nodes_pons.php">Nodes & PONs</a></li>
                <li><a href="manage_packages.php">Speed Packages</a></li>
                <li><a href="manage_mapping.php">Package Mappings</a></li>
                <li><a href="import.php">Bulk Import</a></li>
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
                        <option value="nodes_pons">Nodes & PONs</option>
                        <option value="packages">Speed Packages</option>
                        <option value="mappings">Package Mappings</option>
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
                <li><a href="templates/nodes_pons_template.csv" download>Nodes & PONs Template</a></li>
                <li><a href="templates/speed_packages_template.csv" download>Speed Packages Template</a></li>
                <li><a href="templates/package_mapping_template.csv" download>Package Mappings Template</a></li>
            </ul>
        </section>
    </main>
</body>
</html>
