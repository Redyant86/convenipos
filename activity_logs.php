<?php include 'header.php'; 
if ($_SESSION['role'] != 'admin') {
    header("Location: index.php"); exit;
}
?>

<div class="container">
    <h2><i class="fas fa-history"></i> User Activity Log</h2>

    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>Date & Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Description</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $logs = $pdo->query("
            SELECT l.*, u.username, u.full_name 
            FROM activity_logs l 
            JOIN users u ON l.user_id = u.id 
            ORDER BY l.created_at DESC
        ");
        while($log = $logs->fetch()) {
            echo "<tr>
                <td>{$log['created_at']}</td>
                <td>{$log['full_name']} ({$log['username']})</td>
                <td><strong>{$log['action']}</strong></td>
                <td>{$log['description']}</td>
                <td>{$log['ip_address']}</td>
            </tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>