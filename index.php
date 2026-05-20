<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node and PON Management</title>
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
            </ul>
        </nav>
    </header>
    <main>
<?php
require_once 'csv_helper.php';

$nodes_pons = read_csv('data/nodes_pons.csv');
$packages = read_csv('data/speed_packages.csv');
$mappings = read_csv('data/package_mapping.csv');

// Create a lookup for packages
$package_lookup = [];
foreach ($packages as $pkg) {
    $package_lookup[$pkg['package_id']] = $pkg['package_name'] . " (" . $pkg['speed'] . ")";
}

// Group mappings by node_pon_id
$node_mappings = [];
foreach ($mappings as $map) {
    $node_mappings[$map['node_pon_id']][] = $map['package_id'];
}

echo "<h2>System Overview</h2>";
if (empty($nodes_pons)) {
    echo "<p>No Nodes or PONs defined yet.</p>";
} else {
    echo "<table>";
    echo "<thead><tr><th>City</th><th>ID (Node/PON)</th><th>Name</th><th>Associated Packages</th></tr></thead>";
    echo "<tbody>";
    foreach ($nodes_pons as $item) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['city']) . "</td>";
        echo "<td>" . htmlspecialchars($item['node_pon_id']) . "</td>";
        echo "<td>" . htmlspecialchars($item['node_pon_name']) . "</td>";

        $associated = [];
        if (isset($node_mappings[$item['node_pon_id']])) {
            foreach ($node_mappings[$item['node_pon_id']] as $pkg_id) {
                if (isset($package_lookup[$pkg_id])) {
                    $associated[] = htmlspecialchars($package_lookup[$pkg_id]);
                }
            }
        }
        echo "<td>" . (empty($associated) ? "None" : implode(", ", $associated)) . "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
}
?>
    </main>
</body>
</html>
