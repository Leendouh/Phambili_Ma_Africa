<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/env_loader.inc.php';
require_once 'includes/dbh.inc.php';
require_once 'includes/rate_limiter.inc.php';

echo "<h1>Insert Test</h1>";

try {
    $clientIP = getClientIP();
    echo "<p>Attempting to insert rate limit record for IP: " . htmlspecialchars($clientIP) . "</p>";

    // Directly test the exact same query
    $stmt = $pdo->prepare("
        INSERT INTO rate_limits (identifier, action, created_at)
        VALUES (?, ?, NOW())
    ");
    
    $result = $stmt->execute([$clientIP, 'login']);
    
    if ($result) {
        echo "<p style='color: green;'>Insert SUCCEEDED!</p>";
        echo "<p>Last Insert ID: " . $pdo->lastInsertId() . "</p>";
    } else {
        echo "<p style='color: red;'>Insert FAILED! (execute returned false)</p>";
        echo "<pre>";
        print_r($stmt->errorInfo());
        echo "</pre>";
    }

    // Now query it back
    $check = $pdo->prepare("SELECT * FROM rate_limits WHERE identifier = ? ORDER BY created_at DESC LIMIT 1");
    $check->execute([$clientIP]);
    $row = $check->fetch(PDO::FETCH_ASSOC);

    echo "<h3>Read Back Result:</h3>";
    echo "<pre>";
    print_r($row);
    echo "</pre>";

} catch (Exception $e) {
    echo "<h3>Exception Caught!</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
