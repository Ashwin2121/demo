<?php
session_start();
require 'db.php';
include 'header.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success = "";
$error = "";

// Add new course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_name'])) {
    $course_name = trim($_POST['course_name']);
    $description = trim($_POST['description']);

    if (!empty($course_name) && !empty($description)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO course (course_name, description) VALUES (:course_name, :description)");
            $stmt->bindParam(':course_name', $course_name);
            $stmt->bindParam(':description', $description);
            if ($stmt->execute()) {
                $success = "Course enrolled successfully!";
            } else {
                $error = "Failed to enroll course.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill all fields.";
    }
}

// Fetch courses
try {
    $courses = $pdo->query("SELECT * FROM course ORDER BY course_id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $courses = [];
    $error = "Error fetching courses: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Enroll Course</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            background-color: #f4f4f4; 
        }
        main {
            padding: 20px;
            max-width: 1200px;
            margin: auto;
        }
        .container { 
            display: flex; 
            gap: 30px; 
            flex-wrap: wrap; 
        }
        form, .course-list {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            flex: 1;
            min-width: 300px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        input, textarea {
            width: 100%; padding: 10px; margin: 8px 0;
            border: 1px solid #ccc; border-radius: 5px;
        }
        button {
            padding: 10px 15px; background: #28a745; border: none;
            color: white; border-radius: 5px; cursor: pointer;
        }
        button:hover { background: #218838; }
        .delete-btn {
            background: #dc3545; border: none;
            color: white; padding: 5px 10px;
            border-radius: 5px; cursor: pointer;
            font-size: 12px;
        }
        .delete-btn:hover { background: #b02a37; }
        .success { color: green; }
        .error { color: red; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid #ccc; }
        th { text-align: left; }
    </style>
</head>
<body>

<main>
<h2>Enroll a New Course</h2>
<?php if ($success) echo "<p class='success'>$success</p>"; ?>
<?php if ($error) echo "<p class='error'>$error</p>"; ?>

<div class="container">
    <!-- Form -->
    <form method="POST">
        <label>Course Name:</label>
        <input type="text" name="course_name" required>

        <label>Description:</label>
        <textarea name="description" rows="4" required></textarea>

        <button type="submit">Enroll Course</button>
    </form>

    <!-- List -->
    <div class="course-list">
        <h3>Available Courses</h3>
        <table id="courseTable">
            <tr>
                <th>Course Name</th>
                <th>Action</th>
            </tr>
            <?php foreach ($courses as $course): ?>
            <tr id="row-<?= $course['course_id'] ?>">
                <td><?= htmlspecialchars($course['course_name']) ?></td>
                <td>
                    <button class="delete-btn" onclick="deleteCourse(<?= $course['course_id'] ?>)">Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</main>

<script>
function deleteCourse(id) {
    if (!confirm("Are you sure you want to delete this course?")) return;

    fetch("delete_course.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + id
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById("row-" + id).remove();
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => alert("Request failed"));
}
</script>

</body>
</html>
