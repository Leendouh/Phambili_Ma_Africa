<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Rate Limiter Debugger</h1>";

try {
    // 1. Include necessary files
    require_once 'includes/config_session.inc.php';
    require_once 'includes/dbh.inc.php';
    require_once 'includes/rate_limiter.inc.php';

    // 2. Check the client IP that the server is detecting
    $clientIP = getClientIP();
    echo "<h3>1. Detected Client IP:</h3>";
    echo "<p><strong>" . htmlspecialchars($clientIP) . "</strong></p>";
    
    // Also dump raw proxy headers to see what the host is sending
    echo "<h4>Raw IP Headers:</h4>";
    echo "<pre>";
    echo "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'Not Set') . "\n";
    echo "HTTP_X_FORWARDED_FOR: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'Not Set') . "\n";
    echo "HTTP_CF_CONNECTING_IP: " . ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'Not Set') . "\n";
    echo "</pre>";

    // 3. Check Database Connection
    if (!$pdo) {
        die("Database connection failed.");
    }

    // 4. Check Raw Database Records for this IP
    echo "<h3>2. Raw Database Records for this IP:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM rate_limits WHERE identifier = ? AND action = 'login' ORDER BY created_at DESC");
    $stmt->execute([$clientIP]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($records)) {
        echo "<p>No failed login attempts found in the database for this IP.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Identifier</th><th>Action</th><th>Created At (MySQL Time)</th></tr>";
        foreach ($records as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['ID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['identifier']) . "</td>";
            echo "<td>" . htmlspecialchars($row['action']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Show MySQL current time for comparison
        $stmtTime = $pdo->query("SELECT NOW() as mysql_time");
        $mysqlTime = $stmtTime->fetch(PDO::FETCH_ASSOC);
        echo "<p><em>Current MySQL Server Time: " . htmlspecialchars($mysqlTime['mysql_time']) . "</em></p>";
    }

    // 4b. Check ALL rate limit records (including session-based)
    echo "<h3>2b. ALL Rate Limit Records (last 20):</h3>";
    $stmtAll = $pdo->prepare("SELECT * FROM rate_limits WHERE action = 'login' ORDER BY created_at DESC LIMIT 20");
    $stmtAll->execute([]);
    $allRecords = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

    if (empty($allRecords)) {
        echo "<p>No rate limit records found.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Identifier</th><th>Action</th><th>Created At (MySQL Time)</th></tr>";
        foreach ($allRecords as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['ID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['identifier']) . "</td>";
            echo "<td>" . htmlspecialchars($row['action']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 4c. Check session-based records specifically
    echo "<h3>2c. Session-based Records:</h3>";
    $sessionId = session_id();
    $stmtSession = $pdo->prepare("SELECT * FROM rate_limits WHERE identifier = ? AND action = 'login' ORDER BY created_at DESC");
    $stmtSession->execute(['session:' . $sessionId]);
    $sessionRecords = $stmtSession->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Current Session ID: " . htmlspecialchars($sessionId) . "</p>";
    if (empty($sessionRecords)) {
        echo "<p>No session-based rate limit records found.</p>";
    } else {
        echo "<p>Found " . count($sessionRecords) . " session-based records:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Identifier</th><th>Action</th><th>Created At (MySQL Time)</th></tr>";
        foreach ($sessionRecords as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['ID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['identifier']) . "</td>";
            echo "<td>" . htmlspecialchars($row['action']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // 5. Check what the Rate Limiter function evaluates
    echo "<h3>3. Rate Limiter Function Evaluation:</h3>";
    $result = checkRateLimit($pdo, $clientIP, 'login', 5, 900);
    echo "<pre>";
    print_r($result);
    echo "</pre>";

    // 6. Test manual record insertion
    echo "<h3>4. Test Manual Record Insertion:</h3>";
    $testInsert = $pdo->prepare("INSERT INTO rate_limits (identifier, action, created_at) VALUES (?, ?, NOW())");
    $insertResult = $testInsert->execute([$clientIP, 'login']);
    echo "<p>Insert result: " . ($insertResult ? "SUCCESS" : "FAILED") . "</p>";

    // 7. Check if record was created
    echo "<h3>5. Verify Record After Insert:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM rate_limits WHERE identifier = ? AND action = 'login' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$clientIP]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($record) {
        echo "<p>Record found: ID=" . htmlspecialchars($record['ID']) . ", Created at: " . htmlspecialchars($record['created_at']) . "</p>";
    } else {
        echo "<p>No record found after insert attempt.</p>";
    }

} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
