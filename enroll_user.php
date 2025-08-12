<?php
session_start();
require 'db.php';
include 'header.php';

// Handle user insert
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username   = trim($_POST['username']);
    $password   = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $user_type  = $_POST['user_type'];
    $academy_id = $_POST['academy_id'];

    if (!empty($username) && !empty($password) && !empty($user_type) && !empty($academy_id)) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, user_type, academy_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password, $user_type, $academy_id]);
        $success = "User enrolled successfully!";
    } else {
        $error = "Please fill all fields.";
    }
}

// Fetch academies
try {
    $academies = $pdo->query("SELECT academy_id, academy_name FROM academy")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching academies: " . $e->getMessage());
}

// Fetch users except admin
try {
    $users = $pdo->query("SELECT user_id, username, user_type FROM users WHERE user_type != 'admin'")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching users: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Enroll User</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; }
        .container {
            display: flex;
            padding: 20px;
            gap: 20px;
        }
        .form-container, .list-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            flex: 1;
        }
        form label { display: block; margin-top: 10px; font-weight: bold; }
        form input, form select {
            width: 100%; padding: 8px; margin-top: 5px;
            border: 1px solid #ccc; border-radius: 5px;
        }
        button {
            margin-top: 15px;
            padding: 10px 15px; background: #28a745; border: none;
            color: white; border-radius: 5px; cursor: pointer;
        }
        button:hover { background: #218838; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; border-bottom: 1px solid #ddd; text-align: left; }
        .delete-btn {
            background: #dc3545; color: white; border: none; padding: 5px 8px;
            border-radius: 4px; cursor: pointer;
        }
        .delete-btn:hover { background: #c82333; }
    </style>
</head>
<body>

<div class="container">
    <!-- Enroll User Form -->
    <div class="form-container">
        <h2>Enroll New User</h2>
        <?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <form method="POST">
            <label>Username:</label>
            <input type="text" name="username" required>

            <label>Password:</label>
            <input type="password" name="password" required>

            <label>User Type:</label>
            <select name="user_type" required>
                <option value="academy">Academy</option>
                <option value="user">User</option>
            </select>

            <label>Academy:</label>
            <select name="academy_id" required>
                <?php foreach ($academies as $academy): ?>
                    <option value="<?= $academy['academy_id'] ?>"><?= htmlspecialchars($academy['academy_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Enroll User</button>
        </form>
    </div>

    <!-- User List -->
    <div class="list-container">
        <h2>Existing Users</h2>
        <table>
            <tr>
                <th>Username</th>
                <th>Type</th>
                <th>Action</th>
            </tr>
            <?php foreach ($users as $user): ?>
            <tr id="user-<?= $user['user_id'] ?>">
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['user_type']) ?></td>
                <td><button class="delete-btn" onclick="deleteUser(<?= $user['user_id'] ?>)">Delete</button></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
function deleteUser(id) {
    if (!confirm("Are you sure you want to delete this user?")) return;
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "delete_user.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
        if (xhr.status === 200 && xhr.responseText === "success") {
            document.getElementById("user-" + id).remove();
        } else {
            alert("Error deleting user: " + xhr.responseText);
        }
    };
    xhr.send("user_id=" + id);
}
</script>

</body>
</html>
