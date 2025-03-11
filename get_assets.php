<?php
// Configuration
$config = [
    'ca_bundle' => '/keys/ca-bundle.crt',
    'cert' => '/keys/SG_API.cer',
    'key' => '/keys/SG.APL.key',
    'url' => 'safeguarding.local'
];

/**
 * Make a secure curl request with certificate authentication
 * 
 * @param string $url The URL to request
 * @param array $headers Request headers
 * @return array Response data and status
 */
function curl_request($url, $headers) {
    global $config;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CAINFO => $config['ca_bundle'],
        CURLOPT_SSLCERT => $config['cert'],
        CURLOPT_SSLKEY => $config['key'],
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
 * Fetch all assets from the API
 * 
 * @return array Assets or error message
 */
function getAssets() {
    global $config;
    
    $url = "https://{$config['url']}/service/core/v3/Assets";
    $headers = [
        "Accept: application/json",
        "Content-Type: application/json"
    ];
    
    $response = curl_request($url, $headers);
    return parse_api_response($response, "Failed to retrieve assets");
}

// Process request
header('Content-Type: application/json');

$assets = getAssets();
echo json_encode($assets);
?>
