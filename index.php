<?php
require_once "csv_helper.php";

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node and PON Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <style>
        .pkg-details { font-size: 0.85em; color: #666; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
        .pkg-name { font-weight: bold; color: #333; }

        .profile-container {
            position: relative;
            display: inline-block;
            margin-bottom: 10px;
            padding: 5px;
            border: 1px solid #ddd;
            background: #fcfcfc;
            width: 100%;
        }

        .highest-speed {
            cursor: pointer;
            color: #0066cc;
            font-weight: bold;
        }

        .speed-tooltip {
            visibility: hidden;
            width: 300px;
            background-color: #fff;
            color: #333;
            text-align: left;
            border: 1px solid #333;
            padding: 10px;
            position: absolute;
            z-index: 1000;
            top: 100%;
            left: 0;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        }

        .profile-container:hover .speed-tooltip {
            visibility: visible;
        }
    </style>
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars(get_setting("site_name", "Network Infrastructure Management")); ?></h1>
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
                <li><a href="manage_settings.php">Settings</a></li>
            </ul>
        </nav>
    </header>
    <main>
<?php

function speed_to_mbps($speed_str) {
    $speed_str = strtolower(trim($speed_str));
    $value = floatval($speed_str);
    if (strpos($speed_str, 'gbps') !== false) {
        return $value * 1000;
    }
    if (strpos($speed_str, 'mbps') !== false) {
        return $value;
    }
    if (strpos($speed_str, 'kbps') !== false) {
        return $value / 1000;
    }
    return $value;
}

$towns = read_csv('data/towns_cities.csv');
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

// City Summary Calculation
$city_summary = [];
foreach ($towns as $town) {
    $name = $town['Geographic Area'];
    $city_summary[$name] = [
        '2LA' => $town['2LA'],
        '3LA' => $town['3LA'],
        'CLLI' => $town['CLLI'],
        'Location' => $town['Location'],
        'docsis_count' => 0,
        'fiber_count' => 0,
        'types' => []
    ];
}

foreach ($nodes_pons as $item) {
    $c = $item['city'];
    if (!isset($city_summary[$c])) {
        $city_summary[$c] = ['2LA' => '', '3LA' => '', 'CLLI' => '', 'Location' => '', 'docsis_count' => 0, 'fiber_count' => 0, 'types' => []];
    }
    if ($item['type'] == 'docsis') {
        $city_summary[$c]['docsis_count']++;
        $city_summary[$c]['types']['docsis'] = true;
    } else if ($item['type'] == 'fiber') {
        $city_summary[$c]['fiber_count']++;
        $city_summary[$c]['types']['fiber'] = true;
    }
}

echo "<h2>City Summary</h2>";
echo "<table id='citySummaryTable' class='display'>";
echo "<thead><tr><th>Geographic Area</th><th>2LA</th><th>3LA</th><th>CLLI</th><th>Location</th><th>Type</th><th>Nodes</th><th>PONs</th></tr></thead>";
echo "<tbody>";
foreach ($city_summary as $name => $info) {
    $type_str = "";
    if (isset($info['types']['docsis']) && isset($info['types']['fiber'])) {
        $type_str = "Both";
    } elseif (isset($info['types']['docsis'])) {
        $type_str = "Docsis";
    } elseif (isset($info['types']['fiber'])) {
        $type_str = "Fiber";
    } else {
        $type_str = "None";
    }
    echo "<tr>";
    echo "<td>" . htmlspecialchars($name) . "</td>";
    echo "<td>" . htmlspecialchars($info['2LA']) . "</td>";
    echo "<td>" . htmlspecialchars($info['3LA']) . "</td>";
    echo "<td>" . htmlspecialchars($info['CLLI']) . "</td>";
    echo "<td>" . (!empty($info['Location']) ? "<a href='".htmlspecialchars($info['Location'])."' target='_blank'>View Map</a>" : "") . "</td>";
    echo "<td>" . $type_str . "</td>";
    echo "<td>" . $info['docsis_count'] . "</td>";
    echo "<td>" . $info['fiber_count'] . "</td>";
    echo "</tr>";
}
echo "</tbody></table>";

echo "<h2>Detailed Infrastructure Overview</h2>";
if (empty($nodes_pons)) {
    echo "<p>No Nodes or PONs defined yet.</p>";
} else {
    echo "<table id='overviewTable' class='display'>";
    echo "<thead><tr><th>City</th><th>Type</th><th>ID</th><th>Name</th><th>Profiles & Packages</th></tr></thead>";
    echo "<tbody>";
    foreach ($nodes_pons as $item) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['city']) . "</td>";
        echo "<td>" . ucfirst(htmlspecialchars($item['type'])) . "</td>";
        echo "<td>" . htmlspecialchars($item['node_pon_id']) . "</td>";
        echo "<td>" . htmlspecialchars($item['node_pon_name']) . "</td>";

        echo "<td>";
        if (isset($node_profiles[$item['node_pon_id']])) {
            foreach ($node_profiles[$item['node_pon_id']] as $profile_id) {
                $p_name = $profile_lookup[$profile_id] ?? $profile_id;

                $highest_pkg = null;
                $max_mbps = -1;
                $all_pkgs_html = "";

                if (isset($profile_packages[$profile_id])) {
                    foreach ($profile_packages[$profile_id] as $pkg_id) {
                        if (isset($package_lookup[$pkg_id])) {
                            $pkg = $package_lookup[$pkg_id];
                            $mbps = speed_to_mbps($pkg['Download Speed']);
                            if ($mbps > $max_mbps) {
                                $max_mbps = $mbps;
                                $highest_pkg = $pkg;
                            }

                            $pkg_html = "<div class='pkg-details'>";
                            $pkg_html .= "<span class='pkg-name'>" . htmlspecialchars($pkg['Current Plan']) . "</span><br>";
                            $pkg_html .= "Speeds: " . htmlspecialchars($pkg['Download Speed']) . " / " . htmlspecialchars($pkg['Upload Speed']) . "<br>";
                            $pkg_html .= "State: " . htmlspecialchars($pkg['State']) . " | CSG: " . htmlspecialchars($pkg['CSG CODE']) . "<br>";
                            $pkg_html .= "System: " . htmlspecialchars($pkg['Provisioning System Name']);
                            $pkg_html .= "</div>";
                            $all_pkgs_html .= $pkg_html;
                        }
                    }
                }

                echo "<div class='profile-container'>";
                echo "<strong>Profile: " . htmlspecialchars($p_name) . "</strong><br>";
                if ($highest_pkg) {
                    echo "Max Speed: <span class='highest-speed'>" . htmlspecialchars($highest_pkg['Download Speed']) . " (" . htmlspecialchars($highest_pkg['Current Plan']) . ")</span>";
                    echo "<div class='speed-tooltip'>";
                    echo "<h4>All Packages in Profile</h4>";
                    echo $all_pkgs_html;
                    echo "</div>";
                } else {
                    echo "<em>No packages in this profile.</em>";
                }
                echo "</div>";
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
    <script>
        $(document).ready( function () {
            $('#citySummaryTable').DataTable();
            $('#overviewTable').DataTable({
                "pageLength": 25
            });
        } );
    </script>
</body>
</html>
