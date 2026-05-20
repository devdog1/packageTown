<?php
require_once "csv_helper.php";

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
        <h2>Manage Speed Profiles</h2>
        <div id="message" class="message" style="display:none;"></div>

        <section>
            <h3 id="formTitle">Add New Profile</h3>
            <form id="profileForm">
                <input type="hidden" id="oldId" name="oldId">
                <div>
                    <label>Profile Name:</label>
                    <input type="text" id="profileName" required>
                </div>
                <button type="submit" id="submitBtn">Add</button>
                <button type="button" id="cancelBtn" style="display:none;">Cancel</button>
            </form>
        </section>

        <section>
            <h3>Existing Profiles</h3>
            <table id="profilesTable" class="display">
                <thead>
                    <tr>
                        <th>Profile ID</th>
                        <th>Profile Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </section>

        <hr>

        <section id="mappingSection" style="display:none;">
            <h3>Manage Packages for Profile: <span id="currentProfileName"></span></h3>
            <form id="addPackageMappingForm">
                <input type="hidden" id="currentProfileId">
                <label>Add Package:</label>
                <select id="packageSelect" required></select>
                <button type="submit">Add to Profile</button>
            </form>
            <table id="profilePackagesTable" class="display">
                <thead>
                    <tr>
                        <th>Package Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </section>
    </main>

    <script>
        $(document).ready(function() {
            // Load packages for dropdown
            fetch('api/packages.php')
                .then(res => res.json())
                .then(packages => {
                    packages.forEach(pkg => {
                        $('#packageSelect').append($('<option>', { value: pkg['Current Plan'], text: pkg['Current Plan'] }));
                    });
                });

            const profilesTable = $('#profilesTable').DataTable({
                ajax: {
                    url: 'api/profiles.php',
                    dataSrc: ''
                },
                columns: [
                    { data: 'profile_id' },
                    { data: 'profile_name' },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <button class="manage-pkgs-btn" data-id="${row['profile_id']}" data-name="${row['profile_name']}">Manage Packages</button>
                                <button class="edit-btn" data-id="${row['profile_id']}">Rename</button>
                                <button class="delete-btn" data-id="${row['profile_id']}">Delete</button>
                            `;
                        }
                    }
                ]
            });

            function showMessage(msg, isError = false) {
                $('#message').text(msg).css('background', isError ? '#f8d7da' : '#d4edda').show();
                setTimeout(() => $('#message').hide(), 3000);
            }

            $('#profileForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#oldId').val();
                const data = { 'profile_name': $('#profileName').val() };
                if (id) data['profile_id'] = id;

                const method = id ? 'PUT' : 'POST';
                const url = id ? `api/profiles.php?id=${encodeURIComponent(id)}` : 'api/profiles.php';

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
                    profilesTable.ajax.reload();
                })
                .catch(err => showMessage(err.message, true));
            });

            $('#profilesTable').on('click', '.edit-btn', function() {
                const data = profilesTable.row($(this).parents('tr')).data();
                $('#oldId').val(data['profile_id']);
                $('#profileName').val(data['profile_name']);
                $('#formTitle').text('Rename Profile');
                $('#submitBtn').text('Update');
                $('#cancelBtn').show();
            });

            $('#profilesTable').on('click', '.delete-btn', function() {
                if (!confirm('Are you sure?')) return;
                const id = $(this).data('id');
                fetch(`api/profiles.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(res => {
                    if (res.error) throw new Error(res.error);
                    showMessage('Deleted successfully');
                    profilesTable.ajax.reload();
                })
                .catch(err => showMessage(err.message, true));
            });

            // Package Mapping logic
            let profilePackagesTable = null;

            $('#profilesTable').on('click', '.manage-pkgs-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                $('#currentProfileId').val(id);
                $('#currentProfileName').text(name);
                $('#mappingSection').show();
                loadProfilePackages(id);
                window.scrollTo(0, document.body.scrollHeight);
            });

            function loadProfilePackages(profileId) {
                if (profilePackagesTable) {
                    profilePackagesTable.destroy();
                }
                profilePackagesTable = $('#profilePackagesTable').DataTable({
                    ajax: {
                        url: `api/mappings.php?type=profile_package`,
                        dataSrc: function(json) {
                            return json.filter(m => m.profile_id === profileId);
                        }
                    },
                    columns: [
                        { data: 'package_id' },
                        {
                            data: null,
                            render: function(data, type, row) {
                                return `<button class="remove-pkg-btn" data-profile="${row.profile_id}" data-package="${row.package_id}">Remove</button>`;
                            }
                        }
                    ]
                });
            }

            $('#addPackageMappingForm').on('submit', function(e) {
                e.preventDefault();
                const profileId = $('#currentProfileId').val();
                const packageId = $('#packageSelect').val();

                fetch('api/mappings.php?type=profile_package', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ profile_id: profileId, package_id: packageId })
                })
                .then(res => res.json())
                .then(() => {
                    showMessage('Package added to profile');
                    profilePackagesTable.ajax.reload();
                })
                .catch(err => showMessage(err.message, true));
            });

            $('#profilePackagesTable').on('click', '.remove-pkg-btn', function() {
                const profileId = $(this).data('profile');
                const packageId = $(this).data('package');
                fetch('api/mappings.php?type=profile_package', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ profile_id: profileId, package_id: packageId })
                })
                .then(res => res.json())
                .then(() => {
                    showMessage('Package removed from profile');
                    profilePackagesTable.ajax.reload();
                })
                .catch(err => showMessage(err.message, true));
            });

            $('#cancelBtn').on('click', resetForm);

            function resetForm() {
                $('#profileForm')[0].reset();
                $('#oldId').val('');
                $('#formTitle').text('Add New Profile');
                $('#submitBtn').text('Add');
                $('#cancelBtn').hide();
            }
        });
    </script>
</body>
</html>
