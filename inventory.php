

<?php

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
// ==================== EXPORT INVENTORY REPORT TO EXCEL (FIXED) ====================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    include 'db_connect.php';   // ← This was missing before

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Inventory_Report_' . date('Y-m-d_H-i') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // CSV Header
    fputcsv($output, ['ID', 'Product Name', 'Category', 'Barcode', 'Selling Price', 'Cost Price', 'Current Stock', 'Min Stock', 'Status', 'Inventory Value']);

    $stmt = $pdo->query("SELECT * FROM products ORDER BY name");
    while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = ($p['stock'] == 0) ? 'Out of Stock' : 
                  ($p['stock'] < $p['min_stock']) ? 'Below Minimum' : 'Good';

        $value = $p['stock'] * $p['cost'];

        fputcsv($output, [
            $p['id'],
            $p['name'],
            $p['category'],
            "'" . $p['barcode'],           // ← Force as TEXT (prevents scientific notation)
            $p['price'],
            $p['cost'],
            $p['stock'],
            $p['min_stock'],
            $status,
            $value
        ]);
    }
    fclose($output);
    exit;   // Important: stop further execution
}
?>

<?php include 'header.php';
?>
<div class="container">
    <h2><i class="fas fa-boxes"></i> Inventory Stock Balance</h2>

    <div class="alert alert-success">
        <strong>Total Inventory Value:</strong> ₱<?= number_format($pdo->query("SELECT SUM(stock * cost) as total FROM products")->fetch()['total'] ?? 0, 2) ?>
    </div>
<?php
    // ==================== CREATE INVENTORY BACKUP ====================
if (isset($_GET['backup'])) {
    $backup_file = "backup/Inventory_Backup_" . date('Y-m-d_H-i-s') . ".csv";
    $dir = "backup";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $output = fopen($backup_file, 'w');
    fputcsv($output, ['Backup Date', date('Y-m-d H:i:s')]);
    fputcsv($output, ['ID', 'Product Name', 'Category', 'Barcode', 'Price', 'Cost', 'Current Stock', 'Min Stock', 'Value']);

    $stmt = $pdo->query("SELECT * FROM products ORDER BY name");
    while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $value = $p['stock'] * $p['cost'];
        fputcsv($output, [
            $p['id'], $p['name'], $p['category'], $p['barcode'],
            $p['price'], $p['cost'], $p['stock'], $p['min_stock'], $value
        ]);
    }
    fclose($output);

    echo '<div class="alert alert-success">
            ✅ Inventory Backup Created Successfully!<br>
            File: <strong>' . $backup_file . '</strong>
          </div>';
}
?>
    <!-- Action Buttons -->
    <div class="mb-4">
        <a href="inventory.php?export=excel" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export Inventory Report
        </a>
        <a href="inventory.php?backup=1" class="btn btn-info">
            <i class="fas fa-download"></i> Create Inventory Backup
        </a>
</div>
<?php
// ==================== SET MIN STOCK ====================
if (isset($_POST['set_min_stock'])) {
    $pdo->prepare("UPDATE products SET min_stock = ? WHERE id = ?")
        ->execute([(int)$_POST['min_stock'], (int)$_POST['id']]);
    echo '<div class="alert alert-success">✅ Minimum stock updated!</div>';
}

// ==================== DELETE PRODUCT ====================
if (isset($_POST['delete_product'])) {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$_POST['id']]);
    echo '<div class="alert alert-danger">🗑️ Product deleted!</div>';
}

// ==================== FILTER ====================
$stockFilter = $_GET['stock'] ?? 'all';
$where = [];

if ($stockFilter == 'low')      $where[] = "stock < min_stock";
elseif ($stockFilter == 'good') $where[] = "stock >= min_stock";
elseif ($stockFilter == 'out')  $where[] = "stock = 0";

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
?>

    <!-- Filters -->
    <div class="btn-group mb-4 w-100">
        <a href="?stock=all" class="btn btn-outline-primary <?= $stockFilter=='all'?'active':'' ?>">All Stocks</a>
        <a href="?stock=low" class="btn btn-outline-warning <?= $stockFilter=='low'?'active':'' ?>">Below Min Stock</a>
        <a href="?stock=good" class="btn btn-outline-success <?= $stockFilter=='good'?'active':'' ?>">Good Stock</a>
        <a href="?stock=out" class="btn btn-outline-danger <?= $stockFilter=='out'?'active':'' ?>">Out of Stock</a>
    </div>

    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>Product Name</th>
                <th>Category</th>
                <th>Barcode</th>
                <th>Price</th>
                <th>Cost</th>
                <th class="text-center">Current Stock</th>
                <th class="text-center">Min Stock</th>
                <th class="text-center">Status</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->query("SELECT * FROM products $whereClause ORDER BY name");
        while($p = $stmt->fetch()) {
            // Explicit "Out of Stock" when stock = 0
            if ($p['stock'] == 0) {
                $status = '<span class="badge bg-danger fs-6">Out of Stock</span>';
            } elseif ($p['stock'] < $p['min_stock']) {
                $status = '<span class="badge bg-warning fs-6">Below Minimum</span>';
            } else {
                $status = '<span class="badge bg-success fs-6">Good</span>';
            }

            echo "<tr>
                <td>{$p['name']}</td>
                <td>{$p['category']}</td>
                <td>{$p['barcode']}</td>
                <td>₱".number_format($p['price'],2)."</td>
                <td>₱".number_format($p['cost'],2)."</td>
                <td class='text-center fw-bold'>{$p['stock']}</td>
                <td class='text-center fw-bold text-primary'>{$p['min_stock']}</td>
                <td class='text-center'>{$status}</td>
                <td class='text-center'>
                    <button class='btn btn-primary btn-sm set-min-btn' 
                        data-id='{$p['id']}' 
                        data-name='{$p['name']}' 
                        data-current='{$p['min_stock']}'>
                        <i class='fas fa-edit'></i>
                    </button>
                    
                    <form method='post' class='d-inline' onsubmit=\"return confirm('Delete this product permanently?')\">
                        <input type='hidden' name='id' value='{$p['id']}'>
                        <button type='submit' name='delete_product' class='btn btn-danger btn-sm'>
                            <i class='fas fa-trash'></i>
                        </button>
                    </form>
                </td>
            </tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<!-- Set Minimum Stock Modal -->
<div class="modal fade" id="minStockModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5>Set Minimum Stock Level</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="id" id="min_id">
                    <p>Product: <strong id="min_name"></strong></p>
                    <p>Current Min: <strong id="current_min"></strong></p>
                    <input type="number" name="min_stock" id="min_value" class="form-control form-control-lg" min="0" required>
                    <button type="submit" name="set_min_stock" class="btn btn-success w-100 mt-3">Save Minimum Stock</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Set Min Stock Modal
document.querySelectorAll('.set-min-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('min_id').value = this.dataset.id;
        document.getElementById('min_name').textContent = this.dataset.name;
        document.getElementById('current_min').textContent = this.dataset.current;
        document.getElementById('min_value').value = this.dataset.current;
        new bootstrap.Modal(document.getElementById('minStockModal')).show();
    });
});
</script>

<?php include 'footer.php'; ?>