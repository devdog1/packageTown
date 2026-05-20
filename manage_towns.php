<?php
require_once "csv_helper.php";
$filename = 'data/towns_cities.csv';
$message = '';

$fields = ['Geographic Area', '2LA', '3LA', 'CLLI', 'Location'];

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
        $message = "Town/City added successfully.";
    } elseif ($action === 'delete') {
        $name = $_POST['geographic_area'];
        $rows = array_filter($rows, function($row) use ($name) {
            return $row['Geographic Area'] !== $name;
        });
        write_csv($filename, array_values($rows));
        $message = "Town/City deleted successfully.";
    } elseif ($action === 'update') {
        $old_name = $_POST['old_geographic_area'];
        foreach ($rows as &$row) {
            if ($row['Geographic Area'] === $old_name) {
                foreach ($fields as $f) {
                    $row[$f] = $_POST[str_replace(' ', '_', $f)];
                }
            }
        }
        write_csv($filename, $rows);
        $message = "Town/City updated successfully.";
    }
}

$towns = read_csv($filename);
$edit_item = null;
if (isset($_GET['edit'])) {
    foreach ($towns as $item) {
        if ($item['Geographic Area'] === $_GET['edit']) {
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
    <title>Manage Towns & Cities</title>
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
        <h2>Manage Towns and Cities</h2>
        <?php if ($message) echo "<p class='message'>$message</p>"; ?>

        <section>
            <h3><?php echo $edit_item ? 'Edit' : 'Add New'; ?> Town/City</h3>
            <form method="post">
                <input type="hidden" name="action" value="<?php echo $edit_item ? 'update' : 'add'; ?>">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="old_geographic_area" value="<?php echo htmlspecialchars($edit_item['Geographic Area']); ?>">
                <?php endif; ?>

                <?php foreach ($fields as $f):
                    $id = str_replace(' ', '_', $f);
                ?>
                <div>
                    <label><?php echo htmlspecialchars($f); ?>:</label>
                    <input type="text" name="<?php echo $id; ?>" value="<?php echo $edit_item ? htmlspecialchars($edit_item[$f]) : ''; ?>" <?php echo ($f === 'Geographic Area') ? 'required' : ''; ?>>
                </div>
                <?php endforeach; ?>

                <button type="submit"><?php echo $edit_item ? 'Update' : 'Add'; ?></button>
                <?php if ($edit_item): ?>
                    <a href="manage_towns.php">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <section>
            <h3>Existing Towns and Cities</h3>
            <table id="townsTable" class="display">
                <thead>
                    <tr>
                        <?php foreach ($fields as $f): ?>
                            <th><?php echo htmlspecialchars($f); ?></th>
                        <?php endforeach; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($towns as $item): ?>
                    <tr>
                        <?php foreach ($fields as $f): ?>
                            <td>
                                <?php if ($f === 'Location' && !empty($item[$f])): ?>
                                    <a href="<?php echo htmlspecialchars($item[$f]); ?>" target="_blank">View Map</a>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($item[$f]); ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <a href="?edit=<?php echo urlencode($item['Geographic Area']); ?>">Edit</a>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="geographic_area" value="<?php echo htmlspecialchars($item['Geographic Area']); ?>">
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
            $('#townsTable').DataTable();
        } );
    </script>
</body>
</html>
