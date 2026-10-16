<?php 

// check if the user submitted the form correctly
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // fetch the data from the user
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // try-catch if the query fails
    try {

        // include or require the database connection file
        require_once 'dbh.inc.php'; // database connection file
        require_once 'signup_model.inc.php'; // model file
        require_once 'signup_contr.inc.php'; // controller file

         // initialize an empty array to store error messages
        $errors = [];

        // checking if every input are filled out, 
        if (is_input_empty($fullName, $email, $password, $confirmPassword)) {

            // if any input is empty, add an error message to the errors array
            $errors["empty_input"] = "All fields are required.";
        }

        // checking the validity of the email
        if (is_email_invalid($email)) {

            // if email is not valid, add an error message to the errors array
            $errors["invalid_email"] = "Please enter a valid email address.";
        }

        // checking the validality of the full name
        if (is_fullname_invalid($fullName)) {

            // if full name is not valid, add an error message to the errors array
            $errors["invalid_fullname"] = "Full name can only contain letters and spaces.";
        }

        // checking if the password and confirm password match
        if (do_passwords_mismatch($password, $confirmPassword)) {

            // if passwords do not match, add an error message to the errors array
            $errors["password_mismatch"] = "Passwords do not match.";
        }

        // checking if the email already exists in the database
        if (is_email_registered($pdo, $email)) {

            // if email already exists, add an error message to the errors array
            $errors["email_exists"] = "An account with this email already exists.";
        }

        // include a session config file 
        require_once 'config_session.inc.php';

        // if there are errors, store them and redirect back
        if (!empty($errors)) {
            // store the errors in the session to display them on the signup page
            $_SESSION["errors_signup"] = $errors;

            // store the user input data in the session to pre-fill the form
            $signupData = [
                "fullName" => $fullName,
                "email" => $email
            ];

            // send it back to the signup page by storing it in the session
            $_SESSION["signup_data"] = $signupData;

            // Return JSON error response
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Registration failed', 'errors' => $errors]);
            die(); // terminate the script
        }

        // no errors - create the user in the database
        create_user($pdo, $fullName, $email, $password);

        // Get the newly created user to set up session
        require_once 'login_model.inc.php';
        $result = get_user_unified($pdo, $email);

        // Set up session for the newly registered user
        $_SESSION["user_id"] = $result["ID"];
        $_SESSION["user_email"] = htmlspecialchars($result["Email"]);
        $_SESSION["user_role"] = 'customer';
        $_SESSION["user_name"] = htmlspecialchars($result["Full_Name"] ?? 'Customer');

        // regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();

        // Return JSON success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
        die(); 

    // catch any PDO exceptions
    } catch (PDOException $e) {

        // if there is an error, display the error message
        die("Query failed: " . $e->getMessage());
    }

} else {
    // if the user did not submit the form correctly, redirect to the signup page
    header("Location: ../login.php");

    // terminate the script
    die();
}