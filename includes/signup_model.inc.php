<?php

// allow code to have type decoration
declare(strict_types=1);

// function that checks whether the fullname is taken on the database
function get_fullname(object $pdo, string $fullName)
{
    // prepare the SQL statement to select the fullname from the customers table
    $stmt = $pdo->prepare("SELECT Full_Name FROM customers WHERE Full_Name = :Full_Name");

    // bind the fullname parameter to the prepared statement
    $stmt->bindParam(':Full_Name', $fullName, PDO::PARAM_STR);

    // execute the prepared statement
    $stmt->execute();

    // fetch the result
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // return the result (true if found, false otherwise)
    return $result !== false;
}

// function that checks whether the email is registered on the database
function get_email(object $pdo, string $email)
{
    // prepare the SQL statement to select the email from the customers table
    $stmt = $pdo->prepare("SELECT Email FROM customers WHERE Email = :Email");

    // bind the email parameter to the prepared statement
    $stmt->bindParam(':Email', $email, PDO::PARAM_STR);

    // execute the prepared statement
    $stmt->execute();

    // fetch the result
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // return the result (true if found, false otherwise)
    return $result !== false;
}

// function that insert data inserted by the user into the database
function set_user(object $pdo, string $fullName, string $email, string $password)
{
    // Get the next ID (3-digit format)
    $stmt = $pdo->prepare("SELECT MAX(CAST(ID AS UNSIGNED)) as max_id FROM customers");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextId = $result['max_id'] ? $result['max_id'] + 1 : 1;
    $formattedId = str_pad((string)$nextId, 3, '0', STR_PAD_LEFT);
    
    // Get current timestamp
    $currentDateTime = date('Y-m-d H:i:s');
    
    // prepare the SQL statement to insert the user data into the customers table
    $stmt = $pdo->prepare("INSERT INTO customers (ID, Full_Name, Email, Password, createdAt, updatedAt) VALUES (:ID, :Full_Name, :Email, :Password, :createdAt, :updatedAt)");

    // cost factor which how difficult the hashing will be
    $options = [

        // cost factor for password hashing number between 4 and 31 
        'cost' => 12,
    ];

    // hash the password using BCRYPT algorithm
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, $options);

    // bind the parameters to the prepared statement
    $stmt->bindParam(':ID', $formattedId, PDO::PARAM_STR);
    $stmt->bindParam(':Full_Name', $fullName, PDO::PARAM_STR);
    $stmt->bindParam(':Email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':Password', $hashedPassword, PDO::PARAM_STR);
    $stmt->bindParam(':createdAt', $currentDateTime, PDO::PARAM_STR);
    $stmt->bindParam(':updatedAt', $currentDateTime, PDO::PARAM_STR);

    // execute the prepared statement
    $stmt->execute();
}

// function that gets a user by ID
function getUserById(object $pdo, string $id)
{
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE ID = :ID");
    $stmt->bindParam(':ID', $id, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}