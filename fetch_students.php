<?php
session_start();
require 'db.php';

$academy_id = $_SESSION['academy_id'];

$stmt = $pdo->prepare("
    SELECT s.student_name, c.course_name, a.academy_name, s.batch_start, s.batch_end
    FROM students s
    JOIN course c ON s.course_id = c.course_id
    JOIN academy a ON s.academy_id = a.academy_id
    WHERE s.academy_id = ?
    ORDER BY s.student_id DESC
");
$stmt->execute([$academy_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($students) {
    echo "<table>
            <tr>
                <th>Student</th>
                <th>Course</th>
                <th>Academy</th>
                <th>Batch</th>
                <th>Action</th>
            </tr>";
    foreach ($students as $row) {
        echo "<tr>
                <td>".htmlspecialchars($row['student_name'])."</td>
                <td>".htmlspecialchars($row['course_name'])."</td>
                <td>".htmlspecialchars($row['academy_name'])."</td>
                <td>".htmlspecialchars($row['batch_start'])." to ".htmlspecialchars($row['batch_end'])."</td>
                <td><button class='apply-btn'>Apply</button></td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "No students found.";
}
