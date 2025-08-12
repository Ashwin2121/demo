<?php
session_start();
require 'db.php'; // DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $type     = $_POST['type'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND user_type = ?");
    $stmt->execute([$username, $type]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];

        // Store academy_id in session if available
        if (!empty($user['academy_id'])) {
            $_SESSION['academy_id'] = $user['academy_id'];
        }

        if ($type === 'admin') {
            header("Location: admin.php");
        } elseif ($type === 'academy') {
            header("Location: academy.php");
        } elseif ($type === 'user') {
            header("Location: user.php");
        }
        exit();
    } else {
        $error = "Invalid credentials or type.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body { font-family: Arial; background: #f0f0f0; }
        .container { max-width: 400px; margin: auto; background: white; padding: 20px; margin-top: 50px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, select { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #007bff; color: white; border: none; padding: 10px; width: 100%; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
<div class="container">
    <h2>Login</h2>
    <?php 
    if (!empty($_GET['setup']) && $_GET['setup'] === 'success') {
        echo "<p class='success'>Setup completed successfully. Please log in.</p>";
    }
    if (!empty($error)) echo "<p class='error'>$error</p>"; 
    ?>
    <form method="POST">
        <label>Username:</label>
        <input type="text" name="username" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Type:</label>
        <select name="type" required>
            <option value="">-- Select Type --</option>
            <option value="admin">Admin</option>
            <option value="academy">Academy</option>
            <option value="user">User</option>
        </select>

        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
