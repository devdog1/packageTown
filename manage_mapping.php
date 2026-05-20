<?php
require_once 'csv_helper.php';

$filename = 'data/package_mapping.csv';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rows = read_csv($filename);

    if ($action === 'add') {
        $new_row = [
            'node_pon_id' => $_POST['node_pon_id'],
            'package_id' => $_POST['package_id']
        ];
        // Prevent duplicates
        $exists = false;
        foreach ($rows as $row) {
            if ($row['node_pon_id'] === $new_row['node_pon_id'] && $row['package_id'] === $new_row['package_id']) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $rows[] = $new_row;
            write_csv($filename, $rows);
            $message = "Mapping added successfully.";
        } else {
            $message = "Mapping already exists.";
        }
    } elseif ($action === 'delete') {
        $node_id = $_POST['node_pon_id'];
        $pkg_id = $_POST['package_id'];
        $rows = array_filter($rows, function($row) use ($node_id, $pkg_id) {
            return !($row['node_pon_id'] === $node_id && $row['package_id'] === $pkg_id);
        });
        write_csv($filename, array_values($rows));
        $message = "Mapping deleted successfully.";
    }
}

$mappings = read_csv($filename);
$nodes_pons = read_csv('data/nodes_pons.csv');
$packages = read_csv('data/speed_packages.csv');

// Create lookups
$node_lookup = [];
foreach ($nodes_pons as $n) $node_lookup[$n['node_pon_id']] = $n['city'] . " - " . $n['node_pon_name'];

$pkg_lookup = [];
foreach ($packages as $p) $pkg_lookup[$p['package_id']] = $p['package_name'] . " (" . $p['download_speed'] . "/" . $p['upload_speed'] . ")";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Mappings</title>
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
        <h2>Manage Package to Node/PON Mappings</h2>
        <?php if ($message) echo "<p class='message'>$message</p>"; ?>

        <section>
            <h3>Add New Mapping</h3>
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div>
                    <label>Node/PON:</label>
                    <select name="node_pon_id" required>
                        <option value="">-- Select Node/PON --</option>
                        <?php foreach ($nodes_pons as $item): ?>
                            <option value="<?php echo htmlspecialchars($item['node_pon_id']); ?>">
                                <?php echo htmlspecialchars($item['city'] . " - " . $item['node_pon_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Speed Package:</label>
                    <select name="package_id" required>
                        <option value="">-- Select Package --</option>
                        <?php foreach ($packages as $item): ?>
                            <option value="<?php echo htmlspecialchars($item['package_id']); ?>">
                                <?php echo htmlspecialchars($item['package_name'] . " (" . $item['download_speed'] . "/" . $item['upload_speed'] . ")"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Add Mapping</button>
            </form>
        </section>

        <section>
            <h3>Existing Mappings</h3>
            <table>
                <thead>
                    <tr>
                        <th>Node/PON</th>
                        <th>Package</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mappings as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($node_lookup[$item['node_pon_id']] ?? $item['node_pon_id']); ?></td>
                        <td><?php echo htmlspecialchars($pkg_lookup[$item['package_id']] ?? $item['package_id']); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="node_pon_id" value="<?php echo htmlspecialchars($item['node_pon_id']); ?>">
                                <input type="hidden" name="package_id" value="<?php echo htmlspecialchars($item['package_id']); ?>">
                                <button type="submit" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
