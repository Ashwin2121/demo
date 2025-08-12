<?php
require 'db.php';

if (!empty($_POST['user_id'])) {
    $user_id = (int) $_POST['user_id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    if ($stmt->execute([$user_id])) {
        echo "success";
    } else {
        echo "error";
    }
}
?>
