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
 * @return array Response data and status
 */
function curl_request($url, $headers) {
    global $config;
    
    // Add authorization header with bearer token if available
    if (isset($_SESSION['access_token']) && !empty($_SESSION['access_token'])) {
        $headers[] = "Authorization: Bearer " . $_SESSION['access_token'];
    } else {
        return ['data' => null, 'status' => 0, 'error' => 'No access token available'];
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => $headers
    ]);
    
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
 * Fetch accounts for the selected asset
 * 
 * @param string $assetId Asset ID to fetch accounts for
 * @return array Accounts or error message
 */
function getAccounts($assetId) {
    global $config;
    
    $url = "https://{$config['url']}/service/core/v3/Assets/$assetId/Accounts";
    $headers = [
        "Accept: application/json",
        "Content-Type: application/json"
    ];
    
    $response = curl_request($url, $headers);
    return parse_api_response($response, "Failed to retrieve accounts");
}

// Process request
header('Content-Type: application/json');

$assetId = $_GET['assetId'] ?? '';
if (empty($assetId)) {
    echo json_encode(['error' => 'Asset ID is required']);
    exit;
}

$accounts = getAccounts($assetId);
echo json_encode($accounts);
?>