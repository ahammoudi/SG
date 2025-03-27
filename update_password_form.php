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
            <button type="submit" class="btn btn-primary" id="submitButton" disabled>Update Passwords</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const submitButton = document.getElementById('submitButton');

            // Function to check if at least one checkbox is selected
            function updateSubmitButtonState() {
                const checkedCheckboxes = document.querySelectorAll('.accountCheckbox:checked');
                submitButton.disabled = checkedCheckboxes.length === 0;
            }

            fetch('get_assets.php')
                .then(response => response.json())
                .then(data => {
                    const assetSelect = document.getElementById('assetSelect');
                    data.forEach(asset => {
                        const option = document.createElement('option');
                        option.value = asset.id;
                        option.textContent = asset.name;
                        assetSelect.appendChild(option);
                    });
                });

            document.getElementById('assetSelect').addEventListener('change', function () {
                const assetId = this.value;
                fetch('get_accounts.php?assetId=' + assetId)
                    .then(response => response.json())
                    .then(data => {
                        const accountsContainer = document.getElementById('accountsContainer');
                        const tableBody = accountsContainer.querySelector('tbody');
                        tableBody.innerHTML = ''; // Clear previous table rows

                        data.forEach(account => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td><input type="checkbox" name="selectedAccounts[]" value="${account.id}" class="accountCheckbox">
                                <input type="hidden" name="accountNames[${account.id}]" value="${account.name}">
                                </td>
                                <td><input type="text" class="form-control" value="${account.name}" disabled></td>
                                <td><input type="password" name="passwords[${account.id}]" class="form-control passwordBox"></td>
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
                                updateSubmitButtonState();
                            });
                        });
                    });
            });
        });
    </script>
</body>
</html>