<?php
require_once 'csv_helper.php';

$filename = 'data/speed_packages.csv';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rows = read_csv($filename);

    if ($action === 'add') {
        $new_row = [
            'package_id' => $_POST['package_id'],
            'package_name' => $_POST['package_name'],
            'download_speed' => $_POST['download_speed'],
            'upload_speed' => $_POST['upload_speed']
        ];
        $rows[] = $new_row;
        write_csv($filename, $rows);
        $message = "Package added successfully.";
    } elseif ($action === 'delete') {
        $id_to_delete = $_POST['package_id'];
        $rows = array_filter($rows, function($row) use ($id_to_delete) {
            return $row['package_id'] !== $id_to_delete;
        });
        write_csv($filename, array_values($rows));
        $message = "Package deleted successfully.";
    } elseif ($action === 'update') {
        $old_id = $_POST['old_package_id'];
        foreach ($rows as &$row) {
            if ($row['package_id'] === $old_id) {
                $row['package_id'] = $_POST['package_id'];
                $row['package_name'] = $_POST['package_name'];
                $row['download_speed'] = $_POST['download_speed'];
                $row['upload_speed'] = $_POST['upload_speed'];
            }
        }
        write_csv($filename, $rows);
        $message = "Package updated successfully.";
    }
}

$packages = read_csv($filename);
$edit_item = null;
if (isset($_GET['edit'])) {
    foreach ($packages as $item) {
        if ($item['package_id'] === $_GET['edit']) {
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
    <title>Manage Speed Packages</title>
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
                <li><a href="manage_profiles.php">Profiles</a></li>
                <li><a href="manage_mapping.php">Node Mapping</a></li>
                <li><a href="import.php">Bulk Import</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h2>Manage Internet Speed Packages</h2>
        <?php if ($message) echo "<p class='message'>$message</p>"; ?>

        <section>
            <h3><?php echo $edit_item ? 'Edit' : 'Add New'; ?> Package</h3>
            <form method="post">
                <input type="hidden" name="action" value="<?php echo $edit_item ? 'update' : 'add'; ?>">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="old_package_id" value="<?php echo htmlspecialchars($edit_item['package_id']); ?>">
                <?php endif; ?>

                <div>
                    <label>Package ID:</label>
                    <input type="text" name="package_id" value="<?php echo $edit_item ? htmlspecialchars($edit_item['package_id']) : ''; ?>" required>
                </div>
                <div>
                    <label>Package Name:</label>
                    <input type="text" name="package_name" value="<?php echo $edit_item ? htmlspecialchars($edit_item['package_name']) : ''; ?>" required>
                </div>
                <div>
                    <label>Download Speed:</label>
                    <input type="text" name="download_speed" value="<?php echo $edit_item ? htmlspecialchars($edit_item['download_speed']) : ''; ?>" placeholder="e.g. 100Mbps" required>
                </div>
                <div>
                    <label>Upload Speed:</label>
                    <input type="text" name="upload_speed" value="<?php echo $edit_item ? htmlspecialchars($edit_item['upload_speed']) : ''; ?>" placeholder="e.g. 20Mbps" required>
                </div>
                <button type="submit"><?php echo $edit_item ? 'Update' : 'Add'; ?></button>
                <?php if ($edit_item): ?>
                    <a href="manage_packages.php">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <section>
            <h3>Existing Packages</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Download</th>
                        <th>Upload</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($packages as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['package_id']); ?></td>
                        <td><?php echo htmlspecialchars($item['package_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['download_speed']); ?></td>
                        <td><?php echo htmlspecialchars($item['upload_speed']); ?></td>
                        <td>
                            <a href="?edit=<?php echo urlencode($item['package_id']); ?>">Edit</a>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
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
