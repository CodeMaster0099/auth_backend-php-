<?php
require "./cors.php";
require "./db.php";
session_start();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->prepare("SELECT id, title, content FROM services");
    $stmt->execute();
    $result = $stmt->get_result();

    $all_service = [];
    while ($row = $result->fetch_assoc()) {
        $all_service[] = $row;
    }
    echo json_encode($all_service);

    $stmt->close();
    $conn->close();
    exit();
}

if ($method === 'POST') {
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    if (!isset($data['title']) || !isset($data['content'])) {
        echo json_encode(["status" => "error", "message" => "Title and Content are required"]);
        exit();
    }

    $title = $data['title'];
    $content = $data['content'];

    $stmt = $conn->prepare("SELECT id FROM services WHERE title = ? AND content = ?");
    $stmt->bind_param("ss", $title, $content);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "This service already exists."]);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO services (title, content) VALUES (?, ?)");
    $stmt->bind_param("ss", $title, $content);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Service added successfully",
            "data" => [
                "id" => $stmt->insert_id,
                "title" => $title,
                "content" => $content,
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add service"]);
    }
    $stmt->close();
    $conn->close();
    exit();
}

if ($method === 'PUT') {
    // Update or Delete service
    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData, true);

    $id = $_PUT['id'] ?? null;
    $title = $_PUT['title'] ?? null;
    $content = $_PUT['content'] ?? null;

    if (!$id) {
        echo json_encode(["status" => "error", "message" => "ID is required"]);
        exit();
    }

    if ($title === null && $content === null) {
        // DELETE
        $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(["status" => "success", "message" => "Service deleted successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "No service found with this ID"]);
        }

        $stmt->close();
        $conn->close();
        exit();
    } else{
        // UPDATE
        $stmt = $conn->prepare("UPDATE services SET title = ?, content = ? WHERE id = ?");
        $stmt->bind_param("ssi", $title, $content, $id);
        $stmt->execute();
    
        if ($stmt->affected_rows > 0) {
            echo json_encode(["status" => "success", "message" => "Service updated successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Service not found or no changes made"]);
        }
    }

    $stmt->close();
    $conn->close();
    exit();
}

echo json_encode(["status" => "error", "message" => "Unsupported request method"]);
