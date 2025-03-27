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
                        const tableBody = accountsContainer.querySelector('tbody');
                        tableBody.innerHTML = ''; // Clear previous table rows

                        data.forEach(account => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td><input type="checkbox" name="selectedAccounts[]" value="${account.Id}" class="accountCheckbox">
                                <input type="hidden" name="accountNames[${account.Id}]" value="${account.Name}">
                                </td>
                                <td><input type="text" class="form-control" value="${account.Name}" disabled></td>
                                <td><input type="password" name="passwords[${account.Id}]" class="form-control passwordBox"></td>
                            `;
                            tableBody.appendChild(row);
                        });

                        document.querySelectorAll('.accountCheckbox').forEach(checkbox => {
                            checkbox.addEventListener('change', function () {
                                const passwordBox = this.closest('tr').querySelector('.passwordBox');
                                passwordBox.required = this.checked;
                                if (!this.checked) {
                                    passwordBox.value = '';
                                }
                            });
                        });
                    });
            });
        });
    </script>
</body>
</html>