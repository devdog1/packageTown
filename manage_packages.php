<?php
require_once "csv_helper.php";

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
        <h2>Manage Internet Speed Packages</h2>
        <div id="message" class="message" style="display:none;"></div>

        <section>
            <h3 id="formTitle">Add New Speed Package</h3>
            <form id="packageForm">
                <input type="hidden" id="oldId" name="oldId">
                <div>
                    <label>State:</label>
                    <input type="text" id="state">
                </div>
                <div>
                    <label>Current Plan Name:</label>
                    <input type="text" id="planName" required>
                </div>
                <div>
                    <label>CSG CODE(s):</label>
                    <input type="text" id="csgCode">
                </div>
                <div>
                    <label>Download Speed:</label>
                    <input type="text" id="downloadSpeed">
                </div>
                <div>
                    <label>Upload Speed:</label>
                    <input type="text" id="uploadSpeed">
                </div>
                <div>
                    <label>Provisioning System Name:</label>
                    <input type="text" id="systemName">
                </div>
                <button type="submit" id="submitBtn">Add</button>
                <button type="button" id="cancelBtn" style="display:none;">Cancel</button>
            </form>
        </section>

        <section>
            <h3>Existing Speed Packages</h3>
            <table id="packagesTable" class="display">
                <thead>
                    <tr>
                        <th>State</th>
                        <th>Current Plan</th>
                        <th>CSG CODE</th>
                        <th>Download</th>
                        <th>Upload</th>
                        <th>System Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </section>
    </main>

    <script>
        $(document).ready(function() {
            const table = $('#packagesTable').DataTable({
                ajax: {
                    url: 'api/packages.php',
                    dataSrc: ''
                },
                columns: [
                    { data: 'State' },
                    { data: 'Current Plan' },
                    { data: 'CSG CODE' },
                    { data: 'Download Speed' },
                    { data: 'Upload Speed' },
                    { data: 'Provisioning System Name' },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <button class="edit-btn" data-id="${row['Current Plan']}">Edit</button>
                                <button class="delete-btn" data-id="${row['Current Plan']}">Delete</button>
                            `;
                        }
                    }
                ]
            });

            function showMessage(msg, isError = false) {
                $('#message').text(msg).css('background', isError ? '#f8d7da' : '#d4edda').show();
                setTimeout(() => $('#message').hide(), 3000);
            }

            $('#packageForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#oldId').val();
                const data = {
                    'State': $('#state').val(),
                    'Current Plan': $('#planName').val(),
                    'CSG CODE': $('#csgCode').val(),
                    'Download Speed': $('#downloadSpeed').val(),
                    'Upload Speed': $('#uploadSpeed').val(),
                    'Provisioning System Name': $('#systemName').val()
                };

                const method = id ? 'PUT' : 'POST';
                const url = id ? `api/packages.php?id=${encodeURIComponent(id)}` : 'api/packages.php';

                fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(res => res.json())
                .then(res => {
                    if (res.error) throw new Error(res.error);
                    showMessage(id ? 'Updated successfully' : 'Added successfully');
                    resetForm();
                    table.ajax.reload();
                })
                .catch(err => showMessage(err.message, true));
            });

            $('#packagesTable').on('click', '.edit-btn', function() {
                const data = table.row($(this).parents('tr')).data();
                $('#oldId').val(data['Current Plan']);
                $('#state').val(data['State']);
                $('#planName').val(data['Current Plan']);
                $('#csgCode').val(data['CSG CODE']);
                $('#downloadSpeed').val(data['Download Speed']);
                $('#uploadSpeed').val(data['Upload Speed']);
                $('#systemName').val(data['Provisioning System Name']);
                $('#formTitle').text('Edit Speed Package');
                $('#submitBtn').text('Update');
                $('#cancelBtn').show();
            });

            $('#packagesTable').on('click', '.delete-btn', function() {
                if (!confirm('Are you sure?')) return;
                const id = $(this).data('id');
                fetch(`api/packages.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(res => {
                    if (res.error) throw new Error(res.error);
                    showMessage('Deleted successfully');
                    table.ajax.reload();
                })
                .catch(err => showMessage(err.message, true));
            });

            $('#cancelBtn').on('click', resetForm);

            function resetForm() {
                $('#packageForm')[0].reset();
                $('#oldId').val('');
                $('#formTitle').text('Add New Speed Package');
                $('#submitBtn').text('Add');
                $('#cancelBtn').hide();
            }
        });
    </script>
</body>
</html>
