<?php
// If form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_user = $_POST['admin_user'];
    $admin_pass = $_POST['admin_pass'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];

    try {
        // Connect to DB
        $pdo = new PDO("mysql:host=localhost;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create tables
        $sql = "
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255),
    name VARCHAR(100),
    email VARCHAR(100),
    academy_id VARCHAR(100),
    user_type ENUM('admin','academy','user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS academy (
    academy_id INT AUTO_INCREMENT PRIMARY KEY,
    academy_name VARCHAR(100),
    address TEXT,
    unicode VARCHAR(50) UNIQUE,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS course (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100),
    description TEXT
);

CREATE TABLE IF NOT EXISTS batch (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    academy_id INT,
    batch_name VARCHAR(100),
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (course_id) REFERENCES course(course_id),
    FOREIGN KEY (academy_id) REFERENCES academy(academy_id)
);

CREATE TABLE IF NOT EXISTS enrollment_request (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100),
    course_id INT,
    batch_id INT,
    academy_id INT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    FOREIGN KEY (course_id) REFERENCES course(course_id),
    FOREIGN KEY (batch_id) REFERENCES batch(batch_id),
    FOREIGN KEY (academy_id) REFERENCES academy(academy_id)
);

CREATE TABLE IF NOT EXISTS certificate (
    certificate_id INT AUTO_INCREMENT PRIMARY KEY,
    certificate_no VARCHAR(50) UNIQUE,
    student_name VARCHAR(100),
    course_id INT,
    batch_id INT,
    academy_id INT,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES course(course_id),
    FOREIGN KEY (batch_id) REFERENCES batch(batch_id),
    FOREIGN KEY (academy_id) REFERENCES academy(academy_id)
);

CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(255) NOT NULL,
    roll_no VARCHAR(50) NOT NULL,
    phone_no VARCHAR(20) NOT NULL,
    course_id INT NOT NULL,
    batch_start DATE NOT NULL,
    batch_end DATE NOT NULL,
    academy_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES course(course_id) ON DELETE CASCADE,
    FOREIGN KEY (academy_id) REFERENCES academy(academy_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reports (
    request_id VARCHAR(50) PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    roll_no VARCHAR(50) NOT NULL,
    course VARCHAR(100) NOT NULL,
    batch_start DATE NOT NULL,
    batch_end DATE NOT NULL,
    academy_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";
        $pdo->exec($sql);

        // Insert admin user
        $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, user_type) VALUES (?, ?, 'admin')");
        $stmt->execute([$admin_user, $hashed_pass]);

         // Create db.php file for future connections
        $dbFileContent = "<?php\n".
            "\$db_host = 'localhost';\n".
            "\$db_name = '$db_name';\n".
            "\$db_user = '$db_user';\n".
            "\$db_pass = '$db_pass';\n".
            "try {\n".
            "    \$pdo = new PDO(\"mysql:host=\$db_host;dbname=\$db_name\", \$db_user, \$db_pass);\n".
            "    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n".
            "} catch (PDOException \$e) {\n".
            "    die('DB Connection failed: ' . \$e->getMessage());\n".
            "}\n".
            "?>";

        file_put_contents('db.php', $dbFileContent);

        // Redirect to login
        header("Location: login.php");
        exit();
    } catch (PDOException $e) {
        $error = "Setup failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Page</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; }
        .container { max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 8px; margin-top: 50px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);}
        input { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #28a745; color: white; border: none; padding: 10px; width: 100%; cursor: pointer; }
        button:hover { background: #218838; }
        .error { color: red; }
    </style>
</head>
<body>
<div class="container">
    <h2>System Setup</h2>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <label>Admin Username:</label>
        <input type="text" name="admin_user" required>
        
        <label>Admin Password:</label>
        <input type="password" name="admin_pass" required>

        <label>Database Name:</label>
        <input type="text" name="db_name" required>

        <label>Database User:</label>
        <input type="text" name="db_user" required>

        <label>Database Password:</label>
        <input type="password" name="db_pass" required>

        <button type="submit">Setup</button>
    </form>
</div>
</body>
</html>
