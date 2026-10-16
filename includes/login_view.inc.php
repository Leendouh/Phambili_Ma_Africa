<?php


// allow code to have type decoration
declare(strict_types=1); 

// function to print-out email when login
function output_email() 
{
    // check all recurently login by checking for user-id
    if (isset($_SESSION["user_email"])) {

        // print-out the email
        echo htmlspecialchars($_SESSION["user_email"]);
    } else {

        // print-out not logged in
        echo "You are not logged in.";
    }
}

// a function that will output errors
function check_login_errors()
{
    // check if we have session variable set correctly
    if (isset($_SESSION["errors_login"])) {

        // check if we already have errors stored inside a session
        $errors = $_SESSION["errors_login"];

        // we need to run the error code
        echo "<br>";

        // loop-out an array for each error
        foreach ($errors as $error) {
            // output the error 
            echo "ERROR: " . $error . "<br>";
        }


        // we want to unset our session variables(FOR SECURITY)
        // if theres any sort of information inside our session is no longer needed we unset it
        unset($_SESSION['errors_login']);


        // check if we have certain get message URL [success message]
    } else if (isset($_GET["login"]) && $_GET["login"] === "success") {

        // output messag
        echo "<br>";
        echo "Login Success";
    }
}