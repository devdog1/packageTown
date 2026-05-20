<?php
require_once 'csv_helper.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    update_setting('site_name', $_POST['site_name']);
    update_setting('base_url', rtrim($_POST['base_url'], '/') . '/');
    $message = "Settings updated successfully.";
}

$site_name = get_setting('site_name', 'Network Infrastructure Management');
$base_url = get_setting('base_url', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($site_name); ?></h1>
        <nav>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="infrastructure_overview.php">Infrastructure</a></li>
                <li><a href="manage_towns.php">Towns & Cities</a></li>
                <li><a href="manage_nodes_pons.php">Nodes & PONs</a></li>
                <li><a href="manage_packages.php">Speed Packages</a></li>
                <li><a href="manage_profiles.php">Profiles</a></li>
                <li><a href="manage_mapping.php">Node Mapping</a></li>
                <li><a href="import.php">Bulk Import</a></li>
                <li><a href="api/docs.php" target="_blank">REST API</a></li>
                <li><a href="manage_settings.php">Settings</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>Application Settings</h2>
        <?php if ($message) echo "<p class='message'>$message</p>"; ?>

        <section>
            <form method="post">
                <div>
                    <label>Site Name:</label>
                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" required>
                </div>
                <div>
                    <label>Base URL (e.g., http://localhost/subdir/):</label>
                    <input type="text" name="base_url" value="<?php echo htmlspecialchars($base_url); ?>" placeholder="http://localhost/">
                    <p><small>Include the protocol (http/https) and any subdirectories. This is used for API documentation and internal links.</small></p>
                </div>
                <button type="submit">Save Settings</button>
            </form>
        </section>
    </main>
</body>
</html>
