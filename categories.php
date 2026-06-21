
<?php include 'header.php'; 

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Handle Add
if (isset($_POST['add_category'])) {
    try {
        $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([trim($_POST['name'])]);
        echo '<div class="alert alert-success">✅ Category added!</div>';
    } catch(Exception $e) {
        echo '<div class="alert alert-danger">Category already exists!</div>';
    }
}

// Handle Edit
if (isset($_POST['update_category'])) {
    $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?")
        ->execute([trim($_POST['name']), $_POST['id']]);
    echo '<div class="alert alert-success">✅ Category updated!</div>';
}

// Handle Delete
if (isset($_POST['delete_category'])) {
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$_POST['id']]);
    echo '<div class="alert alert-danger">🗑️ Category deleted!</div>';
}
?>

<div class="container">
    <h2><i class="fas fa-tags"></i> Manage Categories</h2>

    <!-- Add New Category -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Add New Category</div>
        <div class="card-body">
            <form method="post">
                <div class="input-group">
                    <input type="text" name="name" class="form-control" placeholder="Category Name (e.g. Electronics)" required>
                    <button type="submit" name="add_category" class="btn btn-success">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- All Categories Table -->
    <div class="card">
        <div class="card-header bg-dark text-white">All Categories</div>
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Category Name</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
                while($cat = $stmt->fetch()) {
                    echo "<tr>
                        <td>{$cat['id']}</td>
                        <td>{$cat['name']}</td>
                        <td>
                            <button class='btn btn-warning btn-sm edit-cat' 
                                data-id='{$cat['id']}' data-name='{$cat['name']}'>
                                <i class='fas fa-edit'></i> Edit
                            </button>
                            
                            <form method='post' class='d-inline' 
                                onsubmit=\"return confirm('Delete this category?\\nProducts using it will be set to Others.')\">
                                <input type='hidden' name='id' value='{$cat['id']}'>
                                <button type='submit' name='delete_category' class='btn btn-danger btn-sm'>
                                    <i class='fas fa-trash'></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCatModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="id" id="cat_id">
                    <input type="text" name="name" id="cat_name" class="form-control" required>
                    <button type="submit" name="update_category" class="btn btn-primary mt-3">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-cat').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('cat_id').value = btn.dataset.id;
        document.getElementById('cat_name').value = btn.dataset.name;
        new bootstrap.Modal(document.getElementById('editCatModal')).show();
    });
});
</script>

<?php include 'footer.php'; ?>