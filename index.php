<?php include 'header.php'; ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
        <button onclick="exportMonthlyPDF()" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> Export Monthly Report to PDF
        </button>
    </div>
    <?php
    // Today Stats
    $today = $pdo->query("SELECT 
        COALESCE(SUM(total),0) as today_sales,
        COALESCE(SUM(profit),0) as today_profit,
        COUNT(*) as today_orders 
        FROM sales WHERE DATE(sale_date) = CURDATE()")->fetch();

    // This Month Summary
    $month = $pdo->query("SELECT 
        COALESCE(SUM(total),0) as month_sales,
        COALESCE(SUM(profit),0) as month_profit,
        COUNT(*) as month_orders 
        FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())")->fetch();

    $totalProducts = $pdo->query("SELECT COUNT(*) as total FROM products")->fetch()['total'];
    
    // Last Month
    $lastMonth = $pdo->query("SELECT 
        COALESCE(SUM(total),0) as last_sales,
        COALESCE(SUM(profit),0) as last_profit
        FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE())-1 AND YEAR(sale_date) = YEAR(CURDATE())")->fetch();

    // This Year
    $year = $pdo->query("SELECT 
        COALESCE(SUM(total),0) as year_sales,
        COALESCE(SUM(profit),0) as year_profit,
        COUNT(*) as year_orders 
        FROM sales WHERE YEAR(sale_date) = YEAR(CURDATE())")->fetch();

    $totalProducts = $pdo->query("SELECT COUNT(*) as total FROM products")->fetch()['total'];
    ?>

    <!-- Today Stats -->
    <div class="row g-3 mb-5">
        <div class="col-md-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h5>Today Sales</h5>
                    <h3>₱<?= number_format($today['today_sales'], 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h5>Today Profit</h5>
                    <h3>₱<?= number_format($today['today_profit'], 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info h-100">
                <div class="card-body">
                    <h5>Today Orders</h5>
                    <h3><?= $today['today_orders'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <h5>Total Products</h5>
                    <h3><?= $totalProducts ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- This Month Summary with Comparison -->
    <div class="card mb-5">
        <div class="card-header bg-dark text-white">
            <i class="fas fa-calendar-month"></i> This Month Summary
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4">
                    <h5 class="text-muted">Total Sales</h5>
                    <h2 class="text-primary">₱<?= number_format($month['month_sales'], 2) ?></h2>
                    <?php 
                    $salesChange = $lastMonth['last_sales'] > 0 ? (($month['month_sales'] - $lastMonth['last_sales']) / $lastMonth['last_sales']) * 100 : 0;
                    $salesColor = $salesChange >= 0 ? 'text-success' : 'text-danger';
                    $salesArrow = $salesChange >= 0 ? '↑' : '↓';
                    ?>
                    <small class="<?= $salesColor ?>"><?= $salesArrow ?> <?= abs(number_format($salesChange, 1)) ?>% from last month</small>
                </div>
                <div class="col-md-4">
                    <h5 class="text-muted">Total Profit</h5>
                    <h2 class="text-success">₱<?= number_format($month['month_profit'], 2) ?></h2>
                    <?php 
                    $profitChange = $lastMonth['last_profit'] > 0 ? (($month['month_profit'] - $lastMonth['last_profit']) / $lastMonth['last_profit']) * 100 : 0;
                    $profitColor = $profitChange >= 0 ? 'text-success' : 'text-danger';
                    $profitArrow = $profitChange >= 0 ? '↑' : '↓';
                    ?>
                    <small class="<?= $profitColor ?>"><?= $profitArrow ?> <?= abs(number_format($profitChange, 1)) ?>% from last month</small>
                </div>
                <div class="col-md-4">
                    <h5 class="text-muted">Total Orders</h5>
                    <h2 class="text-info"><?= $month['month_orders'] ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Yearly Summary -->
    <div class="card mb-5">
        <div class="card-header bg-dark text-white">
            <i class="fas fa-calendar"></i> This Year Summary (<?= date('Y') ?>)
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4">
                    <h5 class="text-muted">Total Sales</h5>
                    <h2 class="text-primary">₱<?= number_format($year['year_sales'], 2) ?></h2>
                </div>
                <div class="col-md-4">
                    <h5 class="text-muted">Total Profit</h5>
                    <h2 class="text-success">₱<?= number_format($year['year_profit'], 2) ?></h2>
                </div>
                <div class="col-md-4">
                    <h5 class="text-muted">Total Orders</h5>
                    <h2 class="text-info"><?= $year['year_orders'] ?></h2>
                </div>
            </div>
        </div>
    </div>
    <!-- ==================== LOW STOCK ALERT (FIXED) ==================== -->
<?php
// Always define the variable to prevent undefined error
$lowStockProducts = $pdo->query("
    SELECT name, stock, min_stock, category 
    FROM products 
    WHERE stock < min_stock 
    ORDER BY stock ASC
")->fetchAll();

$lowStockCount = count($lowStockProducts);
?>

<?php if($lowStockCount > 0): ?>
<div class="alert alert-danger d-flex justify-content-between align-items-center" 
     style="cursor:pointer;" onclick="showLowStockModal()">
    <div>
        <i class="fas fa-exclamation-triangle"></i> 
        <strong><?= $lowStockCount ?> product<?= $lowStockCount > 1 ? 's' : '' ?> 
        are below minimum stock!</strong>
    </div>
    <small class="text-muted">Click to view details →</small>
</div>
<?php endif; ?>

<!-- ==================== LOW STOCK MODAL ==================== -->
<div class="modal fade" id="lowStockModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5>Products Below Minimum Stock Level</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th class="text-center">Current Stock</th>
                            <th class="text-center">Minimum Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($lowStockProducts as $p): ?>
                        <tr class="<?= $p['stock'] == 0 ? 'table-danger' : 'table-warning' ?>">
                            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                            <td><?= htmlspecialchars($p['category']) ?></td>
                            <td class="text-center fw-bold"><?= $p['stock'] ?></td>
                            <td class="text-center fw-bold text-primary"><?= $p['min_stock'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <a href="inventory.php" class="btn btn-primary">Go to Full Inventory</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
    
    <!-- Charts Section -->
    <div class="row">
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white"><i class="fas fa-chart-bar"></i> Daily Sales – Last 7 Days</div>
                <div class="card-body"><canvas id="dailyChart" height="130"></canvas></div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white"><i class="fas fa-chart-pie"></i> Top 5 Products This Month</div>
                <div class="card-body"><canvas id="topProductsPie" height="220"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white"><i class="fas fa-chart-line"></i> Monthly Sales Trend</div>
                <div class="card-body"><canvas id="salesLineChart" height="160"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white"><i class="fas fa-chart-line"></i> Monthly Profit Trend</div>
                <div class="card-body"><canvas id="profitLineChart" height="160"></canvas></div>
            </div>
        </div>
    </div>
</div>

<?php
// ==================== CHART DATA (unchanged) ====================
$dailyData = $pdo->query("SELECT DATE(sale_date) as sale_date, SUM(total) as total 
    FROM sales WHERE sale_date >= CURDATE() - INTERVAL 7 DAY 
    GROUP BY DATE(sale_date) ORDER BY sale_date")->fetchAll();

$dates = []; $totals = [];
foreach($dailyData as $d) {
    $dates[] = date('M d', strtotime($d['sale_date']));
    $totals[] = round($d['total'], 2);
}

$topProducts = $pdo->query("
    SELECT p.name, SUM(si.quantity * si.price) as revenue
    FROM sale_items si JOIN products p ON si.product_id = p.id 
    JOIN sales s ON si.sale_id = s.id
    WHERE MONTH(s.sale_date) = MONTH(CURDATE())
    GROUP BY p.id ORDER BY revenue DESC LIMIT 5
")->fetchAll();

$pieLabels = []; $pieData = []; $pieColors = ['#0d6efd','#198754','#fd7e14','#dc3545','#6f42c1'];
foreach($topProducts as $p) {
    $pieLabels[] = $p['name'];
    $pieData[] = round($p['revenue'], 2);
}

$monthlyData = $pdo->query("
    SELECT DATE_FORMAT(sale_date, '%b %Y') as month_label,
           SUM(total) as sales, SUM(profit) as profit
    FROM sales 
    GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
    ORDER BY DATE_FORMAT(sale_date, '%Y-%m') DESC LIMIT 12
")->fetchAll();

$monthlyData = array_reverse($monthlyData);
$monthLabels = []; $salesData = []; $profitData = [];
foreach($monthlyData as $m) {
    $monthLabels[] = $m['month_label'];
    $salesData[] = round($m['sales'], 2);
    $profitData[] = round($m['profit'], 2);
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// Daily Sales Bar Chart
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: { labels: <?= json_encode($dates) ?>, datasets: [{ label: 'Daily Sales (₱)', data: <?= json_encode($totals) ?>, backgroundColor: '#0d6efd' }] }
});

// Top 5 Products Pie
new Chart(document.getElementById('topProductsPie'), {
    type: 'pie',
    data: { labels: <?= json_encode($pieLabels) ?>, datasets: [{ data: <?= json_encode($pieData) ?>, backgroundColor: <?= json_encode($pieColors) ?> }] }
});

// Monthly Sales Line
new Chart(document.getElementById('salesLineChart'), {
    type: 'line',
    data: { labels: <?= json_encode($monthLabels) ?>, datasets: [{ label: 'Sales (₱)', data: <?= json_encode($salesData) ?>, borderColor: '#0d6efd', borderWidth: 4 }] }
});

// Monthly Profit Line
new Chart(document.getElementById('profitLineChart'), {
    type: 'line',
    data: { labels: <?= json_encode($monthLabels) ?>, datasets: [{ label: 'Profit (₱)', data: <?= json_encode($profitData) ?>, borderColor: '#198754', borderWidth: 4 }] }
});


</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- PDF Export Libraries -->
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
// ==================== ALL YOUR EXISTING CHARTS (keep them) ====================
// Daily Sales, Top Products Pie, Monthly Sales Line, Monthly Profit Line
// ... (your existing chart code remains here) ...

// ==================== EXPORT MONTHLY REPORT TO PDF ====================
function exportMonthlyPDF() {
    const element = document.querySelector('.card.mb-5'); // Targets the "This Month Summary" card

    html2canvas(element, { scale: 2 }).then(canvas => {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        
        const imgData = canvas.toDataURL('image/png');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

        pdf.setFontSize(18);
        pdf.text("MONTHLY REPORT - Redyant Convenience Store", pdfWidth/2, 20, { align: "center" });
        pdf.setFontSize(11);
        pdf.text("Generated on: <?= date('Y-m-d H:i:s') ?>", pdfWidth/2, 28, { align: "center" });

        pdf.addImage(imgData, 'PNG', 10, 40, pdfWidth - 20, pdfHeight);

        pdf.save("Monthly_Report_<?= date('Y-m-d') ?>.pdf");
    });
}

</script>
<script>
function showLowStockModal() {
    new bootstrap.Modal(document.getElementById('lowStockModal')).show();
}
</script>
<?php include 'footer.php'; ?>