
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
// ==================== EXPORT TO EXCEL (MUST BE FIRST) ====================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Deliveries_' . date('Y-m-d_H-i') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Supplier Name', 'Invoice #', 'DR #', 'Product Name', 'Barcode', 'Quantity', 'Cost per Unit', 'Total Cost']);

    include 'db_connect.php';
    $stmt = $pdo->query("
        SELECT d.delivery_date, d.supplier_name, d.invoice_number, d.dr_number, 
               p.name, p.barcode, d.quantity, d.cost_per_unit
        FROM deliveries d 
        JOIN products p ON d.product_id = p.id 
        ORDER BY d.delivery_date DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            date('Y-m-d H:i', strtotime($row['delivery_date'])),
            $row['supplier_name'],
            $row['invoice_number'] ?? '',
            $row['dr_number'],
            $row['name'],
            "'" . $row['barcode'] ?? '',
            $row['quantity'],
            $row['cost_per_unit'],
            $row['quantity'] * $row['cost_per_unit']
        ]);
    }
    fclose($output);
    exit;
}

// ==================== NORMAL PAGE ====================
include 'header.php';

// ==================== ADD DELIVERY ====================
if (isset($_POST['add_delivery'])) {
    $product_id = (int)$_POST['product_id'];
    $qty = (int)$_POST['quantity'];
    $cost = (float)$_POST['cost_per_unit'];

    $pdo->prepare("INSERT INTO deliveries (supplier_name, invoice_number, dr_number, product_id, quantity, cost_per_unit) 
                   VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$_POST['supplier_name'], $_POST['invoice_number'], $_POST['dr_number'], $product_id, $qty, $cost]);

    $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
        ->execute([$qty, $product_id]);

    echo '<div class="alert alert-success">✅ Delivery recorded and stock updated!</div>';
}

// ==================== EDIT DELIVERY ====================
if (isset($_POST['edit_delivery'])) {
    $id = (int)$_POST['id'];
    $old = $pdo->prepare("SELECT quantity, product_id FROM deliveries WHERE id = ?");
    $old->execute([$id]);
    $oldData = $old->fetch();

    $pdo->prepare("UPDATE deliveries SET supplier_name=?, invoice_number=?, dr_number=?, quantity=?, cost_per_unit=? WHERE id=?")
        ->execute([$_POST['supplier_name'], $_POST['invoice_number'], $_POST['dr_number'], $_POST['quantity'], $_POST['cost_per_unit'], $id]);

    $diff = $_POST['quantity'] - $oldData['quantity'];
    $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
        ->execute([$diff, $oldData['product_id']]);

    echo '<div class="alert alert-success">✅ Delivery updated successfully!</div>';
}

// ==================== DELETE DELIVERY ====================
if (isset($_POST['delete_delivery'])) {
    $id = (int)$_POST['id'];
    $del = $pdo->prepare("SELECT quantity, product_id FROM deliveries WHERE id = ?");
    $del->execute([$id]);
    $d = $del->fetch();

    $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
        ->execute([$d['quantity'], $d['product_id']]);

    $pdo->prepare("DELETE FROM deliveries WHERE id = ?")->execute([$id]);
    echo '<div class="alert alert-danger">🗑️ Delivery deleted and stock rolled back!</div>';
}

// ==================== BARCODE SCAN ====================
if (isset($_GET['barcode'])) {
    $barcode = trim($_GET['barcode']);
    $p = $pdo->prepare("SELECT id, cost FROM products WHERE barcode = ? LIMIT 1");
    $p->execute([$barcode]);
    if ($prod = $p->fetch()) {
        $_SESSION['barcode_product'] = $prod;
    }
    header("Location: deliveries.php"); exit;
}
?>

<div class="container">
    <h2><i class="fas fa-truck-loading"></i> Deliveries / Restocking</h2>

    <!-- Export Buttons -->
    <div class="mb-4">
        <a href="deliveries.php?export=excel" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export to Excel
        </a>
        <button onclick="exportToPDF()" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> Export to PDF
        </button>
    </div>

    <!-- Add Delivery Form -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">Record New Delivery</div>
        <div class="card-body">
            <form method="post">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-primary"><i class="fas fa-barcode"></i></span>
                            <input type="text" id="barcode-input" class="form-control" placeholder="SCAN BARCODE HERE..." autofocus>
                        </div>
                    </div>
                    <div class="col-md-3"><input type="text" name="supplier_name" class="form-control" placeholder="Supplier Name" required></div>
                    <div class="col-md-2"><input type="text" name="invoice_number" class="form-control" placeholder="Invoice #"></div>
                    <div class="col-md-2"><input type="text" name="dr_number" class="form-control" placeholder="DR #" required></div>
                    <div class="col-md-3">
                        <select name="product_id" id="product_select" class="form-select" required>
                            <option value="">Select Product</option>
                            <?php
                            $prods = $pdo->query("SELECT id, name, barcode FROM products ORDER BY name");
                            while($p = $prods->fetch()) echo "<option value='{$p['id']}'>{$p['name']} ({$p['barcode']})</option>";
                            ?>
                        </select>
                    </div>
                    <div class="col-md-1"><input type="number" name="quantity" class="form-control" placeholder="Qty" min="1" required></div>
                    <div class="col-md-2"><input type="number" step="0.01" name="cost_per_unit" id="cost_field" class="form-control" placeholder="Cost/Unit" required></div>
                    <div class="col-12">
                        <button type="submit" name="add_delivery" class="btn btn-success btn-lg">Record Delivery & Update Stock</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delivery History -->
    <h4>Delivery History</h4>
    <table class="table table-bordered table-hover" id="deliveryTable">
        <thead class="table-dark">
            <tr>
                <th>Date</th><th>Supplier</th><th>Invoice</th><th>DR #</th><th>Product</th>
                <th class="text-center">Qty</th><th class="text-center">Cost/Unit</th><th class="text-center">Total Cost</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->query("SELECT d.*, p.name, p.barcode FROM deliveries d JOIN products p ON d.product_id = p.id ORDER BY d.delivery_date DESC");
        while($d = $stmt->fetch()) {
            echo "<tr>
                <td>".date('M d, Y h:i A', strtotime($d['delivery_date']))."</td>
                <td>{$d['supplier_name']}</td>
                <td>{$d['invoice_number']}</td>
                <td>{$d['dr_number']}</td>
                <td>{$d['name']} <small>({$d['barcode']})</small></td>
                <td class='text-center fw-bold'>{$d['quantity']}</td>
                <td class='text-center'>₱".number_format($d['cost_per_unit'],2)."</td>
                <td class='text-center'>₱".number_format($d['quantity']*$d['cost_per_unit'],2)."</td>
                <td class='text-center'>
                    <button class='btn btn-warning btn-sm edit-btn' data-id='{$d['id']}' data-supplier='{$d['supplier_name']}' 
                        data-invoice='{$d['invoice_number']}' data-dr='{$d['dr_number']}' data-product='{$d['product_id']}' 
                        data-qty='{$d['quantity']}' data-cost='{$d['cost_per_unit']}'>Edit</button>
                    <form method='post' class='d-inline' onsubmit=\"return confirm('Delete this delivery? Stock will be rolled back.')\">
                        <input type='hidden' name='id' value='{$d['id']}'>
                        <button type='submit' name='delete_delivery' class='btn btn-danger btn-sm'>Delete</button>
                    </form>
                </td>
            </tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5>Edit Delivery</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="text" name="supplier_name" id="edit_supplier" class="form-control mb-2" required>
                    <div class="row g-2">
                        <div class="col-6"><input type="text" name="invoice_number" id="edit_invoice" class="form-control"></div>
                        <div class="col-6"><input type="text" name="dr_number" id="edit_dr" class="form-control" required></div>
                    </div>
                    <select name="product_id" id="edit_product" class="form-select my-2" required></select>
                    <div class="row g-2">
                        <div class="col-6"><input type="number" name="quantity" id="edit_qty" class="form-control" required></div>
                        <div class="col-6"><input type="number" step="0.01" name="cost_per_unit" id="edit_cost" class="form-control" required></div>
                    </div>
                    <button type="submit" name="edit_delivery" class="btn btn-primary w-100 mt-3">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- PDF Libraries -->
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
// Barcode Scanner
document.getElementById('barcode-input').addEventListener('keypress', function(e) {
    if (e.key === "Enter") {
        window.location = `deliveries.php?barcode=${encodeURIComponent(this.value.trim())}`;
    }
});

// Auto-fill from barcode
<?php if (isset($_SESSION['barcode_product'])): ?>
    document.getElementById('product_select').value = <?= $_SESSION['barcode_product']['id'] ?>;
    document.getElementById('cost_field').value = <?= $_SESSION['barcode_product']['cost'] ?>;
    <?php unset($_SESSION['barcode_product']); ?>
<?php endif; ?>

// Edit Modal
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_supplier').value = this.dataset.supplier;
        document.getElementById('edit_invoice').value = this.dataset.invoice;
        document.getElementById('edit_dr').value = this.dataset.dr;
        document.getElementById('edit_product').value = this.dataset.product;
        document.getElementById('edit_qty').value = this.dataset.qty;
        document.getElementById('edit_cost').value = this.dataset.cost;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});

// ==================== PDF EXPORT ====================
function exportToPDF() {
    html2canvas(document.getElementById('deliveryTable'), {scale: 2}).then(canvas => {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        const imgData = canvas.toDataURL('image/png');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

        pdf.setFontSize(16);
        pdf.text("DELIVERIES / RESTOCKING REPORT", pdfWidth/2, 15, { align: "center" });
        pdf.setFontSize(11);
        pdf.text("Generated: <?= date('Y-m-d H:i') ?>", pdfWidth/2, 22, { align: "center" });
        pdf.addImage(imgData, 'PNG', 10, 30, pdfWidth-20, pdfHeight);
        pdf.save("Deliveries_Report_<?= date('Y-m-d') ?>.pdf");
    });
}
</script>

<?php include 'footer.php'; ?>