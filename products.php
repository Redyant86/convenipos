<?php 
include 'header.php'; 
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
// ==================== ADD PRODUCT WITH PHOTO ====================
if (isset($_POST['add_product'])) {
    $barcode = trim($_POST['barcode']);
    if (empty($barcode)) {
        do {
            $barcode = '480' . str_pad(rand(100000000, 999999999), 9, '0');
            $check = $pdo->prepare("SELECT id FROM products WHERE barcode = ?");
            $check->execute([$barcode]);
        } while ($check->fetch());
    }

    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "uploads/products/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $new_name = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $new_name;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $photo = $new_name;
        }
    }

    $pdo->prepare("INSERT INTO products (name,barcode,price,cost,stock,category,photo) VALUES (?,?,?,?,?,?,?)")
        ->execute([$_POST['name'], $barcode, $_POST['price'], $_POST['cost'], $_POST['stock'], $_POST['category'], $photo]);

    header("Location: products.php?success=1"); exit;
}

// ==================== UPDATE PRODUCT WITH PHOTO ====================
if (isset($_POST['update_product'])) {
    $photo = $_POST['old_photo'] ?? '';

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "uploads/products/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $new_name = uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $new_name;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            // Delete old photo
            if (!empty($_POST['old_photo']) && file_exists($target_dir . $_POST['old_photo'])) {
                unlink($target_dir . $_POST['old_photo']);
            }
            $photo = $new_name;
        }
    }

    $pdo->prepare("UPDATE products SET name=?, barcode=?, price=?, cost=?, stock=?, category=?, photo=? WHERE id=?")
        ->execute([$_POST['name'], $_POST['barcode'], $_POST['price'], $_POST['cost'], $_POST['stock'], $_POST['category'], $photo, $_POST['id']]);
    
    echo '<div class="alert alert-success text-center">✅ Product updated!</div>';
}

// ==================== DELETE PRODUCT ====================
if (isset($_POST['delete_product'])) {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$_POST['id']]);
    echo '<div class="alert alert-danger text-center">🗑️ Product deleted!</div>';
}
?>

<div class="container mt-4">
    <h2><i class="fas fa-box"></i> Manage Products</h2>

    <!-- Add New Product -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Add New Product</div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-3"><input name="name" class="form-control" placeholder="Product Name" required></div>
                    <div class="col-md-2">
                        <input name="barcode" class="form-control" placeholder="Barcode (leave blank = auto)">
                    </div>
                    <div class="col-md-2"><input name="price" type="number" step="0.01" class="form-control" placeholder="Price" required></div>
                    <div class="col-md-2"><input name="cost" type="number" step="0.01" class="form-control" placeholder="Cost" required></div>
                    <div class="col-md-2"><input name="stock" type="number" class="form-control" placeholder="Stock" required></div>
                    <div class="col-md-3">
                        <select name="category" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php
                            $cats = $pdo->query("SELECT name FROM categories ORDER BY name");
                            while($c = $cats->fetch()) echo "<option value='{$c['name']}'>{$c['name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Product Photo</label>
                        <input type="file" name="photo" accept="image/*" class="form-control">
                    </div>
                    <div class="col-12">
                        <button name="add_product" class="btn btn-success btn-lg">Add Product</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-header bg-dark text-white">All Products</div>
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Barcode</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM products ORDER BY name");
                while($p = $stmt->fetch()) {
                    $photo_html = $p['photo'] ? "<img src='uploads/products/{$p['photo']}' width='50' height='50' style='object-fit:cover; border-radius:5px;'>" : 
                                                "<span class='text-muted'>No Photo</span>";

                    echo "<tr>
                        <td>{$photo_html}</td>
                        <td>{$p['name']}</td>
                        <td>{$p['category']}</td>
                        <td><strong>{$p['barcode']}</strong></td>
                        <td>₱".number_format($p['price'],2)."</td>
                        <td class='fw-bold text-center'>{$p['stock']}</td>
                        <td>
                            <button class='btn btn-warning btn-sm edit-btn' 
                                data-id='{$p['id']}' data-name='{$p['name']}' data-barcode='{$p['barcode']}' 
                                data-price='{$p['price']}' data-cost='{$p['cost']}' data-stock='{$p['stock']}' 
                                data-category='{$p['category']}' data-photo='{$p['photo']}'>Edit</button>
                            
                            <button class='btn btn-info btn-sm barcode-btn ms-2' 
                                data-name='{$p['name']}' data-price='{$p['price']}' data-barcode='{$p['barcode']}'>
                                Print Barcode
                            </button>
                            
                            <form method='post' class='d-inline' onsubmit=\"return confirm('Delete this product?')\">
                                <input type='hidden' name='id' value='{$p['id']}'>
                                <button type='submit' name='delete_product' class='btn btn-danger btn-sm ms-2'>Delete</button>
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

<!-- Edit Modal + Barcode Modal (keep from previous version) -->
 <!-- JsBarcode Library -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<!-- ==================== EDIT MODAL WITH PHOTO ==================== -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="old_photo" id="edit_old_photo">

                    <div class="mb-3 text-center">
                        <img id="current_photo_preview" src="" width="120" height="120" style="object-fit:cover;border-radius:8px;display:none;">
                    </div>

                    <div class="mb-3">
                        <label>Product Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Barcode</label>
                        <input type="text" name="barcode" id="edit_barcode" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Category</label>
                        <select name="category" id="edit_category" class="form-select" required>
                            <?php
                            $cats = $pdo->query("SELECT name FROM categories ORDER BY name");
                            while($c = $cats->fetch()) echo "<option value='{$c['name']}'>{$c['name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label>Selling Price</label>
                            <input type="number" step="0.01" name="price" id="edit_price" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label>Cost Price</label>
                            <input type="number" step="0.01" name="cost" id="edit_cost" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" id="edit_stock" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Change Photo (optional)</label>
                        <input type="file" name="photo" accept="image/*" class="form-control">
                    </div>

                    <button type="submit" name="update_product" class="btn btn-primary w-100">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- ... paste your existing modal and script here ... -->
<!-- ==================== BARCODE MODAL ==================== -->
<div class="modal fade" id="barcodeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Barcode Label</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="barcode-content">
                <svg id="barcode-svg"></svg>
                <h5 id="modal-product-name" class="mt-3"></h5>
                <h4 id="modal-price" class="text-success"></h4>
                <small id="modal-barcode-text" class="text-muted"></small>
            </div>
            <div class="modal-footer">
                <button onclick="printBarcodeLabel()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Label
                </button>
                <button onclick="downloadBarcodeImage()" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Save as Image
                </button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// ==================== EDIT BUTTON WITH PHOTO ====================
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_name').value = this.dataset.name;
        document.getElementById('edit_barcode').value = this.dataset.barcode;
        document.getElementById('edit_price').value = this.dataset.price;
        document.getElementById('edit_cost').value = this.dataset.cost;
        document.getElementById('edit_stock').value = this.dataset.stock;
        document.getElementById('edit_category').value = this.dataset.category;
        document.getElementById('edit_old_photo').value = this.dataset.photo || '';

        // Show current photo preview if exists
        const preview = document.getElementById('current_photo_preview');
        if (this.dataset.photo) {
            preview.src = 'uploads/products/' + this.dataset.photo;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});

// ==================== PRINT BARCODE MODAL ====================
document.querySelectorAll('.barcode-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const name = this.dataset.name;
        const price = parseFloat(this.dataset.price);
        let barcode = this.dataset.barcode || '123456789012';

        document.getElementById('modal-product-name').textContent = name;
        document.getElementById('modal-price').innerHTML = '₱' + price.toFixed(2);
        

        JsBarcode("#barcode-svg", barcode, {
            format: "CODE128",
            lineColor: "#000",
            width: 2.5,
            height: 90,
            fontSize: 18,
            displayValue: true
        });

        new bootstrap.Modal(document.getElementById('barcodeModal')).show();
    });
});


// ==================== DOWNLOAD BARCODE AS IMAGE (PNG) ====================
function downloadBarcodeImage() {
    const svg = document.getElementById('barcode-svg');
    const svgData = new XMLSerializer().serializeToString(svg);
    const canvas = document.createElement("canvas");
    const ctx = canvas.getContext("2d");

    const img = new Image();
    img.onload = function() {
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);

        // Download as PNG
        const link = document.createElement('a');
        link.download = 'barcode_' + document.getElementById('modal-barcode-text').textContent + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    };

    img.src = 'data:image/svg+xml;base64,' + btoa(svgData);
}

function printBarcodeLabel() {
    const content = document.getElementById('barcode-content').innerHTML;
    const original = document.body.innerHTML;
    document.body.innerHTML = `<div style="text-align:center; padding:40px;">${content}</div>`;
    window.print();
    document.body.innerHTML = original;
    location.reload();
}

</script>


<?php include 'footer.php'; ?>