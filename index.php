<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node and PON Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .pkg-details { font-size: 0.85em; color: #666; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
        .pkg-name { font-weight: bold; color: #333; }
    </style>
</head>
<body>
    <header>
        <h1>Network Infrastructure Management</h1>
        <nav>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="manage_nodes_pons.php">Nodes & PONs</a></li>
                <li><a href="manage_packages.php">Speed Packages</a></li>
                <li><a href="manage_profiles.php">Profiles</a></li>
                <li><a href="manage_mapping.php">Node Mapping</a></li>
                <li><a href="import.php">Bulk Import</a></li>
            </ul>
        </nav>
    </header>
    <main>
<?php
require_once 'csv_helper.php';

$nodes_pons = read_csv('data/nodes_pons.csv');
$packages = read_csv('data/speed_packages.csv');
$profiles = read_csv('data/profiles.csv');
$profile_package_mappings = read_csv('data/profile_package_mapping.csv');
$node_profile_mappings = read_csv('data/node_profile_mapping.csv');

// Create a lookup for packages
$package_lookup = [];
foreach ($packages as $pkg) {
    $package_lookup[$pkg['Current Plan']] = $pkg;
}

// Create a lookup for profiles
$profile_lookup = [];
foreach ($profiles as $p) {
    $profile_lookup[$p['profile_id']] = $p['profile_name'];
}

// Group packages by profile
$profile_packages = [];
foreach ($profile_package_mappings as $ppm) {
    $profile_packages[$ppm['profile_id']][] = $ppm['package_id'];
}

// Group profiles by node_pon_id
$node_profiles = [];
foreach ($node_profile_mappings as $npm) {
    $node_profiles[$npm['node_pon_id']][] = $npm['profile_id'];
}

echo "<h2>System Overview</h2>";
if (empty($nodes_pons)) {
    echo "<p>No Nodes or PONs defined yet.</p>";
} else {
    echo "<table>";
    echo "<thead><tr><th>City</th><th>ID (Node/PON)</th><th>Name</th><th>Profiles & Packages</th></tr></thead>";
    echo "<tbody>";
    foreach ($nodes_pons as $item) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['city']) . "</td>";
        echo "<td>" . htmlspecialchars($item['node_pon_id']) . "</td>";
        echo "<td>" . htmlspecialchars($item['node_pon_name']) . "</td>";

        echo "<td>";
        if (isset($node_profiles[$item['node_pon_id']])) {
            foreach ($node_profiles[$item['node_pon_id']] as $profile_id) {
                $p_name = $profile_lookup[$profile_id] ?? $profile_id;
                echo "<strong>Profile: " . htmlspecialchars($p_name) . "</strong>";
                if (isset($profile_packages[$profile_id])) {
                    echo "<div style='margin-left: 20px; margin-top: 10px;'>";
                    foreach ($profile_packages[$profile_id] as $pkg_id) {
                        if (isset($package_lookup[$pkg_id])) {
                            $pkg = $package_lookup[$pkg_id];
                            echo "<div class='pkg-details'>";
                            echo "<span class='pkg-name'>" . htmlspecialchars($pkg['Current Plan']) . "</span><br>";
                            echo "Speeds: " . htmlspecialchars($pkg['Download Speed']) . " / " . htmlspecialchars($pkg['Upload Speed']) . "<br>";
                            echo "State: " . htmlspecialchars($pkg['State']) . " | CSG: " . htmlspecialchars($pkg['CSG CODE']) . "<br>";
                            echo "System: " . htmlspecialchars($pkg['Provisioning System Name']);
                            echo "</div>";
                        } else {
                            echo "<div class='pkg-details'>Unknown Package: " . htmlspecialchars($pkg_id) . "</div>";
                        }
                    }
                    echo "</div>";
                } else {
                    echo "<p><em>No packages in this profile.</em></p>";
                }
            }
        } else {
            echo "None";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
}
?>
    </main>
</body>
</html>
