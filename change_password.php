<?php
session_start();
include 'db_connect.php';
include 'functions.php';

if (isset($_POST['current_password'])) {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (password_verify($_POST['current_password'], $user['password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            $newPass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                ->execute([$newPass, $_SESSION['user_id']]);

            log_activity($_SESSION['user_id'], 'CHANGE_PASSWORD', 'User changed their password');
            echo '<div class="alert alert-success text-center mt-5">Password changed successfully!</div>';
        } else {
            echo '<div class="alert alert-danger text-center mt-5">New passwords do not match!</div>';
        }
    } else {
        echo '<div class="alert alert-danger text-center mt-5">Current password is incorrect!</div>';
    }
}
?>
<div class="text-center mt-4">
    <a href="index.php" class="btn btn-primary">Back to Dashboard</a>
</div>