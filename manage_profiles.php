<?php
require_once 'csv_helper.php';

$profiles_file = 'data/profiles.csv';
$mapping_file = 'data/profile_package_mapping.csv';
$packages_file = 'data/speed_packages.csv';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_profile') {
        $rows = read_csv($profiles_file);
        $rows[] = [
            'profile_id' => $_POST['profile_id'],
            'profile_name' => $_POST['profile_name']
        ];
        write_csv($profiles_file, $rows);
        $message = "Profile added successfully.";
    } elseif ($action === 'delete_profile') {
        $id = $_POST['profile_id'];
        $rows = array_filter(read_csv($profiles_file), fn($r) => $r['profile_id'] !== $id);
        write_csv($profiles_file, array_values($rows));
        $mappings = array_filter(read_csv($mapping_file), fn($m) => $m['profile_id'] !== $id);
        write_csv($mapping_file, array_values($mappings));
        $message = "Profile and its mappings deleted.";
    } elseif ($action === 'add_mapping') {
        $rows = read_csv($mapping_file);
        $new_mapping = ['profile_id' => $_POST['profile_id'], 'package_id' => $_POST['package_id']];
        $exists = false;
        foreach ($rows as $row) {
            if ($row['profile_id'] == $new_mapping['profile_id'] && $row['package_id'] == $new_mapping['package_id']) {
                $exists = true; break;
            }
        }
        if (!$exists) {
            $rows[] = $new_mapping;
            write_csv($mapping_file, $rows);
            $message = "Package added to profile.";
        }
    } elseif ($action === 'delete_mapping') {
        $pid = $_POST['profile_id'];
        $pkid = $_POST['package_id'];
        $rows = array_filter(read_csv($mapping_file), fn($m) => !($m['profile_id'] == $pid && $m['package_id'] == $pkid));
        write_csv($mapping_file, array_values($rows));
        $message = "Package removed from profile.";
    }
}

$profiles = read_csv($profiles_file);
$packages = read_csv($packages_file);
$mappings = read_csv($mapping_file);

$pkg_lookup = [];
foreach ($packages as $p) {
    $pkg_lookup[$p['Current Plan']] = $p['Current Plan'] . " (" . $p['Download Speed'] . "/" . $p['Upload Speed'] . ") [" . $p['State'] . "]";
}

$profile_packages = [];
foreach ($mappings as $m) {
    $profile_packages[$m['profile_id']][] = $m['package_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Profiles</title>
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
        <h2>Manage Speed Profiles</h2>
        <?php if ($message) echo "<p class='message'>$message</p>"; ?>

        <section>
            <h3>Add New Profile</h3>
            <form method="post">
                <input type="hidden" name="action" value="add_profile">
                <div>
                    <label>Profile ID:</label>
                    <input type="text" name="profile_id" required>
                </div>
                <div>
                    <label>Profile Name:</label>
                    <input type="text" name="profile_name" required>
                </div>
                <button type="submit">Create Profile</button>
            </form>
        </section>

        <section>
            <h3>Assign Packages to Profiles</h3>
            <form method="post">
                <input type="hidden" name="action" value="add_mapping">
                <div>
                    <label>Profile:</label>
                    <select name="profile_id" required>
                        <option value="">-- Select Profile --</option>
                        <?php foreach ($profiles as $p): ?>
                            <option value="<?php echo htmlspecialchars($p['profile_id']); ?>"><?php echo htmlspecialchars($p['profile_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Package:</label>
                    <select name="package_id" required>
                        <option value="">-- Select Package --</option>
                        <?php foreach ($packages as $pkg): ?>
                            <option value="<?php echo htmlspecialchars($pkg['Current Plan']); ?>"><?php echo htmlspecialchars($pkg_lookup[$pkg['Current Plan']] ?? $pkg['Current Plan']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Add Package to Profile</button>
            </form>
        </section>

        <section>
            <h3>Existing Profiles</h3>
            <table id="profilesTable" class="display">
                <thead>
                    <tr>
                        <th>Profile Name (ID)</th>
                        <th>Assigned Packages</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($profiles as $p): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($p['profile_name']); ?></strong> (<?php echo htmlspecialchars($p['profile_id']); ?>)</td>
                        <td>
                            <?php if (isset($profile_packages[$p['profile_id']])): ?>
                                <ul>
                                <?php foreach ($profile_packages[$p['profile_id']] as $pkg_id): ?>
                                    <li>
                                        <?php echo htmlspecialchars($pkg_lookup[$pkg_id] ?? $pkg_id); ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_mapping">
                                            <input type="hidden" name="profile_id" value="<?php echo htmlspecialchars($p['profile_id']); ?>">
                                            <input type="hidden" name="package_id" value="<?php echo htmlspecialchars($pkg_id); ?>">
                                            <button type="submit" style="padding: 2px 5px; font-size: 0.8em; background: #c00;">x</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                No packages assigned.
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="action" value="delete_profile">
                                <input type="hidden" name="profile_id" value="<?php echo htmlspecialchars($p['profile_id']); ?>">
                                <button type="submit" onclick="return confirm('Delete profile?')">Delete Profile</button>
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
            $('#profilesTable').DataTable();
        } );
    </script>
</body>
</html>
