<?php
if (!isset($_SESSION)) {
    session_start();
}

// Check if logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<div class="header">
    <a href="admin.php">Dashboard</a>
    <a href="enroll_user.php">User Management</a>
    <a href="enroll_course.php">Enroll Course</a>
    <a href="enroll_academy.php">Enroll Academy</a>
    <a href="request_list.php">Request List</a>
    <a href="create_certificate.php">Create Certificate</a>
    <a href="logout.php">Logout</a>
</div>

<style>
    .header { background: #343a40; padding: 10px; display: flex; }
    .header a {
        color: white; padding: 10px 15px; text-decoration: none;
        border-right: 1px solid #555;
    }
    .header a:hover { background: #495057; }
</style>
