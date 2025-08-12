<?php
session_start();
require 'db.php';
require 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE reports SET status = 'pending' WHERE request_id = ?");
        $stmt->execute([$request_id]);
        $_SESSION['success'] = "Application resubmitted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error resubmitting application: " . $e->getMessage();
    }
}

header("Location: academy.php");
exit();
?>