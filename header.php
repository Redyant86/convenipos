<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db_connect.php';
include 'functions.php';

$current_page = basename($_SERVER['PHP_SELF']);

// Redirect to login if not logged in
if ($current_page != 'login.php' && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ConveniPOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-store"></i> ConveniPOS</a>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="d-flex align-items-center">
            <!-- Profile Picture -->
            <div class="me-3">
                <?php if (!empty($_SESSION['profile_photo'])): ?>
                    <img src="uploads/profile/<?= $_SESSION['profile_photo'] ?>" width="32" height="32" class="rounded-circle border" style="object-fit:cover;">
                <?php else: ?>
                    <i class="fas fa-user-circle fa-2x text-light"></i>
                <?php endif; ?>
            </div>

            <span class="text-light me-3">
                <?= $_SESSION['full_name'] ?> 
                (<small><?= ucfirst($_SESSION['role']) ?></small>)
            </span>

            <!-- Navigation -->
            <a href="index.php" class="btn btn-outline-light me-2 <?= $current_page == 'index.php' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="pos.php" class="btn btn-outline-light me-2 <?= $current_page == 'pos.php' ? 'active' : '' ?>"><i class="fas fa-cash-register"></i> POS</a>

            <?php if ($_SESSION['role'] == 'admin'): ?>
            <a href="sales.php" class="btn btn-outline-light me-2 <?= $current_page == 'sales.php' ? 'active' : '' ?>"><i class="fas fa-receipt"></i> Sales</a>
            <a href="inventory.php" class="btn btn-outline-light me-2 <?= $current_page == 'inventory.php' ? 'active' : '' ?>"><i class="fas fa-boxes"></i> Inventory</a>
            <a href="deliveries.php" class="btn btn-outline-light me-2 <?= $current_page == 'deliveries.php' ? 'active' : '' ?>"><i class="fas fa-truck-loading"></i> Deliveries</a>
            <a href="products.php" class="btn btn-outline-light me-2 <?= $current_page == 'products.php' ? 'active' : '' ?>"><i class="fas fa-box"></i> Products</a>
            <a href="categories.php" class="btn btn-outline-light me-2 <?= $current_page == 'categories.php' ? 'active' : '' ?>"><i class="fas fa-tags"></i> Categories</a>
            <a href="users.php" class="btn btn-outline-light me-2 <?= $current_page == 'users.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Users</a>
            <a href="activity_logs.php" class="btn btn-outline-light me-2 <?= $current_page == 'activity_logs.php' ? 'active' : '' ?>"><i class="fas fa-history"></i> Activity Log</a>
            <?php endif; ?>

            <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                <i class="fas fa-key"></i> Change Password
            </button>

            <a href="logout.php" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        <?php endif; ?>
    </div>
</nav>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="change_password.php">
                    <div class="mb-3">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>