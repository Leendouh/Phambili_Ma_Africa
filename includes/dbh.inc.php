<?php

// Load environment variables
require_once __DIR__ . '/env_loader.inc.php';

// connecting to database

// data source name, which contains the host and database name
$dsn = "mysql:host=" . env('DB_HOST') . "; dbname=" . env('DB_NAME');

// database user name which is root
$dbusername = env('DB_USERNAME');

// database user password which is blank
$dbpassword = env('DB_PASSWORD');

// try-catch block to catch any errors
try {

    // creating a new PDO instance, which represents a connection to the database
    $pdo = new PDO($dsn, $dbusername, $dbpassword);

    // setting the error mode to exception, so that we can handle errors properly
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    
} catch (PDOException $e) {
    // Check if this is an API request (JSON expected)
    $isApiRequest = strpos($_SERVER['REQUEST_URI'], '/api/') !== false ||
                    (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isApiRequest) {
        // Return JSON error for API requests (show detailed message for localhost debugging)
        $isLocalhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost']);
        $errorMessage = $isLocalhost ? 'Database error: ' . $e->getMessage() : 'Database connection failed. Please try again later.';
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    } else {
        // Display error for regular page requests
        echo "Connection failed: " . $e->getMessage();
    }

    exit(); // terminate the script
}
