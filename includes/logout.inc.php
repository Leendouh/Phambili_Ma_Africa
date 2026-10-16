<?php

// include secure session configuration
require_once 'config_session.inc.php';
require_once 'dbh.inc.php';
require_once 'login_model.inc.php';

// The session is already started securely by config_session.inc.php
// session_start(); 

// Delete remember me token if it exists
if (isset($_COOKIE['remember_me'])) {
    $parts = explode(':', $_COOKIE['remember_me']);
    if (count($parts) === 2) {
        $selector = $parts[0];
        // Delete token from database only if PDO connection exists
        if (isset($pdo) && $pdo !== null) {
            delete_remember_me_token($pdo, $selector);
        }
    }
    
    // Robust HTTPS check for cookie security
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
             || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');

    // Clear the cookie
    setcookie('remember_me', '', time() - 3600, '/', '', $isSecure, true);
}

// Free all session variables
session_unset();

// Destroys all data registered to a session
session_destroy();

// Destroy the session cookie to fully log out the user
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// redirect a user back on front page
header("Location: ../login.php");

//stop the script from running 
die();