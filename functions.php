<?php
function log_activity($user_id, $action, $description) {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $description, $ip]);
}
?>