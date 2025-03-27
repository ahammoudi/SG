<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Account Password</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h2>Update Account Password</h2>
        <form id="passwordUpdateForm" action="process_password_update.php" method="POST">
            <div class="form-group">
                <label for="assetSelect">Select Asset</label>
                <select class="form-control" id="assetSelect" name="assetId" required>
                    <option value="" disabled selected>Select Asset</option>
                    <!-- Options will be populated via JavaScript -->
                </select>
            </div>

            <div id="accountsContainer">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Account Name</th>
                            <th>New Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Account rows will be added here -->
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn btn-primary" id="submitButton">Update Passwords</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const submitButton = document.getElementById('submitButton');

            fetch('/includes/get_assets/')
                .then(response => response.json())
                .then(data => {
                    const assetSelect = document.getElementById('assetSelect');
                    data.forEach(asset => {
                        const option = document.createElement('option');
                        option.value = asset.Id;
                        option.textContent = asset.Name;
                        assetSelect.appendChild(option);
                    });
                });

            document.getElementById('assetSelect').addEventListener('change', function () {
                const assetId = this.value;
                fetch('/includes/get_accounts/?assetId=' + assetId)
                    .then(response => response.json())
                    .then(data => {
                        const accountsContainer = document.getElementById('accountsContainer');
                        accountsContainer.innerHTML = `${data.length} accounts loaded`;
                    });
            });
        });
    </script>
</body>
</html>