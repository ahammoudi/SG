<?php
session_start(); // Start the session

// Generate a new secret key on each page load
$_SESSION['secret_key'] = bin2hex(random_bytes(32));
$secretKey = $_SESSION['secret_key'];

?>
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
        <p>
            For assistance with updating passwords, refer to the
            <a href="[Documentation Link Here]" target="_blank">Documentation</a>.
        </p>
        <form id="passwordUpdateForm" action="process_password_update.php" method="POST">
            <input type="hidden" name="secret_key" value="<?php echo htmlspecialchars($secretKey, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group" style="width: 50%;">
                <label for="assetSelect">Select Asset</label>
                <select class="form-control" id="assetSelect" name="assetId" required>
                    <option value="" disabled selected>Select Asset</option>
                    <!-- Options will be populated via JavaScript -->
                </select>
            </div>
            <div id="accountsContainer">
            </div>
            <button type="submit" class="btn btn-primary" id="submitButton" disabled>Update Passwords</button>
            <button type="button" class="btn btn-secondary" id="downloadCsv">Download CSV</button>
            <input type="file" id="uploadCsv" accept=".csv" style="display: none;">
            <button type="button" class="btn btn-info" id="uploadCsvButton">Upload CSV</button>
        </form>
    </div>

    <div id="customAlert" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: white; padding: 20px; border: 1px solid black; z-index: 1000;">
        <p id="customAlertMessage"></p>
        <button id="customAlertButton">OK</button>
    </div>
    <div id="alertOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999;"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const submitButton = document.getElementById('submitButton');
            const downloadCsvButton = document.getElementById('downloadCsv');
            const assetSelect = document.getElementById('assetSelect');
            const uploadCsvButton = document.getElementById('uploadCsvButton');
            const uploadCsv = document.getElementById('uploadCsv');

            let currentAssetId = null; // Store the currently selected asset ID
            let currentAssetName = null; // Store the currently selected asset Name
            let currentAccountData = []; // Store the account data
            let uploadedAccountData = []; // Store the uploaded account data

            // Initially hide the download button
            downloadCsvButton.style.display = 'none';
            uploadCsvButton.disabled = true; // Disable upload button initially

            // Define secretKey in the global scope
            const secretKey = "<?php echo htmlspecialchars($secretKey, ENT_QUOTES, 'UTF-8'); ?>";

            fetch('/includes/get_assets/?secret_key=' + secretKey)
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

                // Enable upload button when asset is selected
                uploadCsvButton.disabled = false;

                // Clear previously uploaded data
                uploadedAccountData = [];

                // Disable the submit button
                submitButton.disabled = true;

                // Clear existing hidden input fields
                let form = document.getElementById('passwordUpdateForm');
                while (form.firstChild) {
                    form.removeChild(form.firstChild);
                }

                // Add back the secret key
                let secretKeyInput = document.createElement('input');
                secretKeyInput.type = 'hidden';
                secretKeyInput.name = 'secret_key';
                secretKeyInput.value = secretKey;
                form.appendChild(secretKeyInput);

                fetch('/includes/get_accounts/?assetId=' + currentAssetId + '&secret_key=' + secretKey)
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

            uploadCsvButton.addEventListener('click', () => {
                uploadCsv.click(); // Trigger the file input
            });

            uploadCsv.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const csv = e.target.result;
                        const parsedData = parseCSV(csv);

                        // Verify headers
                        if (!verifyCSVHeaders(parsedData[0])) {
                            customAlert('CSV file is missing required headers: Asset Name, Account Name, Account ID, Account Password');
                            uploadedAccountData = []; // Clear any previously stored data
                            return;
                        }

                        // Verify Asset Name
                        if (!verifyAssetNames(parsedData, currentAssetName)) {
                            customAlert('CSV file contains Asset Names that do not match the selected Asset.');
                            uploadedAccountData = []; // Clear any previously stored data
                            return;
                        }

                        uploadedAccountData = parsedData;
                        customAlert('CSV file uploaded and parsed. Data stored in uploadedAccountData.');

                        // Create hidden input fields for the data
                        let form = document.getElementById('passwordUpdateForm');

                        // Add asset ID and name as hidden inputs
                        let assetIdInput = document.createElement('input');
                        assetIdInput.type = 'hidden';
                        assetIdInput.name = 'assetId';
                        assetIdInput.value = currentAssetId;
                        form.appendChild(assetIdInput);

                        let assetNameInput = document.createElement('input');
                        assetNameInput.type = 'hidden';
                        assetNameInput.name = 'assetName';
                        assetNameInput.value = currentAssetName;
                        form.appendChild(assetNameInput);

                        // Add account data as hidden inputs
                        uploadedAccountData.forEach((account, index) => {
                            let accountNameInput = document.createElement('input');
                            accountNameInput.type = 'hidden';
                            accountNameInput.name = `accountNames[${index}]`;
                            accountNameInput.value = account['Account Name'];
                            form.appendChild(accountNameInput);

                            let accountIdInput = document.createElement('input');
                            accountIdInput.type = 'hidden';
                            accountIdInput.name = `accountIds[${index}]`;
                            accountIdInput.value = account['Account ID'];
                            form.appendChild(accountIdInput);

                            let accountPasswordInput = document.createElement('input');
                            accountPasswordInput.type = 'hidden';
                            accountPasswordInput.name = `accountPasswords[${index}]`;
                            accountPasswordInput.value = account['Account Password'];
                            form.appendChild(accountPasswordInput);
                        });

                        // Enable the submit button
                        submitButton.disabled = false;

                        // Disable the upload button
                        uploadCsvButton.disabled = true;
                    };
                    reader.readAsText(file);
                }
            });

            function parseCSV(csv) {
                const lines = csv.split('\r\n');
                const filteredLines = lines.filter(line => line.trim() !== ''); // Filter out empty lines
                const headers = filteredLines[0].split(',');
                const result = [];

                for (let i = 1; i < filteredLines.length; i++) {
                    const obj = {};
                    const currentLine = filteredLines[i].split(',');

                    for (let j = 0; j < headers.length; j++) {
                        obj[headers[j].trim()] = currentLine[j] ? currentLine[j].trim() : '';
                    }
                    result.push(obj);
                }
                return result;
            }

            function verifyCSVHeaders(firstRow) {
                const requiredHeaders = ['Asset Name', 'Account Name', 'Account ID', 'Account Password'];
                for (const header of requiredHeaders) {
                    if (!firstRow.hasOwnProperty(header)) {
                        return false;
                    }
                }
                return true;
            }

            function verifyAssetNames(data, currentAssetName) {
                for (let i = 1; i < data.length; i++) { // Start from 1 to skip the header row
                    if (data[i]['Asset Name'].replace(/"/g, '') !== currentAssetName) {
                        return false;
                    }
                }
                return true;
            }

            function customAlert(message) {
                const alertBox = document.getElementById('customAlert');
                const alertMessage = document.getElementById('customAlertMessage');
                const alertButton = document.getElementById('customAlertButton');
                const overlay = document.getElementById('alertOverlay');

                alertMessage.textContent = message;
                alertBox.style.display = 'block';
                overlay.style.display = 'block';

                alertButton.onclick = function() {
                    alertBox.style.display = 'none';
                    overlay.style.display = 'none';
                };
            }
        });
    </script>
</body>

</html>