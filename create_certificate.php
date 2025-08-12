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

// Fetch academies and courses
$academies = $pdo->query("SELECT academy_id, academy_name, unicode FROM academy")->fetchAll(PDO::FETCH_ASSOC);
$courses = $pdo->query("SELECT course_id, course_name FROM course")->fetchAll(PDO::FETCH_ASSOC);

// Handle certificate creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_name'], $_POST['course_id'], $_POST['academy_id'], $_POST['batch_start'])) {
    $student_name = trim($_POST['student_name']);
    $course_id = (int) $_POST['course_id'];
    $academy_id = (int) $_POST['academy_id'];
    $batch_start = $_POST['batch_start'];

    if (!empty($student_name) && $course_id && $academy_id && $batch_start) {
        // Calculate batch_end = batch_start + 3 months - 1 day
        $startDate = new DateTime($batch_start . '-01');
        $batch_start_date = $startDate->format('Y-m-d');
        $endDate = clone $startDate;
        $endDate->modify('+3 months')->modify('-1 day');
        $batch_end_date = $endDate->format('Y-m-d');

        // Get academy unicode prefix
        $stmt = $pdo->prepare("SELECT unicode FROM academy WHERE academy_id = ?");
        $stmt->execute([$academy_id]);
        $academy = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$academy) {
            $error = "Invalid academy selected.";
        } else {
            $prefix = $academy['unicode'];

            // Generate unique certificate number
            do {
                $randomNumber = mt_rand(10000, 99999);
                $certificate_no = $prefix . $randomNumber;
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM certificate WHERE certificate_no = ?");
                $stmt->execute([$certificate_no]);
                $exists = $stmt->fetchColumn() > 0;
            } while ($exists);

            // Insert certificate
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO certificate (certificate_no, student_name, course_id, batch_start, batch_end, academy_id, issued_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                if ($stmt->execute([$certificate_no, $student_name, $course_id, $batch_start_date, $batch_end_date, $academy_id])) {
                    $success = "Certificate created successfully! Certificate No: " . $certificate_no;
                } else {
                    $error = "Failed to create certificate.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please fill all fields.";
    }
}

// Handle search
$search_results = [];
if (isset($_GET['search_cert'])) {
    $search = trim($_GET['search_cert']);
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT c.*, a.academy_name, cr.course_name
            FROM certificate c
            JOIN academy a ON c.academy_id = a.academy_id
            JOIN course cr ON c.course_id = cr.course_id
            WHERE c.certificate_no LIKE ?
        ");
        $stmt->execute(['%' . $search . '%']);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Fetch recent certificate
$recent_certificate = $pdo->query("
    SELECT c.*, a.academy_name, cr.course_name
    FROM certificate c
    JOIN academy a ON c.academy_id = a.academy_id
    JOIN course cr ON c.course_id = cr.course_id
    ORDER BY c.certificate_id DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Certificate Management</title>
    <style>
        :root {
            --primary-color: #3498db;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --dark-gray: #343a40;
            --text-color: #212529;
            --white: #ffffff;
            --border-radius: 0.375rem;
            --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--light-gray);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .page-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h2, h3, h4 {
            color: var(--dark-gray);
            margin-bottom: 1rem;
        }

        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-gray);
        }

        input[type="text"],
        input[type="month"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--medium-gray);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        input[type="text"]:focus,
        input[type="month"]:focus,
        select:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        #batch_end_display[readonly] {
            background-color: var(--light-gray);
            opacity: 1;
        }

        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: var(--border-radius);
            transition: all 0.15s ease-in-out;
            cursor: pointer;
        }

        .btn-primary {
            color: var(--white);
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2c7be5;
            border-color: #2b75db;
        }

        .btn-success {
            color: var(--white);
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .search-form input {
            flex: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background-color: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--medium-gray);
        }

        th {
            background-color: var(--primary-color);
            color: var(--white);
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: var(--light-gray);
        }

        tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: var(--border-radius);
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .page-wrapper {
                padding: 15px;
            }
            
            th, td {
                padding: 0.75rem;
            }
        }
    </style>

    <script>
        function updateBatchEnd() {
            const start = document.getElementById('batch_start').value;
            if (start) {
                const [year, month] = start.split('-');
                const startDate = new Date(year, month - 1, 1);
                const endDate = new Date(startDate);
                endDate.setMonth(endDate.getMonth() + 3);
                endDate.setDate(endDate.getDate() - 1);

                const endMonth = String(endDate.getMonth() + 1).padStart(2, '0');
                const endYear = endDate.getFullYear();
                document.getElementById('batch_end_display').value = `${endYear}-${endMonth}`;
            }
        }
    </script>
</head>
<body>
    <div class="page-wrapper">
        <h2>Certificate Management</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="container">
            <!-- Certificate Creation Form -->
            <div class="card">
                <h3>Create New Certificate</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="student_name">Student Name:</label>
                        <input type="text" id="student_name" name="student_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="course_id">Course:</label>
                        <select id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="academy_id">Academy:</label>
                        <select id="academy_id" name="academy_id" required>
                            <option value="">Select Academy</option>
                            <?php foreach ($academies as $academy): ?>
                                <option value="<?= $academy['academy_id'] ?>"><?= htmlspecialchars($academy['academy_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="batch_start">Batch Start (YYYY-MM):</label>
                        <input type="month" id="batch_start" name="batch_start" onchange="updateBatchEnd()" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="batch_end_display">Batch End:</label>
                        <input type="month" id="batch_end_display" readonly>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Create Certificate</button>
                </form>
            </div>

            <!-- Search Section -->
            <div class="card">
                <h3>Search Certificates</h3>
                <form method="GET" class="search-form">
                    <input type="text" name="search_cert" placeholder="Enter certificate number" value="<?= isset($_GET['search_cert']) ? htmlspecialchars($_GET['search_cert']) : '' ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
                
                <?php if (!empty($search_results)): ?>
                    <h4>Search Results</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Certificate No</th>
                                <th>Student</th>
                                <th>Academy</th>
                                <th>Course</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($search_results as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['certificate_no']) ?></td>
                                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                                    <td><?= htmlspecialchars($row['academy_name']) ?></td>
                                    <td><?= htmlspecialchars($row['course_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <!-- Recent Certificates -->
                <h4 style="margin-top: 20px;">Recently Created Certificates</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Certificate No</th>
                            <th>Student</th>
                            <th>Academy</th>
                            <th>Course</th>
                            <th>Batch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_certificate as $cert): ?>
                            <tr>
                                <td><?= htmlspecialchars($cert['certificate_no']) ?></td>
                                <td><?= htmlspecialchars($cert['student_name']) ?></td>
                                <td><?= htmlspecialchars($cert['academy_name']) ?></td>
                                <td><?= htmlspecialchars($cert['course_name']) ?></td>
                                <td><?= date('M Y', strtotime($cert['batch_start'])) . " - " . date('M Y', strtotime($cert['batch_end'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>