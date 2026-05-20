<?php
require_once 'csv_helper.php';

$filename = 'data/towns_cities.csv';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rows = read_csv($filename);

    if ($action === 'add') {
        $new_row = [
            'city_name' => $_POST['city_name'],
            'state' => $_POST['state']
        ];
        $rows[] = $new_row;
        write_csv($filename, $rows);
        $message = "Town/City added successfully.";
    } elseif ($action === 'delete') {
        $name = $_POST['city_name'];
        $rows = array_filter($rows, function($row) use ($name) {
            return $row['city_name'] !== $name;
        });
        write_csv($filename, array_values($rows));
        $message = "Town/City deleted successfully.";
    } elseif ($action === 'update') {
        $old_name = $_POST['old_city_name'];
        foreach ($rows as &$row) {
            if ($row['city_name'] === $old_name) {
                $row['city_name'] = $_POST['city_name'];
                $row['state'] = $_POST['state'];
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
        if ($item['city_name'] === $_GET['edit']) {
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
                    <input type="hidden" name="old_city_name" value="<?php echo htmlspecialchars($edit_item['city_name']); ?>">
                <?php endif; ?>

                <div>
                    <label>City Name:</label>
                    <input type="text" name="city_name" value="<?php echo $edit_item ? htmlspecialchars($edit_item['city_name']) : ''; ?>" required>
                </div>
                <div>
                    <label>State:</label>
                    <input type="text" name="state" value="<?php echo $edit_item ? htmlspecialchars($edit_item['state']) : ''; ?>" required>
                </div>
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
                        <th>City Name</th>
                        <th>State</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($towns as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['city_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['state']); ?></td>
                        <td>
                            <a href="?edit=<?php echo urlencode($item['city_name']); ?>">Edit</a>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="city_name" value="<?php echo htmlspecialchars($item['city_name']); ?>">
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
