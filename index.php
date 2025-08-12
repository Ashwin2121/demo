<?php
require_once 'db.php';

if (!isset($pdo)) {
    die("Database connection failed");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        /* Your existing CSS styles remain the same */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .search-form {
            margin-bottom: 30px;
            text-align: center;
        }
        .search-form input {
            padding: 12px;
            width: 70%;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .search-form button {
            padding: 12px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        .search-form button:hover {
            background-color: #45a049;
        }
        .certificate {
            border: 2px solid #4CAF50;
            padding: 25px;
            margin-top: 20px;
            background-color: #fff;
            border-radius: 5px;
        }
        .certificate h2 {
            text-align: center;
            color: #4CAF50;
            margin-top: 0;
        }
        .certificate-details {
            margin-top: 20px;
        }
        .certificate-details p {
            margin: 10px 0;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .valid {
            color: #4CAF50;
            font-weight: bold;
            text-align: center;
            margin-top: 15px;
            font-size: 18px;
        }
        .invalid {
            color: #f44336;
            font-weight: bold;
            text-align: center;
            padding: 20px;
        }
        .no-certificate {
            text-align: center;
            color: #666;
            font-style: italic;
            margin-top: 20px;
        }
        .error {
            color: #f44336;
            text-align: center;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Certificate Verification</h1>
        
        <form method="GET" action="" class="search-form">
            <input type="text" name="certificate_no" placeholder="Enter Certificate Number" 
                   value="<?php echo isset($_GET['certificate_no']) ? htmlspecialchars($_GET['certificate_no']) : ''; ?>" required>
            <button type="submit">Verify</button>
        </form>
        
        <?php
        if (isset($_GET['certificate_no']) && !empty($_GET['certificate_no'])) {
            $certificate_no = trim($_GET['certificate_no']);
            
            try {
                // First get the certificate with course_id
                $stmt = $pdo->prepare("
                    SELECT * FROM certificate 
                    WHERE certificate_no = :certificate_no
                ");
                $stmt->bindParam(':certificate_no', $certificate_no, PDO::PARAM_STR);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Now get the course name
                    $courseStmt = $pdo->prepare("
                        SELECT course_name FROM course 
                        WHERE course_id = :course_id
                    ");
                    $courseStmt->bindParam(':course_id', $certificate['course_id'], PDO::PARAM_STR);
                    $courseStmt->execute();
                    $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Display certificate
                    echo '<div class="certificate">';
                    echo '<h2>CERTIFICATE OF COMPLETION</h2>';
                    echo '<div class="certificate-details">';
                    echo '<p><strong>Certificate Number:</strong> ' . htmlspecialchars($certificate['certificate_no']) . '</p>';
                    echo '<p><strong>Student Name:</strong> ' . htmlspecialchars($certificate['student_name']) . '</p>';
                    
                    // Display course name if found
                    if ($course && !empty($course['course_name'])) {
                        echo '<p><strong>Course:</strong> ' . htmlspecialchars($course['course_name']) . '</p>';
                    } else {
                        echo '<p><strong>Course:</strong> Not specified</p>';
                    }
                    
                    echo '<p><strong>Completion Date:</strong> ' . htmlspecialchars($certificate['batch_end']) . '</p>';
                    
                    if (isset($certificate['issued_by'])) {
                        echo '<p><strong>Issued By:</strong> ' . htmlspecialchars($certificate['issued_by']) . '</p>';
                    }
                    
                    echo '</div>';
                    echo '<div class="valid">✓ This certificate is valid and verified</div>';
                    echo '</div>';
                } else {
                    echo '<div class="invalid">Certificate not found. Please check the number and try again.</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="error">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            echo '<div class="no-certificate">Enter a certificate number above to verify its authenticity</div>';
        }
        ?>
    </div>
</body>
</html>