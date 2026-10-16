<?php

// allow code to have type decorations
declare(strict_types=1);

// function to check if any input is empty
function is_input_empty(string $fullName, string $email, string $password, string $confirmPassword)
{
    // check if any of the inputs are empty
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {

        return true; // at least one input is empty
    } else {
        return false; // all inputs are filled
    }
}

// function to check if the email is invalid
function is_email_invalid(string $email)
{
    // use PHP's filter_var function to validate the email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        return true; // email is invalid
    } else {
        return false; // email is valid
    }
}

// function to check if the full name is invalid
function is_fullname_invalid($fullName)
{
    // check if the full name contains only letters, spaces, hyphens, and apostrophes
    if (!preg_match("/^[a-zA-Z\s\-'']+$/", $fullName)) {

        // if the full name contains invalid characters, return true
        return true; // full name is invalid
    } else {
        return false; // full name is valid
    }
}

// function checking if the email is taken
function is_email_registered(object $pdo, string $email)
{
    // check if usernam is exist in database
    if (get_email($pdo, $email)) {

        return true; // email is already registered
    } else {
        return false; // email is available
    }
}

// 
function do_passwords_mismatch(string $password, string $confirmPassword)
{
    // check if the password and confirm password do not match
    if ($password !== $confirmPassword) {

        return true; // passwords do not match
    } else {
        return false; // passwords match
    }
}

// function if there nor errors after the user submitted the form
function create_user(object $pdo, string $fullName, string $email, string $password)
{
    // call the function to create a new user in the database
    set_user($pdo, $fullName, $email, $password);
}