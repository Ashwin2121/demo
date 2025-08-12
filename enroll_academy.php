<?php
session_start();
require 'db.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";

// Handle new academy enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $academy_name = trim($_POST['academy_name']);
    $address = trim($_POST['address']);
    $unicode = trim($_POST['unicode']);

    if (!empty($academy_name) && !empty($address) && !empty($unicode)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO academy (academy_name, address, unicode) VALUES (?, ?, ?)");
            $stmt->execute([$academy_name, $address, $unicode]);
            $message = "<p class='success'>Academy enrolled successfully!</p>";
        } catch (PDOException $e) {
            $message = "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        $message = "<p class='error'>All fields are required.</p>";
    }
}

// Handle AJAX delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM academy WHERE academy_id = ?");
        $stmt->execute([$id]);
        echo "success";
    } catch (PDOException $e) {
        echo "error";
    }
    exit;
}

// Fetch all academies for the list
try {
    $academies = $pdo->query("SELECT academy_id, academy_name, unicode FROM academy ORDER BY academy_id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $academies = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Enroll Academy</title>
    <style>
        body { font-family: Arial; background: #f8f9fa; margin: 0; }
        .header { background: #343a40; padding: 10px; display: flex; }
        .header a {
            color: white; padding: 10px 15px; text-decoration: none;
            border-right: 1px solid #555;
        }
        .header a:hover { background: #495057; }
        .container { display: flex; gap: 20px; margin: 30px auto; max-width: 1000px; }
        .form-container, .list-container {
            flex: 1; background: white; padding: 20px; border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        input, textarea { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #007bff; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 4px; }
        button:hover { background: #0056b3; }
        .delete-btn { background: red; padding: 5px 8px; }
        .delete-btn:hover { background: darkred; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <!-- Left: Form -->
    <div class="form-container">
        <h2>Enroll New Academy</h2>
        <?= $message; ?>
        <form method="POST">
            <label>Academy Name:</label>
            <input type="text" name="academy_name" required>

            <label>HQ Address:</label>
            <textarea name="address" required></textarea>

            <label>Unique Code:</label>
            <input type="text" name="unicode" required>

            <button type="submit">Enroll Academy</button>
        </form>
    </div>

    <!-- Right: List -->
    <div class="list-container">
        <h2>Available Academies</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Action</th>
            </tr>
            <?php foreach ($academies as $academy): ?>
                <tr id="academy-row-<?= $academy['academy_id']; ?>">
                    <td><?= htmlspecialchars($academy['academy_name']); ?></td>
                    <td><?= htmlspecialchars($academy['unicode']); ?></td>
                    <td>
                        <button class="delete-btn" onclick="deleteAcademy(<?= $academy['academy_id']; ?>)">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
function deleteAcademy(id) {
    if (confirm("Are you sure you want to delete this academy?")) {
        fetch("enroll_academy.php?delete=" + id)
        .then(res => res.text())
        .then(data => {
            if (data.trim() === "success") {
                document.getElementById("academy-row-" + id).remove();
            } else {
                alert("Error deleting academy.");
            }
        })
        .catch(err => alert("Request failed."));
    }
}
</script>

</body>
</html>
