<?php
session_start();
require 'db.php'; // DB connection
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch data
try {
    // Academies
    $acad_stmt = $pdo->query("SELECT * FROM academy ORDER BY academy_name");
    $academies = $acad_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Students
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE user_type = 'user' ORDER BY username");
    $user_stmt->execute();
    $students = $user_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pending requests
    $req_stmt = $pdo->prepare("SELECT er.*, c.course_name, a.academy_name
                               FROM enrollment_request er
                               LEFT JOIN course c ON er.course_id = c.course_id
                               LEFT JOIN academy a ON er.academy_id = a.academy_id
                               WHERE er.status = 'pending'
                               ORDER BY er.requested_at DESC");
    $req_stmt->execute();
    $pending_requests = $req_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial; background: #f8f9fa; margin: 0; }
        .header { background: #343a40; padding: 10px; display: flex; }
        .header a {
            color: white; padding: 10px 15px; text-decoration: none;
            border-right: 1px solid #555;
        }
        .header a:hover { background: #495057; }
        .container { padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background: #007bff; color: white; }
        h2 { margin-top: 30px; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>


<div class="container">
    <h1>Welcome, Admin <?= htmlspecialchars($_SESSION['username']); ?></h1>

    <h2>Academy List</h2>
    <table>
        <tr><th>ID</th><th>Name</th><th>Address</th><th>Code</th></tr>
        <?php foreach ($academies as $academy): ?>
            <tr>
                <td><?= $academy['academy_id'] ?></td>
                <td><?= htmlspecialchars($academy['academy_name']) ?></td>
                <td><?= htmlspecialchars($academy['address']) ?></td>
                <td><?= htmlspecialchars($academy['unicode']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Pending Requests</h2>
    <table>
        <tr><th>ID</th><th>Student</th><th>Course</th><th>Academy</th><th>Status</th><th>Requested At</th></tr>
        <?php foreach ($pending_requests as $req): ?>
            <tr>
                <td><?= $req['request_id'] ?></td>
                <td><?= htmlspecialchars($req['student_name']) ?></td>
                <td><?= htmlspecialchars($req['course_name']) ?></td>
                <td><?= htmlspecialchars($req['academy_name']) ?></td>
                <td><?= htmlspecialchars($req['status']) ?></td>
                <td><?= $req['requested_at'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
