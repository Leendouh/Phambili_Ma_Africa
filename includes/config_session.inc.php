<?php

// Load environment variables
require_once __DIR__ . '/env_loader.inc.php';

// Load CSRF protection functions
require_once __DIR__ . '/csrf.inc.php';

// Detect if running on localhost
$isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

// Only configure and start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    // sets the value of a configuraion option for the current session
    ini_set('session.use_only_cookies', '1'); // use only cookies to store session IDs, 1 = true & 0 = false

    // only use strict mode for sessions id that is actually created by the server inside a website
    ini_set('session.use_strict_mode', '1'); // only use strict mode for sessions id that is actually created by the server inside a website, 1 = true & 0 = false

    // Robust HTTPS check for hosted environments behind proxies
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
             || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');

    // cookie parameters for the session cookie to make it more secure
    session_set_cookie_params([

        // life time goes inside a cookie, that after certain time is passed make sure the cookie is destroyed (same cookies running inside a website for too long)
        'lifetime' => 1800, // 1800 seconds = 30 minutes

        // it will only work on the same domain that created the cookie
        // Empty string means current domain - works on both localhost and production
        'domain' => '',

        // path point to the root directory of the website
        'path' => '/', // available within the entire domain

        // secure makes sure that we only running this cookie on the secure website
        // Use false for localhost/HTTP, true for production HTTPS
        'secure' => !$isLocalhost && $isSecure,

        // secure makes sure that the cookie is only accessible through the HTTP protocol and not accessible via JavaScript
        'httponly' => true, // helps mitigate the risk of client side script accessing

        // SameSite setting for CSRF protection - use 'Lax' for better compatibility
        'samesite' => 'Lax',
    ]);

    // start the session
    session_start();
}

// Check for remember me cookie if user is not logged in
if (!isset($_SESSION["user_id"]) && isset($_COOKIE['remember_me'])) {
    require_once 'dbh.inc.php';
    require_once 'login_model.inc.php';

    // Robust HTTPS check for cookie security
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
             || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');

    // Parse the cookie value (selector:validator)
    $parts = explode(':', $_COOKIE['remember_me']);
    if (count($parts) === 2) {
        $selector = $parts[0];
        $validator = $parts[1];

        // Get token from database
        $token = get_remember_me_token($pdo, $selector);

        if ($token) {
            // Verify the validator using hash_equals to prevent timing attacks
            $expected_hash = $token['validator'];
            $provided_hash = hash_validator($validator);

            if (hash_equals($expected_hash, $provided_hash)) {
                // Token is valid, get user from database
                $user = get_user_by_id($pdo, $token['user_id']);

                if ($user && $user['user_type'] === $token['user_type']) {
                    // Log the user in
                    $userType = $user['user_type'];

                    if ($userType === 'admin') {
                        $_SESSION["user_id"] = $user["ID"];
                        $_SESSION["user_email"] = htmlspecialchars($user["Email"]);
                        $_SESSION["user_role"] = 'admin';
                        $_SESSION["user_name"] = htmlspecialchars($user["Full_Name"] ?? 'Administrator');
                        $_SESSION["admin_id"] = $user["ID"];
                        $_SESSION["admin_email"] = htmlspecialchars($user["Email"]);
                        $_SESSION["admin_role"] = $user["Role"];
                        $_SESSION["admin_full_name"] = htmlspecialchars($user["Full_Name"] ?? '');
                    } else {
                        $_SESSION["user_id"] = $user["ID"];
                        $_SESSION["user_email"] = htmlspecialchars($user["Email"]);
                        $_SESSION["user_role"] = 'customer';
                        $_SESSION["user_name"] = htmlspecialchars($user["Full_Name"] ?? 'Customer');
                    }

                    $_SESSION['last_regeneration'] = time();
                    session_regenerate_id(true);

                    // Rotate the validator (generate new one for security)
                    $newValidator = generate_validator();
                    $newHashedValidator = hash_validator($newValidator);

                    if (rotate_remember_me_token($pdo, $selector, $newHashedValidator, 30)) {
                        // Update cookie with new validator
                        $newCookieValue = $selector . ':' . $newValidator;
                        $cookie_expire = time() + (30 * 24 * 60 * 60); // 30 days
                        setcookie('remember_me', $newCookieValue, [
                            'expires' => $cookie_expire,
                            'path' => '/',
                            'domain' => '', // Empty string = current domain (works on localhost & production)
                            'secure' => $isSecure,
                            'httponly' => true,
                            'samesite' => 'Strict'
                        ]);
                    }
                }
            } else {
                // Invalid validator - delete the token for security
                delete_remember_me_token($pdo, $selector);
                setcookie('remember_me', '', time() - 3600, '/', '', $isSecure, true);
            }
        } else {
            // Token not found or expired - clear cookie
            setcookie('remember_me', '', time() - 3600, '/', '', $isSecure, true);
        }
    }
}

// checking if the user is already logged in or not. if yes, regenerate the session id to prevent session fixation attacks
if (isset($_SESSION["user_id"])) {

    // regenerate the current session id we have to better one
    // checking if we have a session variable created using isset function
    if (!isset($_SESSION['last_regeneration'])) {

        // if we don't have a last regeneration time, set it to the current time
        regenerate_session_id_logged_in(); //

    } else {

        // check if 30 minutes have passed since the last regeneration
        $interval = 60 * 30; // 60 sec in 30 minutes in seconds

        if (time() - $_SESSION['last_regeneration'] >= $interval) {

            // calling the function to regenerate the session id
            regenerate_session_id_logged_in(); // regenerate the session id
        }
    }

    // will run when the user is not logged in 
} else {

    // regenerate the current session id we have to better one
    // checking if we have a session variable created using isset function
    if (!isset($_SESSION['last_regeneration'])) {

        // if we don't have a last regeneration time, set it to the current time
        regenerate_session_id(); //

    } else {

        // check if 5 minutes have passed since the last regeneration
        $interval = 60 * 30; // 60 sec in 30 minutes in seconds

        if (time() - $_SESSION['last_regeneration'] >= $interval) {

            // calling the function to regenerate the session id
            regenerate_session_id(); // regenerate the session id
        }
    }
}

// function that will regenerate the session id for logged in users
function regenerate_session_id_logged_in()
{
    // Regenerate the session ID to prevent session fixation attacks.
    // Note: session_id() cannot be called after session_start() to change the ID;
    // session_regenerate_id(true) is the correct and only approach here.
    session_regenerate_id(true); // true will delete the old session file

    // update the last regeneration time to the current time
    $_SESSION['last_regeneration'] = time();
}

// regenerate session id
function regenerate_session_id() 
{
    // if more than 30 mins regerate the session id again 
    session_regenerate_id(true); // true will delete the old session id

    // update the last regeneration time to the current time
    $_SESSION['last_regeneration'] = time(); // set the last regeneration time to the current time
}