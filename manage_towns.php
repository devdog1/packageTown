<?php
require_once "csv_helper.php";

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
        <div id="message" class="message" style="display:none;"></div>

        <section>
            <h3 id="formTitle">Add New Town/City</h3>
            <form id="townForm">
                <input type="hidden" id="oldId" name="oldId">
                <div>
                    <label>Geographic Area:</label>
                    <input type="text" id="geographicArea" required>
                </div>
                <div>
                    <label>2LA:</label>
                    <input type="text" id="la2">
                </div>
                <div>
                    <label>3LA:</label>
                    <input type="text" id="la3">
                </div>
                <div>
                    <label>CLLI:</label>
                    <input type="text" id="clli">
                </div>
                <div>
                    <label>Location (URL):</label>
                    <input type="text" id="location">
                </div>
                <button type="submit" id="submitBtn">Add</button>
                <button type="button" id="cancelBtn" style="display:none;">Cancel</button>
            </form>
        </section>

        <section>
            <h3>Existing Towns and Cities</h3>
            <table id="townsTable" class="display">
                <thead>
                    <tr>
                        <th>Geographic Area</th>
                        <th>2LA</th>
                        <th>3LA</th>
                        <th>CLLI</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </section>
    </main>

    <script>
        $(document).ready(function() {
            const table = $('#townsTable').DataTable({
                ajax: {
                    url: 'api/towns.php',
                    dataSrc: ''
                },
                columns: [
                    { data: 'Geographic Area' },
                    { data: '2LA' },
                    { data: '3LA' },
                    { data: 'CLLI' },
                    {
                        data: 'Location',
                        render: function(data) {
                            return data ? `<a href="${data}" target="_blank">View Map</a>` : '';
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <button class="edit-btn" data-id="${row['Geographic Area']}">Edit</button>
                                <button class="delete-btn" data-id="${row['Geographic Area']}">Delete</button>
                            `;
                        }
                    }
                ]
            });

            function showMessage(msg, isError = false) {
                $('#message').text(msg).css('background', isError ? '#f8d7da' : '#d4edda').show();
                setTimeout(() => $('#message').hide(), 3000);
            }

            $('#townForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#oldId').val();
                const data = {
                    'Geographic Area': $('#geographicArea').val(),
                    '2LA': $('#la2').val(),
                    '3LA': $('#la3').val(),
                    'CLLI': $('#clli').val(),
                    'Location': $('#location').val()
                };

                const method = id ? 'PUT' : 'POST';
                const url = id ? `api/towns.php?id=${encodeURIComponent(id)}` : 'api/towns.php';

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

            $('#townsTable').on('click', '.edit-btn', function() {
                const data = table.row($(this).parents('tr')).data();
                $('#oldId').val(data['Geographic Area']);
                $('#geographicArea').val(data['Geographic Area']);
                $('#la2').val(data['2LA']);
                $('#la3').val(data['3LA']);
                $('#clli').val(data['CLLI']);
                $('#location').val(data['Location']);
                $('#formTitle').text('Edit Town/City');
                $('#submitBtn').text('Update');
                $('#cancelBtn').show();
            });

            $('#townsTable').on('click', '.delete-btn', function() {
                if (!confirm('Are you sure?')) return;
                const id = $(this).data('id');
                fetch(`api/towns.php?id=${encodeURIComponent(id)}`, { method: 'DELETE' })
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
                $('#townForm')[0].reset();
                $('#oldId').val('');
                $('#formTitle').text('Add New Town/City');
                $('#submitBtn').text('Add');
                $('#cancelBtn').hide();
            }
        });
    </script>
</body>
</html>
