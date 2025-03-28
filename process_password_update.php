<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration
$config = [
    'url' => 'safeguarding.local'
];

/**
 * Make a curl request with bearer token authentication
 *
 * @param string $url The URL to request
 * @param array $headers Request headers
 * @param string $method HTTP method (GET, POST, etc.)
 * @param array|null $data Data to send with request
 * @return array Response data and status
 */
function curl_request($url, $headers, $method = 'GET', $data = null)
{
    global $config;

    // Add authorization header with bearer token if available
    if (isset($_SESSION['access_token']) && !empty($_SESSION['access_token'])) {
        $headers[] = "Authorization: Bearer " . $_SESSION['access_token'];
    } else {
        return ['data' => null, 'status' => 0, 'error' => 'No access token available'];
    }

    $ch = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method
    ];

    if (($method === 'POST' || $method === 'PUT') && $data !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    curl_setopt_array($ch, $options);

    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return ['data' => $result, 'status' => $status, 'error' => $error];
}

/**
 * Parse and return API response as JSON with error handling
 *
 * @param array $response The curl response
 * @param string $errorMsg Custom error message
 * @return mixed Parsed JSON or null with error message
 */
function parse_api_response($response, $errorMsg = 'API request failed')
{
    if ($response['error']) {
        return ['error' => "Connection error: " . $response['error']];
    }

    if ($response['status'] < 200 || $response['status'] >= 300) {
        return ['error' => "$errorMsg: HTTP status " . $response['status']];
    }

    // Check if the response data is empty
    if (empty($response['data'])) {
        return ['success' => 'Password updated successfully'];
    }

    $data = json_decode($response['data'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => "$errorMsg: Invalid JSON response"];
    }

    return $data;
}

/**
 * Update account password
 *
 * @param string $accountId Account ID to update
 * @param string $newPassword New password
 * @return array Result of update operation
 */
function updatePassword($accountId, $newPassword)
{
    global $config;

    $url = "https://{$config['url']}/service/core/v3/Accounts/$accountId/Password";
    $headers = [
        "Accept: application/json",
        "Content-Type: application/json"
    ];
    $data = $newPassword;

    $response = curl_request($url, $headers, 'PUT', $data);
    return parse_api_response($response, "Failed to update password");
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assetId = $_POST['assetId'] ?? null;
    $assetName = $_POST['assetName'] ?? null;
    $accountNames = $_POST['accountNames'] ?? [];
    $accountIds = $_POST['accountIds'] ?? [];
    $accountPasswords = $_POST['accountPasswords'] ?? [];

    $results = [];
    foreach ($accountIds as $index => $accountId) {
        $accountName = $accountNames[$index] ?? 'Unknown Account';
        $newPassword = $accountPasswords[$index] ?? '';

        if (!empty($newPassword)) {
            // Update the password on Safeguard
            $updateResponse = updatePassword($accountId, $newPassword);

            if (!isset($updateResponse['error'])) {
                $results[$accountId] = 'Password update successful.';
            } else {
                $results[$accountId] = 'Password update failed: ' . $updateResponse['error'];
            }
        } else {
            $results[$accountId] = 'No password provided.';
        }
    }

    // Output the results
    echo '<h2>Update Results</h2>';
    echo '<ul>';
    foreach ($accountIds as $index => $accountId) {
        $accountName = htmlspecialchars($accountNames[$index] ?? 'Unknown Account'); // Get account name for display
        $resultMessage = htmlspecialchars($results[$accountId]);
        $color = (strpos($resultMessage, 'successful') !== false) ? 'green' : 'red';
        echo '<li>Account Name: ' . $accountName . ': <span style="color: ' . $color . ';">' . $resultMessage . '</span></li>';
    }
    echo '</ul>';
}
?>