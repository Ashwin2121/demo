<?php
session_start();
require 'db.php';
require 'functions.php';

// Check if user is admin or has permission
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize $requests as empty array
$requests = [];

// Handle search
$search_id = isset($_GET['search_id']) ? trim($_GET['search_id']) : '';

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve'])) {
        $request_id = $_POST['request_id'];
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // 1. Get the request details
            $stmt = $pdo->prepare("SELECT r.*, a.academy_name FROM reports r 
                                  JOIN academy a ON r.academy_id = a.academy_id 
                                  WHERE r.request_id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($request) {
                // 2. Update request status
                $stmt = $pdo->prepare("UPDATE reports SET status = 'approved' WHERE request_id = ?");
                $stmt->execute([$request_id]);
                
                // 3. Generate certificate
                $student_name = $request['student_name'];
                $course = $request['course'];
                $batch_start = $request['batch_start'];
                $academy_id = $request['academy_id'];
                
                // Get course_id from course name
                $stmt = $pdo->prepare("SELECT course_id FROM course WHERE course_name = ?");
                $stmt->execute([$course]);
                $course_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($course_data) {
                    $course_id = $course_data['course_id'];
                    
                    // Calculate batch_end (batch_start + 3 months - 1 day)
                    $startDate = new DateTime($batch_start);
                    $batch_start_date = $startDate->format('Y-m-d');
                    $endDate = clone $startDate;
                    $endDate->modify('+3 months')->modify('-1 day');
                    $batch_end_date = $endDate->format('Y-m-d');
                    
                    // Get academy unicode prefix
                    $stmt = $pdo->prepare("SELECT unicode, academy_name FROM academy WHERE academy_id = ?");
                    $stmt->execute([$academy_id]);
                    $academy = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($academy) {
                        $prefix = $academy['unicode'];
                        $academy_name = $academy['academy_name'];
                        
                        // Generate unique certificate number
                        do {
                            $randomNumber = mt_rand(10000, 99999);
                            $certificate_no = $prefix . $randomNumber;
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM certificate WHERE certificate_no = ?");
                            $stmt->execute([$certificate_no]);
                            $exists = $stmt->fetchColumn() > 0;
                        } while ($exists);
                        
                        // Insert certificate
                        $stmt = $pdo->prepare("
                            INSERT INTO certificate 
                            (certificate_no, student_name, course_id, batch_start, batch_end, academy_id, issued_at)
                            VALUES (?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $certificate_no,
                            $student_name,
                            $course_id,
                            $batch_start_date,
                            $batch_end_date,
                            $academy_id
                        ]);
                        
                        $_SESSION['success'] = "Request approved and certificate #$certificate_no generated successfully for $academy_name!";
                    } else {
                        throw new Exception("Academy not found");
                    }
                } else {
                    throw new Exception("Course not found");
                }
            } else {
                throw new Exception("Request not found");
            }
            
            // Commit transaction
            $pdo->commit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $_SESSION['error'] = "Error approving request: " . $e->getMessage();
        }
        
    } elseif (isset($_POST['reject'])) {
        $request_id = $_POST['request_id'];
        $stmt = $pdo->prepare("UPDATE reports SET status = 'rejected' WHERE request_id = ?");
        $stmt->execute([$request_id]);
        $_SESSION['success'] = "Request rejected successfully!";
    }
    
    header("Location: request_list.php" . (!empty($search_id) ? "?search_id=" . urlencode($search_id) : ""));
    exit();
}

try {
    // Fetch requests with academy name and optional search
    if (!empty($search_id)) {
        $stmt = $pdo->prepare("SELECT r.*, a.academy_name 
                              FROM reports r
                              JOIN academy a ON r.academy_id = a.academy_id
                              WHERE r.request_id LIKE ? 
                              ORDER BY r.created_at DESC");
        $stmt->execute(["%$search_id%"]);
    } else {
        $stmt = $pdo->prepare("SELECT r.*, a.academy_name 
                              FROM reports r
                              JOIN academy a ON r.academy_id = a.academy_id
                              ORDER BY r.created_at DESC");
        $stmt->execute();
    }
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$requests) {
        $requests = [];
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching requests: " . $e->getMessage();
    $requests = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request List</title>
    <style>
    /* Modern CSS Reset */
    :root {
        --primary-color: #3498db;
        --success-color: #2ecc71;
        --warning-color: #f39c12;
        --danger-color: #e74c3c;
        --light-gray: #f8f9fa;
        --medium-gray: #e9ecef;
        --dark-gray: #6c757d;
        --text-color: #212529;
        --white: #ffffff;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', sans-serif;
        line-height: 1.6;
        background-color: var(--light-gray);
        color: var(--text-color);
        padding: 0;
        margin: 0;
    }
    
    /* Container Styles */
    .container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background: var(--white);
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }
    
    /* Header Styles */
    h1 {
        color: #2c3e50;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--medium-gray);
        font-weight: 600;
    }
    
    /* Message Styles */
    .success {
        background-color: rgba(46, 204, 113, 0.1);
        color: var(--success-color);
        padding: 12px 15px;
        margin-bottom: 20px;
        border-radius: 4px;
        border-left: 4px solid var(--success-color);
        display: flex;
        align-items: center;
    }
    
    .error {
        background-color: rgba(231, 76, 60, 0.1);
        color: var(--danger-color);
        padding: 12px 15px;
        margin-bottom: 20px;
        border-radius: 4px;
        border-left: 4px solid var(--danger-color);
        display: flex;
        align-items: center;
    }
    
    /* Search Form Styles */
    .search-container {
        display: flex;
        gap: 10px;
        margin-bottom: 1.5rem;
        align-items: center;
    }
    
    .search-container input[type="text"] {
        flex: 1;
        padding: 10px 15px;
        border: 1px solid var(--medium-gray);
        border-radius: 4px;
        font-size: 1rem;
        max-width: 400px;
        transition: border-color 0.3s;
    }
    
    .search-container input[type="text"]:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    }
    
    /* Button Styles */
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-approve {
        background-color: var(--success-color);
        color: var(--white);
    }
    
    .btn-approve:hover {
        background-color: #27ae60;
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    .btn-reject {
        background-color: var(--danger-color);
        color: var(--white);
    }
    
    .btn-reject:hover {
        background-color: #c0392b;
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    /* Table Styles */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1.5rem;
        font-size: 0.95rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        background: var(--white);
        border-radius: 8px;
        overflow: hidden;
    }
    
    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid var(--medium-gray);
    }
    
    th {
        background-color: var(--primary-color);
        color: var(--white);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    
    tr:nth-child(even) {
        background-color: var(--light-gray);
    }
    
    tr:hover {
        background-color: #e8f4fc;
    }
    
    /* Status Badges */
    .status-pending {
        color: var(--warning-color);
        font-weight: 600;
        background-color: rgba(243, 156, 18, 0.1);
        padding: 6px 12px;
        border-radius: 50px;
        display: inline-block;
    }
    
    .status-approved {
        color: var(--success-color);
        font-weight: 600;
        background-color: rgba(46, 204, 113, 0.1);
        padding: 6px 12px;
        border-radius: 50px;
        display: inline-block;
    }
    
    .status-rejected {
        color: var(--danger-color);
        font-weight: 600;
        background-color: rgba(231, 76, 60, 0.1);
        padding: 6px 12px;
        border-radius: 50px;
        display: inline-block;
    }
    
    /* Action Column */
    .action-cell {
        white-space: nowrap;
        display: flex;
        gap: 8px;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 2rem;
        color: var(--dark-gray);
        font-style: italic;
    }
    
    /* Clear Search Link */
    .clear-search {
        color: var(--primary-color);
        text-decoration: none;
        margin-left: 10px;
        font-size: 0.9rem;
        transition: color 0.2s;
    }
    
    .clear-search:hover {
        color: #2980b9;
        text-decoration: underline;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .container {
            padding: 15px;
            margin: 10px;
        }
        
        table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        th, td {
            padding: 10px;
        }
        
        .action-cell {
            flex-direction: column;
            gap: 5px;
        }
        
        .btn {
            width: 100%;
            margin-right: 0;
            margin-bottom: 5px;
        }
    }
    
    /* Additional style for academy column */
    .academy-name {
        font-weight: 500;
        color: #2c3e50;
    }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <h1>Certificate Requests</h1>
        
        <!-- Search Form -->
        <form method="GET" style="margin-bottom: 1.5rem;">
            <input type="text" name="search_id" placeholder="Search by Request ID" 
                   value="<?php echo htmlspecialchars($search_id); ?>" 
                   style="padding: 0.5rem; width: 250px; border: 1px solid #ccc; border-radius: 4px;">
            <button type="submit" class="btn btn-approve" style="margin-left: 0.5rem;">Search</button>
            <?php if (!empty($search_id)): ?>
                <a href="request_list.php" style="margin-left: 10px;">Clear search</a>
            <?php endif; ?>
        </form>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Student Name</th>
                    <th>Roll No</th>
                    <th>Course</th>
                    <th>Academy</th>
                    <th>Batch Start</th>
                    <th>Batch End</th>
                    <th>Status</th>
                    <th class="action-cell">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($requests)): ?>
                    <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['request_id']); ?></td>
                        <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['roll_no']); ?></td>
                        <td><?php echo htmlspecialchars($request['course']); ?></td>
                        <td class="academy-name"><?php echo htmlspecialchars($request['academy_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['batch_start']); ?></td>
                        <td><?php echo htmlspecialchars($request['batch_end']); ?></td>
                        <td>
                            <span class="status-<?php echo strtolower($request['status']); ?>">
                                <?php echo htmlspecialchars($request['status']); ?>
                            </span>
                        </td>
                        <td class="action-cell">
                            <?php if ($request['status'] == 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id']); ?>">
                                    <button type="submit" name="approve" class="btn btn-approve">Approve</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id']); ?>">
                                    <button type="submit" name="reject" class="btn btn-reject">Reject</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #6c757d;">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="empty-state">
                            <?php echo empty($search_id) ? 'No requests found' : 'No requests match your search'; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>