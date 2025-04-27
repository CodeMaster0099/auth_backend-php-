<?php

require 'db.php';

if (isset($_POST['action']) && $_POST['action'] == 'services') {
    $title = $_POST['title'];
    $content = $_POST['content'];

    $stmt = $conn->prepare("INSERT INTO services (title, content) VALUES (?, ?)");
    $stmt->bind_param("ss", $title, $content);

    if ($stmt->execute()) {
        echo "Add service successful";
    } else {
        echo "Add service error" . $stmt->error;
    }
    $stmt->close();
}

if (isset($_GET['action']) && $_GET['action'] == 'services') {
    $stmt = $conn->prepare("SELECT title, content FROM services");
    $stmt->execute();

    $result = $stmt->get_result();
    $all_service = [];
    while ($row = $result->fetch_assoc()) {
        $all_service[] = $row;
    }
    echo json_encode($all_service);
    $stmt->close();
}

// Only handle PUT requests
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Create $_PUT manually
    parse_str(file_get_contents("php://input"), $_PUT);

    if (isset($_PUT['action']) && $_PUT['action'] == 'services') {
        echo "-------"; // for testing

        $id = $_PUT['id'];
        $title = $_PUT['title'];
        $content = $_PUT['content'];

        // Assuming $conn is your mysqli connection
        $stmt = $conn->prepare("UPDATE services SET title = ?, content = ? WHERE id = ?");
        $stmt->bind_param("ssi", $title, $content, $id);

        try {
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Service updated successfully!',
                        'data' => [
                            'id' => $id,
                            'title' => $title,
                            'content' => $content
                        ]
                    ];
                    echo json_encode($response);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Service not found'
                    ]);
                }
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error updating service',
                    'error' => $stmt->error
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ]);
        }

        $stmt->close();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
