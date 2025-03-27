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
            </div>
            <button type="submit" class="btn btn-primary" id="submitButton">Update Passwords</button>
            <button type="button" class="btn btn-secondary" id="downloadCsv">Download CSV</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const submitButton = document.getElementById('submitButton');
            const downloadCsvButton = document.getElementById('downloadCsv'); // Get the button here!
            const assetSelect = document.getElementById('assetSelect');

            let currentAssetId = null; // Store the currently selected asset ID
            let currentAssetName = null; // Store the currently selected asset Name
            let currentAccountData = []; // Store the account data

            // Initially hide the download button
            downloadCsvButton.style.display = 'none';

            fetch('/includes/get_assets/')
                .then(response => response.json())
                .then(data => {
                    data.forEach(asset => {
                        const option = document.createElement('option');
                        option.value = asset.Id;
                        option.textContent = asset.Name;
                        assetSelect.appendChild(option);
                    });
                });

            assetSelect.addEventListener('change', function () {
                currentAssetId = this.value;
                currentAssetName = this.options[this.selectedIndex].text; // Get the asset name

                fetch('/includes/get_accounts/?assetId=' + currentAssetId)
                    .then(response => response.json())
                    .then(data => {
                        const accountsContainer = document.getElementById('accountsContainer');
                        accountsContainer.innerHTML = `${data.length} accounts loaded`;
                        currentAccountData = data; // Store the account data

                        // Show the download button after accounts are loaded
                        downloadCsvButton.style.display = 'inline-block';
                    });
            });

            downloadCsvButton.addEventListener('click', function () {
                if (!currentAssetId) {
                    alert('Please select an asset first.');
                    return;
                }

                // CSV Header
                let csvContent = "Asset Name,Account Name,Account ID,Account Password\r\n";

                // Add CSV rows
                currentAccountData.forEach(account => {
                    csvContent += `"${currentAssetName}","${account.Name}","${account.Id}",""\r\n`; // Blank password
                });

                // Create a download link
                const encodedUri = encodeURI("data:text/csv;charset=utf-8," + csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", `${currentAssetName.replace(/[^a-z0-9]/gi, '_')}_accounts.csv`); // Sanitize filename
                document.body.appendChild(link); // Required for Firefox

                link.click(); // Trigger download

                document.body.removeChild(link); // Clean up
            });
        });
    </script>
</body>

</html>