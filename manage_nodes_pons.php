<?php
require_once 'csv_helper.php';

$filename = 'data/nodes_pons.csv';
$towns_file = 'data/towns_cities.csv';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rows = read_csv($filename);

    if ($action === 'add') {
        $new_row = [
            'city' => $_POST['city'],
            'node_pon_id' => $_POST['node_pon_id'],
            'node_pon_name' => $_POST['node_pon_name'],
            'type' => $_POST['type']
        ];
        $rows[] = $new_row;
        write_csv($filename, $rows);
        $message = "Node/PON added successfully.";
    } elseif ($action === 'delete') {
        $id_to_delete = $_POST['node_pon_id'];
        $rows = array_filter($rows, function($row) use ($id_to_delete) {
            return $row['node_pon_id'] !== $id_to_delete;
        });
        write_csv($filename, array_values($rows));
        $message = "Node/PON deleted successfully.";
    } elseif ($action === 'update') {
        $old_id = $_POST['old_node_pon_id'];
        foreach ($rows as &$row) {
            if ($row['node_pon_id'] === $old_id) {
                $row['city'] = $_POST['city'];
                $row['node_pon_id'] = $_POST['node_pon_id'];
                $row['node_pon_name'] = $_POST['node_pon_name'];
                $row['type'] = $_POST['type'];
            }
        }
        write_csv($filename, $rows);
        $message = "Node/PON updated successfully.";
    }
}

$nodes_pons = read_csv($filename);
$towns = read_csv($towns_file);

$edit_item = null;
if (isset($_GET['edit'])) {
    foreach ($nodes_pons as $item) {
        if ($item['node_pon_id'] === $_GET['edit']) {
            $edit_item = $item;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Nodes & PONs</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
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
        <h2>Manage Nodes and PONs</h2>
        <?php if ($message) echo "<p class='message'>$message</p>"; ?>

        <section>
            <h3><?php echo $edit_item ? 'Edit' : 'Add New'; ?> Node/PON</h3>
            <form method="post">
                <input type="hidden" name="action" value="<?php echo $edit_item ? 'update' : 'add'; ?>">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="old_node_pon_id" value="<?php echo htmlspecialchars($edit_item['node_pon_id']); ?>">
                <?php endif; ?>

                <div>
                    <label>City (Geographic Area):</label>
                    <select name="city" required>
                        <option value="">-- Select City --</option>
                        <?php foreach ($towns as $town): ?>
                            <option value="<?php echo htmlspecialchars($town['Geographic Area']); ?>" <?php echo ($edit_item && $edit_item['city'] == $town['Geographic Area']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($town['Geographic Area']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Node/PON ID:</label>
                    <input type="text" name="node_pon_id" value="<?php echo $edit_item ? htmlspecialchars($edit_item['node_pon_id']) : ''; ?>" required>
                </div>
                <div>
                    <label>Node/PON Name:</label>
                    <input type="text" name="node_pon_name" value="<?php echo $edit_item ? htmlspecialchars($edit_item['node_pon_name']) : ''; ?>" required>
                </div>
                <div>
                    <label>Type:</label>
                    <select name="type" required>
                        <option value="docsis" <?php echo ($edit_item && $edit_item['type'] == 'docsis') ? 'selected' : ''; ?>>Docsis (Node)</option>
                        <option value="fiber" <?php echo ($edit_item && $edit_item['type'] == 'fiber') ? 'selected' : ''; ?>>Fiber (PON)</option>
                    </select>
                </div>
                <button type="submit"><?php echo $edit_item ? 'Update' : 'Add'; ?></button>
                <?php if ($edit_item): ?>
                    <a href="manage_nodes_pons.php">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <section>
            <h3>Existing Nodes and PONs</h3>
            <table id="nodesTable" class="display">
                <thead>
                    <tr>
                        <th>City</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nodes_pons as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['city']); ?></td>
                        <td><?php echo htmlspecialchars($item['node_pon_id']); ?></td>
                        <td><?php echo htmlspecialchars($item['node_pon_name']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($item['type'])); ?></td>
                        <td>
                            <a href="?edit=<?php echo urlencode($item['node_pon_id']); ?>">Edit</a>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="node_pon_id" value="<?php echo htmlspecialchars($item['node_pon_id']); ?>">
                                <button type="submit" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
    <script>
        $(document).ready( function () {
            $('#nodesTable').DataTable();
        } );
    </script>
</body>
</html>
