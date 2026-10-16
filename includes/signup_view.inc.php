<?php

// allow code to have type decorations
declare(strict_types=1);

// function to display the full name input field on the signup form
function signup_inputs()
{
    // check if the signup data exists in the session and if there is no, fullname_taken error
    if (isset($_SESSION["signup_data"]["fullName"]) && !isset($_SESSION["errors_signup"]["fullName_taken"])) {

        // display the full name input field with the previously entered value
        echo '<input type="text" name="fullName" placeholder="Full Name" value="' . htmlspecialchars($_SESSION["signup_data"]["fullName"]) . '">';
    } else {

        // display an empty full name input field
        echo '<input type="text" name="fullName" placeholder="Full Name">';
    }


    // check if the signup data exists in the session and if there is no, email_used or invalid_email error
    if (isset($_SESSION["signup_data"]["email"]) && !isset($_SESSION["errors_signup"]["email_used"]) && !isset($_SESSION["errors_signup"]["invalid_email"])) {

        // display the email input field with the previously entered value
        echo '<input type="email" name="email" placeholder="Email" value="' . htmlspecialchars($_SESSION["signup_data"]["email"]) . '">';
    } else {
        // display an empty email input field
        echo '<input type="email" name="email" placeholder="Email">';
    }

    // we want the user to retype the password no-matter what happens 
    echo '<input type="password" name="password" placeholder="Password">';

    // we want the user to retype the confirm password no-matter what happens 
    echo '<input type="password" name="confirmPassword" placeholder="Confirm Password">';
}

function check_signup_errors()
{
    // check if we already have errors stored in the session
    if (isset($_SESSION['errors_signup'])) {
        // retrieve the errors from the session
        $errors = $_SESSION['errors_signup'];

        // loop through each error and display it
        echo "<br>";

        foreach ($errors as $error) {
            // display each error message
            echo " " . $error . "<br>";
        }

        // 
        unset($_SESSION['errors_signup']); // clear errors after displaying them

    } else if (isset($_GET["signup"]) && $_GET["signup"] === "success") {

        echo "<br>"; // add a line break for better readability
        echo "Signup successful! You can now log in."; // display success message
    }
}
