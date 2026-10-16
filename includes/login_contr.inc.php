<?php

// allow code to have decoration
declare(strict_types=1);

// function for checking is input empty
function is_input_empty(string $email, string $password) 
{
    // check is email and password are empty
    if (empty($email) || empty($password)) {

        // return true
        return true;
    }else {
        // else return false
        return false;
    }

}

// function that checks whether the email is wrong which returns true
function is_email_wrong(array|false $result): bool 
{
    // Returns true if no user found (result is false or empty)
    return $result === false || empty($result);
}

// function that checks whether the password is wrong which returns true
function is_password_wrong(string $pswd, string $hashedPassword) 
{
    // check whether the password and hashed password match each other
    if (!password_verify($pswd,  $hashedPassword)) {

        // return an error mssage
        return true;
    } else {

        // not having error message
        return false;
    }
}

// ========== ADMIN SPECIFIC VALIDATION FUNCTIONS ==========

// Check if it's admin's first login
function is_first_login(array $result): bool
{
    return isset($result['first_login']) && $result['first_login'] == 1;
}

// Validate RSA phone number (10 digits)
function is_valid_rsa_phone(string $phone): bool
{
    return preg_match('/^[0-9]{10}$/', $phone) === 1;
}

// Validate password strength for new password
function is_password_strong(string $password): bool
{
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 
        && preg_match('/[A-Z]/', $password) 
        && preg_match('/[a-z]/', $password) 
        && preg_match('/[0-9]/', $password);
}
