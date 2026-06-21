
<?php include 'header.php'; 
if ($_SESSION['role'] == 'cashier') {
    $where = "WHERE user_id = " . $_SESSION['user_id'];
} else {
    $where = "";
}

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}


// ==================== REFUND SALE ====================
if (isset($_POST['refund_sale'])) {
    $sale_id = (int)$_POST['sale_id'];

    // Get sale items to return stock
    $items = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
    $items->execute([$sale_id]);
    while ($item = $items->fetch()) {
        $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
            ->execute([$item['quantity'], $item['product_id']]);
    }

    // Mark as refunded
    $pdo->prepare("UPDATE sales SET status = 'refunded' WHERE id = ?")
        ->execute([$sale_id]);

    echo '<div class="alert alert-warning">✅ Sale refunded and stock returned!</div>';
}

// ==================== DELETE SALE ====================
if (isset($_POST['delete_sale'])) {
    $sale_id = (int)$_POST['sale_id'];

    // Return stock
    $items = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
    $items->execute([$sale_id]);
    while ($item = $items->fetch()) {
        $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
            ->execute([$item['quantity'], $item['product_id']]);
    }

    $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?")->execute([$sale_id]);
    $pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$sale_id]);

    echo '<div class="alert alert-danger">🗑️ Sale permanently deleted!</div>';
}

// ==================== EDIT CASH TENDERED ====================
if (isset($_POST['edit_sale'])) {
    $sale_id = (int)$_POST['sale_id'];
    $new_cash = (float)$_POST['cash_tendered'];

    $sale = $pdo->prepare("SELECT total FROM sales WHERE id = ?");
    $sale->execute([$sale_id]);
    $total = $sale->fetchColumn();

    $new_change = $new_cash - $total;

    $pdo->prepare("UPDATE sales SET cash_tendered = ?, change_amount = ? WHERE id = ?")
        ->execute([$new_cash, $new_change, $sale_id]);

    echo '<div class="alert alert-success">✅ Sale updated successfully!</div>';
}
?>

<div class="container">
    <h2><i class="fas fa-receipt"></i> Sales & Profit Report</h2>

    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>Date</th>
                <th>Invoice #</th>
                <th>Total</th>
                <th>Profit</th>
                <th>Cash Tendered</th>
                <th>Change</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->query("SELECT * FROM sales ORDER BY sale_date DESC");
        while($s = $stmt->fetch()) {
            $invoice = str_pad($s['id'], 6, '0', STR_PAD_LEFT);
            $status = ($s['status'] == 'refunded') 
                ? '<span class="badge bg-warning">Refunded</span>' 
                : '<span class="badge bg-success">Completed</span>';

            echo "<tr>
                <td>{$s['sale_date']}</td>
                <td><strong>#{$invoice}</strong></td>
                <td>₱".number_format($s['total'],2)."</td>
                <td class='text-success'>₱".number_format($s['profit'],2)."</td>
                <td>₱".number_format($s['cash_tendered'],2)."</td>
                <td>₱".number_format($s['change_amount'],2)."</td>
                <td>{$status}</td>
                <td class='text-center'>
                    <button class='btn btn-warning btn-sm edit-btn' 
                        data-id='{$s['id']}' 
                        data-cash='{$s['cash_tendered']}'>
                        Edit
                    </button>
                    <button class='btn btn-info btn-sm refund-btn ms-1' 
                        data-id='{$s['id']}'>
                        Refund
                    </button>
                    <form method='post' class='d-inline' 
                        onsubmit=\"return confirm('⚠️ Permanently delete this sale? Stock will be returned.')\">
                        <input type='hidden' name='sale_id' value='{$s['id']}'>
                        <button type='submit' name='delete_sale' class='btn btn-danger btn-sm ms-1'>Delete</button>
                    </form>
                </td>
            </tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<!-- Edit Cash Modal -->
<div class="modal fade" id="editModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5>Edit Cash Tendered</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="sale_id" id="edit_sale_id">
                    <label>Cash Tendered</label>
                    <input type="number" step="0.01" name="cash_tendered" id="edit_cash" class="form-control" required>
                    <button type="submit" name="edit_sale" class="btn btn-primary w-100 mt-3">Update Sale</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Edit Modal
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_sale_id').value = this.dataset.id;
        document.getElementById('edit_cash').value = this.dataset.cash;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});

// Refund Confirmation
document.querySelectorAll('.refund-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('Refund this sale?\nStock will be returned to inventory.')) {
            let form = document.createElement('form');
            form.method = 'POST';
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'sale_id';
            input.value = this.dataset.id;
            form.appendChild(input);
            let submit = document.createElement('input');
            submit.type = 'hidden';
            submit.name = 'refund_sale';
            submit.value = '1';
            form.appendChild(submit);
            document.body.appendChild(form);
            form.submit();
        }
    });
});
</script>

<?php include 'footer.php'; ?>