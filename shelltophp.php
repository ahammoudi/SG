<?php

// Configuration
$config = [
    'user_id' => 'user1',
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
 * @return mixed Parsed JSON or exits with error
 */
function parse_api_response($response, $errorMsg = 'API request failed') {
    if ($response['error']) {
        exit_with_error("Connection error: " . $response['error']);
    }
    
    if ($response['status'] < 200 || $response['status'] >= 300) {
        exit_with_error("$errorMsg: HTTP status " . $response['status']);
    }
    
    $data = json_decode($response['data'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        exit_with_error("$errorMsg: Invalid JSON response");
    }
    
    return $data;
}

/**
 * Exit with error message
 * 
 * @param string $message Error message
 * @param int $code Exit code
 */
function exit_with_error($message, $code = 1) {
    echo $message . PHP_EOL;
    exit($code);
}

/**
 * Retrieve password using API key
 * 
 * @param string $apiKey The API key
 */
function retrievePassword($apiKey) {
    global $config;
    
    $url = "https://{$config['url']}/service/a2a/v3/Credentials?type=Password";
    $headers = [
        "Authorization: A2A $apiKey",
        "Accept: application/json",
        "Content-Type: application/json"
    ];
    
    $response = curl_request($url, $headers);
    $data = parse_api_response($response, "Password retrieval failed");
    
    if (empty($data) || !isset($data['Password'])) {
        exit_with_error("{$config['user_id']} has no password");
    }
    
    echo $data['Password'] . PHP_EOL;
    exit(0);
}

/**
 * Verify user and retrieve API key
 * 
 * @param string $registrationId The registration ID
 */
function verifyUser($registrationId) {
    global $config;
    
    $url = "https://{$config['url']}/service/core/v3/A2ARegistrations/$registrationId/RetrievableAccounts";
    $headers = [
        "Accept: application/json",
        "Content-Type: application/json"
    ];
    
    $response = curl_request($url, $headers);
    $accounts = parse_api_response($response, "User verification failed");
    
    // Check if user exists and extract API key
    foreach ($accounts as $account) {
        if (isset($account['AccountName']) && $account['AccountName'] === $config['user_id']) {
            if (isset($account['ApiKey'])) {
                retrievePassword($account['ApiKey']);
                return;
            }
            exit_with_error("API key not found for user {$config['user_id']}");
        }
    }
    
    // User not found, display supported users
    echo "User {$config['user_id']} not supported by this certificate\n";
    echo "Below is the supported list of users:\n";
    
    foreach ($accounts as $account) {
        if (isset($account['AccountName'])) {
            echo $account['AccountName'] . "\n";
        }
    }
    
    exit(1);
}

/**
 * Get registration ID from certificate
 */
function getRegistrationID() {
    global $config;
    
    $url = "https://{$config['url']}/service/core/v3/A2ARegistrations";
    $headers = [
        "Accept: application/json",
        "Content-Type: application/json"
    ];
    
    $response = curl_request($url, $headers);
    $registrations = parse_api_response($response, "Registration ID retrieval failed");
    
    if (empty($registrations)) {
        exit_with_error("No registrations found");
    }
    
    $registration = $registrations[0];
    
    if (isset($registration['Disabled']) && $registration['Disabled'] === true) {
        exit_with_error("Certificate is disabled");
    }
    
    if (!isset($registration['Id'])) {
        exit_with_error("Registration ID not found");
    }
    
    verifyUser($registration['Id']);
}

// Main execution
getRegistrationID();

?>
