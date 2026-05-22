<?php
// 1. SANDBOX SETTINGS
$username = 'sandbox'; // MUST be 'sandbox'
$apiKey   = 'atsk_8ab9b167f316f3d686dc9947be9047c04873f4738911455636e6d523f13fbcd7e2a450bc'; // Get this from INSIDE the Sandbox App
$phone    = '+256700765387'; 
$message  = 'Sandbox Test: Connection successful!';

// 2. SANDBOX URL
$url = "https://api.sandbox.africastalking.com/version1/messaging"; 

$data = [
    'username' => $username,
    'to'       => $phone,
    'message'  => $message
];

echo "--- STARTING SANDBOX SMS TEST ---\n";

$ch = curl_init($url); 
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($data),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
        'apikey: ' . $apiKey,
    ],
    CURLOPT_RETURNTRANSFER => true,
    
    // THE BYPASS SETTINGS
    CURLOPT_SSL_VERIFYPEER => false, 
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4, // Forces IPv4 (helps on Windows)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "CURL ERROR: " . $error . "\n";
    echo "Tip: Try turning OFF your VPN and running this again.\n";
} else {
    echo "HTTP CODE: " . $httpCode . "\n";
    echo "RAW RESPONSE:\n" . $response . "\n";
}
echo "--- TEST COMPLETE ---\n";