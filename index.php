<?php
require_once "csv_helper.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node and PON Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars(get_setting("site_name", "Network Infrastructure Management")); ?></h1>
        <nav>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="infrastructure_overview.php">Infrastructure</a></li>
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
<?php

$towns = read_csv('towns_cities.csv');
$nodes_pons = read_csv('nodes_pons.csv');

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
    echo "<td><a href='manage_nodes_pons.php?city=" . urlencode($name) . "'>" . htmlspecialchars($name) . "</a></td>";
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
?>
    </main>
    <script>
        $(document).ready( function () {
            $('#citySummaryTable').DataTable();
        } );
    </script>
</body>
</html>
