<?php
require 'db.php';

//signup
if(isset($_POST['action']) && $_POST['action'] == 'signup') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $username = $_POST['username'];
    echo $email;

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt -> bind_param("s", $email);
    $stmt -> execute();
    $stmt -> store_result();

    if ($stmt->num_rows > 0) {
        // Email is already registered, don't allow signup
        echo "Email already registered! Please use a different email.";
    } else {
        $stmt -> close();

        //Insert user
        $stmt = $conn -> prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $username, $password);

        if ($stmt->execute()) {
            echo "Signup successful!";
        } else {
            echo "Signup error: " . $stmt->error;
        }
        $stmt->close();
    }
}

echo "OKay";

if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch user
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId, $hashedPassword);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            $_SESSION['user_id'] = $userId;
            echo "Login successful!";
        } else {
            echo "Invalid credentials!";
        }
    } else {
        echo "No user found!";
    }
    $stmt->close();
}

$conn->close();

?>