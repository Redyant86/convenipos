<?php include 'header.php'; 
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}


$filter = $_GET['filter'] ?? 'all';
$tab    = $_GET['tab'] ?? 'summary';
$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';

$where = "";
$params = [];

if ($from && $to) {
    $where = "WHERE s.sale_date BETWEEN ? AND ?";
    $params = [$from . ' 00:00:00', $to . ' 23:59:59'];
} elseif ($filter == 'today') {
    $where = "WHERE DATE(s.sale_date) = CURDATE()";
} elseif ($filter == 'month') {
    $where = "WHERE MONTH(s.sale_date) = MONTH(CURDATE()) AND YEAR(s.sale_date) = YEAR(CURDATE())";
}
?>

<div class="container">
    <h2><i class="fas fa-chart-line"></i> Product Issuance Monitoring</h2>

    <!-- Date Range Picker -->
    <form method="GET" class="row g-3 mb-4 align-items-end">
        <input type="hidden" name="tab" value="<?= $tab ?>">
        <div class="col-md-3">
            <label>From Date</label>
            <input type="date" name="from" class="form-control" value="<?= $from ?>">
        </div>
        <div class="col-md-3">
            <label>To Date</label>
            <input type="date" name="to" class="form-control" value="<?= $to ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
        </div>
        <div class="col-md-4">
            <a href="sold_products.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a href="?tab=summary&from=<?= $from ?>&to=<?= $to ?>" class="nav-link <?= $tab=='summary'?'active':'' ?>">Summary by Product</a></li>
        <li class="nav-item"><a href="?tab=details&from=<?= $from ?>&to=<?= $to ?>" class="nav-link <?= $tab=='details'?'active':'' ?>">Transaction Details (Invoice #)</a></li>
    </ul>

    <?php if ($tab == 'summary'): ?>
        <!-- SUMMARY TABLE (same as before) -->
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Product Name</th><th>Category</th><th class="text-center">Total Qty Sold</th>
                    <th class="text-center">Total Revenue</th><th class="text-center">Times Sold</th><th>Last Sold</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $stmt = $pdo->prepare("
                SELECT p.name, p.category, SUM(si.quantity) as total_qty,
                       SUM(si.quantity * si.price) as total_revenue,
                       COUNT(DISTINCT s.id) as times_sold,
                       MAX(s.sale_date) as last_sold
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                JOIN sales s ON si.sale_id = s.id
                $where
                GROUP BY p.id ORDER BY total_qty DESC
            ");
            $stmt->execute($params);
            while($row = $stmt->fetch()) {
                echo "<tr>
                    <td>{$row['name']}</td>
                    <td>{$row['category']}</td>
                    <td class='text-center fw-bold'>{$row['total_qty']}</td>
                    <td class='text-center'>₱".number_format($row['total_revenue'],2)."</td>
                    <td class='text-center'>{$row['times_sold']}</td>
                    <td>".date('M d, Y', strtotime($row['last_sold']))."</td>
                </tr>";
            }
            ?>
            </tbody>
        </table>

    <?php else: ?>
        <!-- TRANSACTION DETAILS WITH CLICKABLE INVOICE -->
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th class="text-center">Qty</th>
                    <th class="text-center">Unit Price</th>
                    <th class="text-center">Line Total</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $stmt = $pdo->prepare("
                SELECT s.id as sale_id, s.sale_date, p.name, p.category, si.quantity, si.price
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                JOIN sales s ON si.sale_id = s.id
                $where
                ORDER BY s.sale_date DESC
            ");
            $stmt->execute($params);
            while($row = $stmt->fetch()) {
                $invoice = str_pad($row['sale_id'], 6, '0', STR_PAD_LEFT);
                echo "<tr>
                    <td>
                        <a href='receipt.php?id={$row['sale_id']}' target='_blank' class='text-primary fw-bold'>
                            #{$invoice}
                        </a>
                    </td>
                    <td>".date('M d, Y h:i A', strtotime($row['sale_date']))."</td>
                    <td>{$row['name']}</td>
                    <td>{$row['category']}</td>
                    <td class='text-center fw-bold'>{$row['quantity']}</td>
                    <td class='text-center'>₱".number_format($row['price'],2)."</td>
                    <td class='text-center'>₱".number_format($row['quantity']*$row['price'],2)."</td>
                </tr>";
            }
            ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>