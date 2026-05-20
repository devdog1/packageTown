<?php
require_once "csv_helper.php";
$filename = 'data/speed_packages.csv';
$message = '';

$fields = [
    'State',
    'Current Plan',
    'CSG CODE',
    'Download Speed',
    'Upload Speed',
    'Provisioning System Name'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rows = read_csv($filename);

    if ($action === 'add') {
        $new_row = [];
        foreach ($fields as $f) {
            $new_row[$f] = $_POST[str_replace(' ', '_', $f)];
        }
        $rows[] = $new_row;
        write_csv($filename, $rows);
        $message = "Package added successfully.";
    } elseif ($action === 'delete') {
        $plan_to_delete = $_POST['current_plan'];
        $rows = array_filter($rows, function($row) use ($plan_to_delete) {
            return $row['Current Plan'] !== $plan_to_delete;
        });
        write_csv($filename, array_values($rows));
        $message = "Package deleted successfully.";
    } elseif ($action === 'update') {
        $old_plan = $_POST['old_current_plan'];
        foreach ($rows as &$row) {
            if ($row['Current Plan'] === $old_plan) {
                foreach ($fields as $f) {
                    $row[$f] = $_POST[str_replace(' ', '_', $f)];
                }
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
        if ($item['Current Plan'] === $_GET['edit']) {
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
        <h2>Manage Internet Speed Packages</h2>
        <?php if ($message) echo "<p class='message'>$message</p>"; ?>

        <section>
            <h3><?php echo $edit_item ? 'Edit' : 'Add New'; ?> Package</h3>
            <form method="post">
                <input type="hidden" name="action" value="<?php echo $edit_item ? 'update' : 'add'; ?>">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="old_current_plan" value="<?php echo htmlspecialchars($edit_item['Current Plan']); ?>">
                <?php endif; ?>

                <?php foreach ($fields as $f):
                    $id = str_replace(' ', '_', $f);
                ?>
                <div>
                    <label><?php echo htmlspecialchars($f); ?>:</label>
                    <input type="text" name="<?php echo $id; ?>" value="<?php echo $edit_item ? htmlspecialchars($edit_item[$f]) : ''; ?>" required>
                </div>
                <?php endforeach; ?>

                <button type="submit"><?php echo $edit_item ? 'Update' : 'Add'; ?></button>
                <?php if ($edit_item): ?>
                    <a href="manage_packages.php">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <section>
            <h3>Existing Packages</h3>
            <table id="packagesTable" class="display" style="font-size: 0.9em;">
                <thead>
                    <tr>
                        <?php foreach ($fields as $f): ?>
                            <th><?php echo htmlspecialchars($f); ?></th>
                        <?php endforeach; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($packages as $item): ?>
                    <tr>
                        <?php foreach ($fields as $f): ?>
                            <td><?php echo htmlspecialchars($item[$f]); ?></td>
                        <?php endforeach; ?>
                        <td>
                            <a href="?edit=<?php echo urlencode($item['Current Plan']); ?>">Edit</a>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="current_plan" value="<?php echo htmlspecialchars($item['Current Plan']); ?>">
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
            $('#packagesTable').DataTable();
        } );
    </script>
</body>
</html>
