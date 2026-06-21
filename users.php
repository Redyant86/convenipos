<?php 
include 'header.php'; 

if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// ==================== ADD NEW USER ====================
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];

    // Handle profile photo upload
    $photo = '';
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $target_dir = "uploads/profile/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $new_name = uniqid() . '.' . strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $target_file = $target_dir . $new_name;
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_file)) {
            $photo = $new_name;
        }
    }

    $pdo->prepare("INSERT INTO users (username, password, role, full_name, profile_photo) 
                   VALUES (?, ?, ?, ?, ?)")
        ->execute([$username, $password, $role, $full_name, $photo]);

    log_activity($_SESSION['user_id'], 'ADD_USER', "Added new user: $username ($role)");
    echo '<div class="alert alert-success">User added successfully!</div>';
}

// ==================== DELETE USER ====================
if (isset($_POST['delete_user'])) {
    $id = (int)$_POST['id'];
    if ($id != $_SESSION['user_id']) { // Prevent self-delete
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        log_activity($_SESSION['user_id'], 'DELETE_USER', "Deleted user ID: $id");
        echo '<div class="alert alert-danger">User deleted successfully!</div>';
    }
}
?>

<div class="container">
    <h2><i class="fas fa-users"></i> Manage Users</h2>

    <!-- Add New User -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Add New User</div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-3"><input name="username" class="form-control" placeholder="Username" required></div>
                    <div class="col-md-3"><input name="password" type="password" class="form-control" placeholder="Password" required></div>
                    <div class="col-md-3"><input name="full_name" class="form-control" placeholder="Full Name" required></div>
                    <div class="col-md-3">
                        <select name="role" class="form-select" required>
                            <option value="cashier">Cashier</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Profile Photo (optional)</label>
                        <input type="file" name="profile_photo" accept="image/*" class="form-control">
                    </div>
                    <div class="col-12">
                        <button name="add_user" class="btn btn-success btn-lg">Add User</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users List -->
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>Photo</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $users = $pdo->query("SELECT * FROM users ORDER BY role DESC, username");
        while($u = $users->fetch()) {
            $photo_html = $u['profile_photo'] 
                ? "<img src='uploads/profile/{$u['profile_photo']}' width='50' height='50' class='rounded-circle'>" 
                : "<i class='fas fa-user-circle fa-2x text-secondary'></i>";

            echo "<tr>
                <td class='text-center'>{$photo_html}</td>
                <td>{$u['username']}</td>
                <td>{$u['full_name']}</td>
                <td><span class='badge bg-".($u['role']=='admin'?'primary':'secondary')."'>".ucfirst($u['role'])."</span></td>
                <td>
                    ".($u['id'] != $_SESSION['user_id'] ? 
                    "<form method='post' class='d-inline' onsubmit=\"return confirm('Delete this user?')\">
                        <input type='hidden' name='id' value='{$u['id']}'>
                        <button type='submit' name='delete_user' class='btn btn-danger btn-sm'>Delete</button>
                    </form>" : "<span class='text-muted small'>Current User</span>")."
                </td>
            </tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>