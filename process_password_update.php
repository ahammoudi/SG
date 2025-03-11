<?php
// Configuration
$config = [
    'ca_bundle' => '/ets/pki/tls/certs/ca-bundle.crt',
    'cert' => '/keys/SG_API.cer',
    'key' => '/keys/SG.APL.key',
    'url' => 'safeguarding.local'
];

/**
 * Make a secure curl request with certificate authentication
 * 
 * @param string $url The URL to request
 * @param array $headers Request headers
 * @param string $method HTTP method (GET, POST, etc.)
 * @param array|null $data Data to send with request
 * @return array Response data and status
 */
function curl_request($url, $headers, $method = 'GET', $data = null) {
    global $config;
    
    $ch = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CAINFO => $config['ca_bundle'],
        CURLOPT_SSLCERT => $config['cert'],
        CURLOPT_SSLKEY => $config['key'],
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method
    ];
    
    if ($method === 'POST' && $data !== null) {
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
function parse_api_response($response, $errorMsg = 'API request failed') {
    if ($response['error']) {
        return ['error' => "Connection error: " . $response['error']];
    }
    
    if ($response['status'] < 200 || $response['status'] >= 300) {
        return ['error' => "$errorMsg: HTTP status " . $response['status']];
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
function updatePassword($accountId, $newPassword) {
    global $config;
    
    $url = "https://{$config['url']}/service/core/v3/Accounts/$accountId/Password";
    $headers = [
        "Accept: application/json",
        "Content-Type: application/json"
    ];
    $data = ['Password' => $newPassword];
    
    $response = curl_request($url, $headers, 'POST', $data);
    return parse_api_response($response, "Failed to update password");
}

/**
 * Verify account password
 * 
 * @param string $accountId Account ID to verify
 * @param string $newPassword Password to verify
 * @return bool Whether verification succeeded
 */
function verifyPasswordUpdate($accountId, $newPassword) {
    global $config;
    
    $url = "https://{$config['url']}/service/core/v3/Accounts/$accountId/CheckPassword";
    $headers = [
        "Accept: application/json",
        "Content-Type: application/json"
    ];
    $data = ['Password' => $newPassword];
    
    $response = curl_request($url, $headers, 'POST', $data);
    $result = parse_api_response($response, "Failed to verify password");
    
    return isset($result['Success']) && $result['Success'] === true;
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedAccounts = $_POST['selectedAccounts'] ?? [];
    $passwords = $_POST['passwords'] ?? [];

    $results = [];
    foreach ($selectedAccounts as $accountId) {
        $newPassword = $passwords[$accountId] ?? '';
        if (!empty($newPassword)) {
            // Update the password on Safeguard
            $updateResponse = updatePassword($accountId, $newPassword);
            
            if (!isset($updateResponse['error'])) {
                // Verify the password update
                $verificationSuccess = verifyPasswordUpdate($accountId, $newPassword);
                
                if ($verificationSuccess) {
                    $results[$accountId] = 'Password update and verification successful.';
                } else {
                    $results[$accountId] = 'Password updated but verification failed.';
                }
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
    foreach ($results as $accountId => $result) {
        echo '<li>Account ID ' . htmlspecialchars($accountId) . ': ' . htmlspecialchars($result) . '</li>';
    }
    echo '</ul>';
}
?>