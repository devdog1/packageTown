<?php
require_once "csv_helper.php";

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
        <h2>Manage Nodes and PONs</h2>
        <div id="message" class="message" style="display:none;"></div>

        <section>
            <h3 id="formTitle">Add New Node/PON</h3>
            <form id="nodeForm">
                <input type="hidden" id="oldId" name="oldId">
                <div>
                    <label>City:</label>
                    <select id="city" required></select>
                </div>
                <div>
                    <label>Node/PON ID:</label>
                    <input type="text" id="nodeId" required>
                </div>
                <div>
                    <label>Node/PON Name:</label>
                    <input type="text" id="nodeName" required>
                </div>
                <div>
                    <label>Type:</label>
                    <select id="type" required>
                        <option value="docsis">Docsis</option>
                        <option value="fiber">Fiber</option>
                    </select>
                </div>
                <button type="submit" id="submitBtn">Add</button>
                <button type="button" id="cancelBtn" style="display:none;">Cancel</button>
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
                <tbody></tbody>
            </table>
        </section>
    </main>

    <script>
        $(document).ready(function() {
            // Load cities for dropdown
            fetch('api/towns.php')
                .then(res => res.json())
                .then(towns => {
                    towns.forEach(t => {
                        $('#city').append($('<option>', { value: t['Geographic Area'], text: t['Geographic Area'] }));
                    });
                });

            const table = $('#nodesTable').DataTable({
                ajax: {
                    url: 'api/nodes.php',
                    dataSrc: ''
                },
                columns: [
                    { data: 'city' },
                    { data: 'node_pon_id' },
                    { data: 'node_pon_name' },
                    { data: 'type' },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <button class="edit-btn" data-id="${row['node_pon_id']}">Edit</button>
                                <button class="delete-btn" data-id="${row['node_pon_id']}">Delete</button>
                            `;
                        }
                    }
                ]
            });

            function showMessage(msg, isError = false) {
                $('#message').text(msg).css('background', isError ? '#f8d7da' : '#d4edda').show();
                setTimeout(() => $('#message').hide(), 3000);
            }

            $('#nodeForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#oldId').val();
                const data = {
                    'city': $('#city').val(),
                    'node_pon_id': $('#nodeId').val(),
                    'node_pon_name': $('#nodeName').val(),
                    'type': $('#type').val()
                };

                const method = id ? 'PUT' : 'POST';
                const url = id ? `api/nodes.php?id=${encodeURIComponent(id)}` : 'api/nodes.php';

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

            $('#nodesTable').on('click', '.edit-btn', function() {
                const data = table.row($(this).parents('tr')).data();
                $('#oldId').val(data['node_pon_id']);
                $('#city').val(data['city']);
                $('#nodeId').val(data['node_pon_id']);
                $('#nodeName').val(data['node_pon_name']);
                $('#type').val(data['type']);
                $('#formTitle').text('Edit Node/PON');
                $('#submitBtn').text('Update');
                $('#cancelBtn').show();
            });

            $('#nodesTable').on('click', '.delete-btn', function() {
                if (!confirm('Are you sure?')) return;
                const id = $(this).data('id');
                fetch(`api/nodes.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' })
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
                $('#nodeForm')[0].reset();
                $('#oldId').val('');
                $('#formTitle').text('Add New Node/PON');
                $('#submitBtn').text('Add');
                $('#cancelBtn').hide();
            }
        });
    </script>
</body>
</html>
