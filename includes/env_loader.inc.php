<?php
// Simple environment variable loader without Composer dependency

function loadEnv($path = null) {
    if ($path === null) {
        $path = dirname(__DIR__) . '/.env';
    }
    
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            
            // Set environment variable if not already set
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    
    return true;
}

// Load environment variables
loadEnv();

// Helper function to get environment variable
function env($key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key);
    return $value !== false ? $value : $default;
}
