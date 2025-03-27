<?php
// oauth.php (OAuth 2.0 handling page)
session_start();

// OAuth 2.0 configuration (replace with your actual values)
$clientId = 'YOUR_CLIENT_ID';
$redirectUri = 'YOUR_REDIRECT_URI'; // Must match registered redirect URI
$authorizationEndpoint = 'AUTHORIZATION_ENDPOINT'; // Example: https://example.com/oauth2/authorize
$tokenEndpoint = 'TOKEN_ENDPOINT'; // Example: https://example.com/oauth2/token
$tokenExpiry = 300; // 5 minutes in seconds

// Function to generate a random state value
function generateState($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Function to generate a random code verifier
function generateCodeVerifier($length = 64) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';
    $codeVerifier = '';
    for ($i = 0; $i < $length; $i++) {
        $codeVerifier .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $codeVerifier;
}

// Function to generate the code challenge from the code verifier
function generateCodeChallenge($codeVerifier) {
    return str_replace('+', '-', str_replace('/', '_', str_replace('=', '', base64_encode(hash('sha256', $codeVerifier, true)))));
}

// Function to handle the OAuth 2.0 flow
function handleOAuth() {
    global $clientId, $redirectUri, $authorizationEndpoint;

    if (!isset($_SESSION['access_token'])) {
        // User is not authenticated, redirect to authorization endpoint
        $state = generateState();
        $_SESSION['oauth_state'] = $state;

        // Generate code verifier and code challenge
        $codeVerifier = generateCodeVerifier();
        $_SESSION['code_verifier'] = $codeVerifier;
        $codeChallenge = generateCodeChallenge($codeVerifier);
        $codeChallengeMethod = 'S256'; // Use S256

        $authorizationUrl = $authorizationEndpoint . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
        ]);

        header('Location: ' . $authorizationUrl);
        exit;
    } else {
        // User is authenticated, redirect to the form page
        header('Location: form.php'); // Redirect to the form page
        exit;
    }
}

// Function to handle the callback from the authorization server
function handleCallback() {
    global $clientId, $redirectUri, $tokenEndpoint, $tokenExpiry;

    if (isset($_GET['code']) && isset($_GET['state'])) {
        if ($_GET['state'] !== $_SESSION['oauth_state']) {
            die('Invalid state');
        }

        $code = $_GET['code'];
        $codeVerifier = $_SESSION['code_verifier']; // Retrieve code verifier

        $tokenUrl = $tokenEndpoint;
        $tokenData = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $clientId,
            'code_verifier' => $codeVerifier, // Include code verifier
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));

        $response = curl_exec($ch);
        curl_close($ch);

        $token = json_decode($response, true);

        if (isset($token['access_token'])) {
            $_SESSION['access_token'] = $token['access_token'];
            $_SESSION['token_expiry'] = time() + $tokenExpiry; // Store expiry time
            unset($_SESSION['oauth_state']); //clear the state
            unset($_SESSION['code_verifier']); // Clear code verifier
            header('Location: form.php'); // Redirect to the form page
            exit;
        } else {
            die('Failed to retrieve access token');
        }
    } else {
        die('Invalid callback');
    }
}

// Main logic
if (isset($_GET['code']) && isset($_GET['state'])) {
    handleCallback();
} else {
    handleOAuth();
}
?>

<?php
// form.php (Form page)
session_start();

// Check if the user is authenticated and token is not expired
if (!isset($_SESSION['access_token']) || (isset($_SESSION['token_expiry']) && time() > $_SESSION['token_expiry'])) {
    unset($_SESSION['access_token']);
    unset($_SESSION['token_expiry']); //clear expiry time
    header('Location: oauth.php'); // Redirect to OAuth page if not authenticated or expired
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Form Submission</title>
</head>
<body>

    <h2>Enter Text</h2>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="textInput">Text:</label><br>
        <input type="text" id="textInput" name="textInput"><br><br>
        <input type="submit" value="Submit">
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $inputText = htmlspecialchars($_POST['textInput']);

        if (!empty($inputText)) {
            echo "<h2>You entered:</h2>";
            echo $inputText;
        } else {
            echo "<h2>Please enter text.</h2>";
        }
    }
    ?>

</body>
</html>