<?php
session_start();
require 'db.php'; // your PDO connection


// Function to generate request ID
function generateRequestId() {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < 4; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    $timestamp = time();
    return 'RQ-' . $randomString . '-' . $timestamp;
}

// Fetch academy_id from session or users table
if (!isset($_SESSION['academy_id'])) {
    $stmt = $pdo->prepare("SELECT academy_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $academyData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($academyData) {
        $_SESSION['academy_id'] = $academyData['academy_id'];
    } else {
        die("Academy not found for this user.");
    }
}
$academy_id = $_SESSION['academy_id'];

// Handle student enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_name'])) {
    $student_name = $_POST['student_name'];
    $roll_no = $_POST['roll_no'];
    $phone_no = $_POST['phone_no'];
    $course_id = $_POST['course_id'];
    $batch_start = $_POST['batch_start'];

    // First check if student with this roll_no already exists
    // Using student_id or whatever your primary key is named
    $checkStmt = $pdo->prepare("SELECT student_id FROM students WHERE roll_no = ? AND academy_id = ?");
    $checkStmt->execute([$roll_no, $academy_id]);
    $existingStudent = $checkStmt->fetch();

    if ($existingStudent) {
        $error = "A student with this roll number already exists!";
    } else {
        $batch_start_date = new DateTime($batch_start . "-01");
        $batch_end_date = (clone $batch_start_date)->modify('+3 months')->modify('-1 day');

        try {
            $stmt = $pdo->prepare("INSERT INTO students (student_name, roll_no, phone_no, course_id, batch_start, batch_end, academy_id) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $student_name,
                $roll_no,
                $phone_no,
                $course_id,
                $batch_start_date->format('Y-m-d'),
                $batch_end_date->format('Y-m-d'),
                $academy_id
            ]);

            $success = "Student enrolled successfully!";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // MySQL duplicate entry error code
                $error = "A student with this roll number already exists!";
            } else {
                $error = "Error enrolling student: " . $e->getMessage();
            }
        }
    }
}

// Display success/error messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Get search term if present
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch courses
$courses = $pdo->query("SELECT course_id, course_name FROM course")->fetchAll(PDO::FETCH_ASSOC);

// Fetch students list for this academy with reports data
$sql = "
    SELECT s.student_name, s.roll_no, c.course_name, s.batch_start, s.batch_end, 
           r.request_id, r.status
    FROM students s
    JOIN course c ON s.course_id = c.course_id
    LEFT JOIN reports r ON s.roll_no = r.roll_no AND s.student_name = r.student_name
    WHERE s.academy_id = ?
";
$params = [$academy_id];

if ($search !== '') {
    $sql .= " AND s.student_name LIKE ?";
    $params[] = "%" . $search . "%";
}

$sql .= " ORDER BY s.enrolled_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Academy Dashboard</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            display: flex;
            background-color: #f5f5f5;
        }
        header { 
            background: #2c3e50; 
            color: #fff; 
            padding: 15px; 
            display: flex; 
            gap: 15px; 
            position: fixed; 
            width: 100%; 
            top: 0; 
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        header a { 
            color: white; 
            text-decoration: none; 
            padding: 10px 20px; 
            background: #3498db; 
            border-radius: 4px; 
            transition: all 0.3s ease;
        }
        header a:hover { 
            background: #2980b9; 
            transform: translateY(-2px);
        }
        .container { 
            padding: 90px 30px 30px; 
            flex: 0 0 40%;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            margin: 20px;
            border-radius: 8px;
        }
        .sidebar { 
            padding: 90px 30px 30px; 
            flex: 0 0 55%;
            overflow-y: auto;
            height: calc(100vh - 60px);
        }
        form { 
            background: #f9f9f9; 
            padding: 25px; 
            border-radius: 8px; 
            max-width: 450px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        label { 
            display: block; 
            margin-top: 15px;
            font-weight: 600;
            color: #34495e;
        }
        input, select { 
            width: 100%; 
            padding: 10px; 
            margin-top: 8px; 
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        button { 
            margin-top: 20px; 
            padding: 12px 20px; 
            background: #27ae60; 
            color: white; 
            border: none; 
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        button:hover { 
            background: #219653;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .success { 
            background: #d4edda; 
            padding: 12px; 
            border-left: 4px solid #28a745; 
            margin-bottom: 20px;
            border-radius: 4px;
            color: #155724;
        }
        .error { 
            background: #f8d7da; 
            padding: 12px; 
            border-left: 4px solid #dc3545; 
            margin-bottom: 20px;
            border-radius: 4px;
            color: #721c24;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            background: white;
        }
        table, th, td { 
            border: 1px solid #e0e0e0; 
        }
        th, td { 
            padding: 12px; 
            text-align: left; 
        }
        th { 
            background: #3498db;
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        
        .btn-apply {
            background-color: #3498db;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-apply:hover {
            background-color: #2980b9;
            transform: translateY(-1px);
        }
        .btn-reapply {
            background-color: #e67e22;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-reapply:hover {
            background-color: #d35400;
            transform: translateY(-1px);
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }
        .status-pending {
            background-color: #f39c12;
            color: #fff;
        }
        .status-approved {
            background-color: #2ecc71;
            color: #fff;
        }
        .status-rejected {
            background-color: #e74c3c;
            color: #fff;
        }
        .status-processing {
            background-color: #3498db;
            color: #fff;
        }
        .action-cell {
            display: flex;
            gap: 8px;
        }
    </style>
</head>
<body>
    <header>
        <a href="academy.php">Enroll Student</a>
        <a href="logout.php">Logout</a>
    </header>
    
    <div class="container">
        <h2>Enroll Student</h2>
        <?php if (!empty($success)): ?>
            <div class='success'><?= $success ?></div>
            <script>
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            </script>
        <?php endif; ?>
        <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <label>Student Name:</label>
            <input type="text" name="student_name" required>

            <label>Roll Number:</label>
            <input type="text" name="roll_no" required>

            <label>Phone No:</label>
            <input type="text" name="phone_no" required>

            <label>Course:</label>
            <select name="course_id" required>
                <option value="">Select Course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= htmlspecialchars($course['course_id']) ?>">
                        <?= htmlspecialchars($course['course_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Batch Start (Month-Year):</label>
            <input type="month" name="batch_start" required>

            <button type="submit">Enroll</button>
        </form>
    </div>

    <div class="sidebar">
        <h2>Student List</h2>
        <form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input type="text" name="search" placeholder="Search by student name" 
                   value="<?= htmlspecialchars($search) ?>" 
                   style="padding: 10px; width: 70%; border-radius: 4px; border: 1px solid #ddd;">
            <button type="submit" style="padding: 10px 20px; background: #3498db;">Search</button>
        </form>

        <table>
            <tr>
                <th>Request ID</th>
                <th>Student Name</th>
                <th>Roll No</th>
                <th>Course</th>
                <th>Batch Start</th>
                <th>Batch End</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($students as $student): ?>
            <tr>
                <td><?= !empty($student['request_id']) ? htmlspecialchars($student['request_id']) : 'N/A' ?></td>
                <td><?= htmlspecialchars($student['student_name']) ?></td>
                <td><?= htmlspecialchars($student['roll_no']) ?></td>
                <td><?= htmlspecialchars($student['course_name']) ?></td>
                <td><?= htmlspecialchars($student['batch_start']) ?></td>
                <td><?= htmlspecialchars($student['batch_end']) ?></td>
                <td>
                    <span class="status-badge status-<?= strtolower(htmlspecialchars($student['status'] ?? 'none')) ?>">
                        <?= htmlspecialchars($student['status'] ?? 'Not Applied') ?>
                    </span>
                </td>
                <td class="action-cell">
                    <?php if (empty($student['request_id'])): ?>
                        <form method="POST" action="apply_report.php">
                            <input type="hidden" name="student_name" value="<?= htmlspecialchars($student['student_name']) ?>">
                            <input type="hidden" name="roll_no" value="<?= htmlspecialchars($student['roll_no']) ?>">
                            <input type="hidden" name="course" value="<?= htmlspecialchars($student['course_name']) ?>">
                            <input type="hidden" name="batch_start" value="<?= htmlspecialchars($student['batch_start']) ?>">
                            <input type="hidden" name="batch_end" value="<?= htmlspecialchars($student['batch_end']) ?>">
                            <button type="submit" class="btn-apply">Apply</button>
                        </form>
                    <?php elseif (($student['status'] ?? '') == 'rejected'): ?>
                        <form method="POST" action="reapply_report.php">
                            <input type="hidden" name="request_id" value="<?= htmlspecialchars($student['request_id']) ?>">
                            <button type="submit" class="btn-reapply">Reapply</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>