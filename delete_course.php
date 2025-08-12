<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

if (!isset($_POST['id'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

$id = intval($_POST['id']);

try {
    $stmt = $pdo->prepare("DELETE FROM course WHERE course_id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Delete failed"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
