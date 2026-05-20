<?php
require_once 'csv_helper.php';

$filename = 'data/node_profile_mapping.csv';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rows = read_csv($filename);

    if ($action === 'add') {
        $new_row = [
            'node_pon_id' => $_POST['node_pon_id'],
            'profile_id' => $_POST['profile_id']
        ];
        $exists = false;
        foreach ($rows as $row) {
            if ($row['node_pon_id'] === $new_row['node_pon_id'] && $row['profile_id'] === $new_row['profile_id']) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $rows[] = $new_row;
            write_csv($filename, $rows);
            $message = "Mapping added successfully.";
        }
    } elseif ($action === 'delete') {
        $node_id = $_POST['node_pon_id'];
        $profile_id = $_POST['profile_id'];
        $rows = array_filter($rows, function($row) use ($node_id, $profile_id) {
            return !($row['node_pon_id'] === $node_id && $row['profile_id'] === $profile_id);
        });
        write_csv($filename, array_values($rows));
        $message = "Mapping deleted successfully.";
    }
}

$mappings = read_csv($filename);
$nodes_pons = read_csv('data/nodes_pons.csv');
$profiles = read_csv('data/profiles.csv');

$node_lookup = [];
foreach ($nodes_pons as $n) $node_lookup[$n['node_pon_id']] = $n['city'] . " - " . $n['node_pon_name'];

$profile_lookup = [];
foreach ($profiles as $p) $profile_lookup[$p['profile_id']] = $p['profile_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Node-Profile Mappings</title>
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
        <h2>Associate Profiles with Nodes/PONs</h2>
        <?php if ($message) echo "<p class='message'>$message</p>"; ?>

        <section>
            <h3>Add New Association</h3>
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
                    <label>Profile:</label>
                    <select name="profile_id" required>
                        <option value="">-- Select Profile --</option>
                        <?php foreach ($profiles as $item): ?>
                            <option value="<?php echo htmlspecialchars($item['profile_id']); ?>">
                                <?php echo htmlspecialchars($item['profile_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Associate Profile</button>
            </form>
        </section>

        <section>
            <h3>Existing Associations</h3>
            <table id="mappingsTable" class="display">
                <thead>
                    <tr>
                        <th>Node/PON</th>
                        <th>Profile</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mappings as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($node_lookup[$item['node_pon_id']] ?? $item['node_pon_id']); ?></td>
                        <td><?php echo htmlspecialchars($profile_lookup[$item['profile_id']] ?? $item['profile_id']); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="node_pon_id" value="<?php echo htmlspecialchars($item['node_pon_id']); ?>">
                                <input type="hidden" name="profile_id" value="<?php echo htmlspecialchars($item['profile_id']); ?>">
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
            $('#mappingsTable').DataTable();
        } );
    </script>
</body>
</html>
