<?php
session_start();
require 'db.php';
require 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get academy_id from session
if (!isset($_SESSION['academy_id'])) {
    $_SESSION['error'] = "Academy information not found. Please login again.";
    header("Location: login.php");
    exit();
}
$academy_id = $_SESSION['academy_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = $_POST['student_name'];
    $roll_no = $_POST['roll_no'];
    $course = $_POST['course'];
    $batch_start = $_POST['batch_start'];
    $batch_end = $_POST['batch_end'];
    
    // Check for existing application
    $checkStmt = $pdo->prepare("SELECT request_id FROM reports WHERE roll_no = ? AND student_name = ? AND academy_id = ?");
    $checkStmt->execute([$roll_no, $student_name, $academy_id]);
    $existingApplication = $checkStmt->fetch();

    if ($existingApplication) {
        $_SESSION['error'] = "An application for this student already exists!";
        header("Location: academy.php");
        exit();
    }

    // Generate request ID
    $request_id = generateRequestId();
    $status = 'pending';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO reports 
                             (request_id, student_name, roll_no, course, batch_start, batch_end, status, academy_id, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $request_id,
            $student_name,
            $roll_no,
            $course,
            $batch_start,
            $batch_end,
            $status,
            $academy_id
        ]);
        
        $_SESSION['success'] = "Application submitted successfully! Request ID: " . $request_id;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error submitting application: " . $e->getMessage();
    }
}

header("Location: academy.php");
exit();
?>