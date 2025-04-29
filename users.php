<?php
session_start();
require 'db.php';
require 'JWT/JWT.php';
include 'cors.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

//signup
if (isset($_POST['action']) && $_POST['action'] == 'signup') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    // $username = $_POST['username'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Email is already registered, don't allow signup
        echo "Email already registered! Please use a different email.";
    } else {
        $stmt->close();

        //Insert user
        $stmt = $conn->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $username, $password);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Signup successful!"]);
        } else {
            echo "Signup error: " . $stmt->error;
        }
        $stmt->close();
    }
}

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (isset($data['action']) && $data['action'] === 'login') {
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Email and password are required."
        ]);
        exit();
    }

    // Fetch user
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId, $hashedPassword);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            // $_SESSION['user_id'] = $userId;
            $key = "secret_key";
            $payload = [
                "iss" => "yourname",
                "aud" => "",
                "iat" => time(),
                "exp" => time() + (60 * 60),
                "data" => [
                    "password" => $password,
                    "email" => $email
                ],
            ];
            $jwt = JWT::encode($payload, $key, 'HS256');

            echo json_encode([
                "status" => "success",
                "message" => "Login successful.",
                "data" => [
                    "userID" => $userId,
                    "email" => $email,
                    "token" => $jwt
                ]
            ]);
            exit();
        } else {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "User not found."
            ]);
            exit();
        }
    } 
    $stmt->close();
}

$conn->close();

?>