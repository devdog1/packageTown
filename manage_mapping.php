<?php
require_once "csv_helper.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Node-Profile Mapping</title>
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
                <li><a href="infrastructure_overview.php">Infrastructure</a></li>
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
        <h2>Manage Profile to Node Mapping</h2>
        <div id="message" class="message" style="display:none;"></div>

        <section>
            <h3 id="formTitle">Assign Profile to Node/PON</h3>
            <form id="mappingForm">
                <input type="hidden" id="oldNode">
                <input type="hidden" id="oldProfile">
                <div>
                    <label>Node/PON:</label>
                    <select id="nodeSelect" required></select>
                </div>
                <div>
                    <label>Profile:</label>
                    <select id="profileSelect" required></select>
                </div>
                <button type="submit" id="submitBtn">Assign Mapping</button>
                <button type="button" id="cancelBtn" style="display:none;">Cancel</button>
            </form>
        </section>

        <section>
            <h3>Existing Node-Profile Mappings</h3>
            <table id="mappingsTable" class="display">
                <thead>
                    <tr>
                        <th>Node/PON ID</th>
                        <th>Profile ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </section>
    </main>

    <script>
        $(document).ready(function() {
            // Load nodes and profiles for dropdowns
            fetch('api/nodes.php')
                .then(res => res.json())
                .then(nodes => {
                    nodes.forEach(n => {
                        $('#nodeSelect').append($('<option>', { value: n['node_pon_id'], text: `${n['node_pon_id']} (${n['node_pon_name']})` }));
                    });
                });

            fetch('api/profiles.php')
                .then(res => res.json())
                .then(profiles => {
                    profiles.forEach(p => {
                        $('#profileSelect').append($('<option>', { value: p['profile_id'], text: p['profile_name'] }));
                    });
                });

            const table = $('#mappingsTable').DataTable({
                ajax: {
                    url: 'api/mappings.php?type=node_profile',
                    dataSrc: ''
                },
                columns: [
                    { data: 'node_pon_id' },
                    { data: 'profile_id' },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <button class="edit-btn" data-node="${row.node_pon_id}" data-profile="${row.profile_id}">Edit</button>
                                <button class="delete-btn" data-node="${row.node_pon_id}" data-profile="${row.profile_id}">Remove Mapping</button>
                            `;
                        }
                    }
                ]
            });

            function showMessage(msg, isError = false) {
                $('#message').text(msg).css('background', isError ? '#f8d7da' : '#d4edda').show();
                setTimeout(() => $('#message').hide(), 3000);
            }

            $('#mappingForm').on('submit', function(e) {
                e.preventDefault();
                const oldNode = $('#oldNode').val();
                const oldProfile = $('#oldProfile').val();

                const data = {
                    'node_pon_id': $('#nodeSelect').val(),
                    'profile_id': $('#profileSelect').val()
                };

                if (oldNode && oldProfile) {
                    // Update
                    fetch('api/mappings.php?type=node_profile', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            old: { node_pon_id: oldNode, profile_id: oldProfile },
                            new: data
                        })
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.error) throw new Error(res.error);
                        showMessage('Mapping updated successfully');
                        resetForm();
                        table.ajax.reload();
                    })
                    .catch(err => showMessage(err.message, true));
                } else {
                    // Create
                    fetch('api/mappings.php?type=node_profile', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    })
                    .then(res => res.json())
                    .then(() => {
                        showMessage('Mapping assigned successfully');
                        resetForm();
                        table.ajax.reload();
                    })
                    .catch(err => showMessage(err.message, true));
                }
            });

            $('#mappingsTable').on('click', '.edit-btn', function() {
                const node = $(this).data('node');
                const profile = $(this).data('profile');
                $('#oldNode').val(node);
                $('#oldProfile').val(profile);
                $('#nodeSelect').val(node);
                $('#profileSelect').val(profile);
                $('#formTitle').text('Edit Node-Profile Mapping');
                $('#submitBtn').text('Update Mapping');
                $('#cancelBtn').show();
            });

            $('#mappingsTable').on('click', '.delete-btn', function() {
                if (!confirm('Are you sure?')) return;
                const data = {
                    'node_pon_id': $(this).data('node'),
                    'profile_id': $(this).data('profile')
                };
                fetch('api/mappings.php?type=node_profile', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(res => res.json())
                .then(() => {
                    showMessage('Mapping removed successfully');
                    table.ajax.reload();
                })
                .catch(err => showMessage(err.message, true));
            });

            $('#cancelBtn').on('click', resetForm);

            function resetForm() {
                $('#mappingForm')[0].reset();
                $('#oldNode').val('');
                $('#oldProfile').val('');
                $('#formTitle').text('Assign Profile to Node/PON');
                $('#submitBtn').text('Assign Mapping');
                $('#cancelBtn').hide();
            }
        });
    </script>
</body>
</html>
