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
                    <!-- Options will be populated via JavaScript -->
                </select>
            </div>
            <div id="accountsContainer">
                <!-- Accounts will be populated via JavaScript -->
            </div>
            <button type="submit" class="btn btn-primary">Update Passwords</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
                        accountsContainer.innerHTML = '';
                        data.forEach(account => {
                            const row = document.createElement('div');
                            row.classList.add('form-row', 'mb-3');
                            row.innerHTML = `
                                <div class="col">
                                    <input type="checkbox" name="selectedAccounts[]" value="${account.id}" class="accountCheckbox">
                                </div>
                                <div class="col">
                                    <input type="text" class="form-control" value="${account.name}" disabled>
                                </div>
                                <div class="col">
                                    <input type="password" name="passwords[${account.id}]" class="form-control passwordBox" required>
                                </div>
                            `;
                            accountsContainer.appendChild(row);
                        });

                        document.querySelectorAll('.accountCheckbox').forEach(checkbox => {
                            checkbox.addEventListener('change', function () {
                                const passwordBox = this.closest('.form-row').querySelector('.passwordBox');
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
