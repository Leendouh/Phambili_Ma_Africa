<?php
// CACHE BUST: 2024-06-30-13-10-00 - Email-based rate limiting

// Start session FIRST - must happen before any $_SESSION usage or CSRF checks
require_once 'config_session.inc.php'; // also loads csrf.inc.php
require_once 'rate_limiter.inc.php';

// check if the user submitted the form correctly
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validate CSRF token
    if (!validateCSRFRequest()) {
        $_SESSION["errors_login"] = ["csrf" => "Security validation failed. Please refresh the page and try again."];
        header("Location: ../login.php");
        die();
    }

    // grab the data from the login form
    $email = $_POST['email'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    // try-catch if query fail
    try {

        // include or require link once
        require_once 'dbh.inc.php'; // database connection file

        // Apply email-based rate limiting (works regardless of IP rotation)
        // Note: IP-based disabled due to IP rotation on this hosting environment
        $clientIP = getClientIP();
        error_log("Login attempt: email=$email, ip=$clientIP");

        $identifiers = [
            'email:' . strtolower(trim($email))  // Email-based works regardless of IP rotation
        ];

        $rateLimitError = null;
        foreach ($identifiers as $identifier) {
            error_log("Checking rate limit for identifier: $identifier");
            $error = enforceRateLimit($pdo, $identifier, 'login', 5, 900);
            if ($error && !$rateLimitError) {
                $rateLimitError = $error; // Capture the first error encountered
                error_log("Rate limit exceeded for identifier: $identifier");
            }
        }

        if ($rateLimitError) {
            $_SESSION["errors_login"]           = ["rate_limit" => $rateLimitError['message']];
            $_SESSION["rate_limit_retry_after"] = $rateLimitError['retry_after'];
            $_SESSION["login_email"]            = $email; // keep email pre-filled during lockout
            header("Location: ../login.php");
            die();
        }

        require_once 'login_model.inc.php'; // model file (includes both customer and admin functions)
        require_once 'login_contr.inc.php'; // controller file (includes both customer and admin validation)

        // array for storing errors
        $errors = []; // contain any errors found during login validation

        // checking if every input are filled out,
        if (is_input_empty($email, $password)) {

            // if any input is empty, add an error message to the errors array
            $errors["empty_input"] = "All fields are required.";
        }

        // grab the data from either customers or admins table
        $result = get_user_unified($pdo, $email);

        // checking if email address is wrong by running a function and pass if we did habe result inside the databae
        if (is_email_wrong($result)) {

            // if email is wrong, add an error message to the errors array
            $errors["login_incorrect"] = "Incorrect email address.";
        }

        // check if email is not wrong and password is incorrect
        // Defensive: handle case-sensitive column name variations from PDO
        $storedHash = $result["Password"] ?? $result["password"] ?? null;

        $passwordWrong = empty($storedHash) || !is_string($storedHash);
        if (!$passwordWrong && !is_email_wrong($result)) {
            $passwordWrong = is_password_wrong($password, $storedHash);
        }

        if (!is_email_wrong($result) && $passwordWrong) {
            // if password is wrong, add an error message to the errors array
            $errors["login_incorrect"] = "Incorrect password.";
        }

        // Session already started at the top of this file

        // if there are any errors, store them in the session and redirect back to the login page
        if (!empty($errors)) {

            // Record this as a failed login attempt against email
            foreach ($identifiers as $identifier) {
                $result = recordRateLimitAttempt($pdo, $identifier, 'login');
                error_log("Rate limit record attempt: identifier=$identifier, result=" . ($result ? 'SUCCESS' : 'FAILED'));
            }

            // store the errors in the session to display them on the login page
            $_SESSION["errors_login"] = $errors;

            // store the submitted email to repopulate the form
            $_SESSION["login_email"] = $email;

            // redirect back to the login page
            header("Location: ../login.php");

            // terminate the script
            die();
        }

        // Determine user type and handle accordingly
        $userType = $result['user_type'] ?? 'customer';

        // Set remember me cookie if checked using secure selector/validator system
        if ($remember) {
            // Generate random selector and validator
            $selector = generate_selector();
            $validator = generate_validator();
            $hashedValidator = hash_validator($validator);

            // Store token in database
            if (create_remember_me_token($pdo, $result["ID"], $userType, $selector, $hashedValidator, 30)) {
                // Set cookie with selector:validator format
                $cookie_value = $selector . ':' . $validator;
                $cookie_expire = time() + (30 * 24 * 60 * 60); // 30 days
                setcookie('remember_me', $cookie_value, [
                    'expires' => $cookie_expire,
                    'path' => '/',
                    'domain' => '', // Empty string = current domain (works on localhost & production)
                    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            }
        }

        if ($userType === 'admin') {
            // Clear failed login attempts on successful login
            foreach ($identifiers as $identifier) {
                clearRateLimitAttempts($pdo, $identifier, 'login');
            }

            // Store admin data in session using standard variable names
            $_SESSION["user_id"] = $result["ID"];
            $_SESSION["user_email"] = htmlspecialchars($result["Email"]);
            $_SESSION["user_role"] = 'admin';
            $_SESSION["user_name"] = htmlspecialchars($result["Full_Name"] ?? 'Administrator');

            // Keep admin-specific variables for backward compatibility
            $_SESSION["admin_id"] = $result["ID"];
            $_SESSION["admin_email"] = htmlspecialchars($result["Email"]);
            $_SESSION["admin_role"] = $result["Role"];
            $_SESSION["admin_full_name"] = htmlspecialchars($result["Full_Name"] ?? '');

            // regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();

            // redirect to admin dashboard
            header("Location: ../admin_dashboard.php");

        } else {
            // Clear failed login attempts on successful login
            foreach ($identifiers as $identifier) {
                clearRateLimitAttempts($pdo, $identifier, 'login');
            }

            // Store customer data in session
            $_SESSION["user_id"] = $result["ID"];
            $_SESSION["user_email"] = htmlspecialchars($result["Email"]);
            $_SESSION["user_role"] = 'customer';
            $_SESSION["user_name"] = htmlspecialchars($result["Full_Name"] ?? 'Customer');

            // regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();

            // redirect to stored redirect URL or home page after successful login
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: ../" . $redirect);
            } else {
                header("Location: ../index.php");
            }
        }

        // close connection and statement
        $pdo = null; // close the database connection
        $stmt = null; // close the statement

        // terminate the script
        die();

        // catch any PDO exceptions
    } catch (PDOException $e) {

        // if there is an error, display the error message
        die("Query failed: " . $e->getMessage());
    }

} else {

// if the user did not submit the form correctly, redirect to the login page
    header("Location: ../login.php");

    // terminate the script
    die();
}