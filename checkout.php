<?php
// checkout.php - FULLY FIXED & UPDATED
include 'db_connect.php';
session_start();

if (empty($_SESSION['cart'])) {
    header('Location: pos.php');
    exit;
}

// ==================== CALCULATIONS ====================
$subtotal = 0;
$profit   = 0;

foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['qty'] * $item['price'];
    $profit   += $item['qty'] * ($item['price'] - $item['cost']);
}

$tax    = $subtotal * 0.12;
$total  = $subtotal + $tax;
$cash   = (float)($_POST['cash'] ?? 0);
$change = $cash - $total;

// ==================== SAVE SALE ====================
$stmt = $pdo->prepare("
    INSERT INTO sales (subtotal, tax, total, profit, cash_tendered, change_amount) 
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$subtotal, $tax, $total, $profit, $cash, $change]);
$sale_id = $pdo->lastInsertId();

// Save items + deduct stock
foreach ($_SESSION['cart'] as $id => $item) {
    $pdo->prepare("
        INSERT INTO sale_items (sale_id, product_id, quantity, price) 
        VALUES (?, ?, ?, ?)
    ")->execute([$sale_id, $id, $item['qty'], $item['price']]);

    $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
        ->execute([$item['qty'], $id]);
}

// ==================== FETCH ITEMS FOR RECEIPT ====================
$items_stmt = $pdo->prepare("
    SELECT p.name, si.quantity, si.price 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    WHERE si.sale_id = ?
");
$items_stmt->execute([$sale_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Clear cart
unset($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sale Complete - ConveniPOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .receipt-container {
            max-width: 80mm;
            margin: 30px auto;
            padding: 15px;
            background: white;
            border: 1px solid #ddd;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .receipt { font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.4; }
        .receipt h5 { text-align: center; }
        .receipt hr { border-top: 1px dashed #000; margin: 8px 0; }
        @media print {
            body * { visibility: hidden; }
            .receipt-container, .receipt-container * { visibility: visible; }
            .receipt-container { position: absolute; left: 0; top: 0; width: 100%; margin: 0; box-shadow: none; }
            .no-print { display: none !important; }
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</head>
<body>

<div class="container text-center mt-5">
    <div class="card shadow p-5">
        <h1 class="text-success">✅ Sale Completed Successfully!</h1>
        <h3>Total: ₱<?= number_format($total, 2) ?></h3>
        <h4>Change: ₱<?= number_format($change, 2) ?></h4>

        <!-- Receipt Preview -->
        <div class="receipt-container">
            <div class="receipt">
                <h5>REDYANT CONVENIENCE STORE</h5>
                <p class="text-center">Balulang, Cagayan de Oro City<br>
                <?= date('Y-m-d H:i:s') ?><br>
                Invoice #<?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?></p>
                <hr>

                <?php foreach ($items as $item): ?>
                    <div class="d-flex justify-content-between">
                        <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                        <span>₱<?= number_format($item['quantity'] * $item['price'], 2) ?></span>
                    </div>
                <?php endforeach; ?>

                <hr>
                <div class="d-flex justify-content-between"><strong>Subtotal:</strong><span>₱<?= number_format($subtotal, 2) ?></span></div>
                <div class="d-flex justify-content-between"><strong>VAT (12%):</strong><span>₱<?= number_format($tax, 2) ?></span></div>
                <div class="d-flex justify-content-between fs-5"><strong>GRAND TOTAL:</strong><span>₱<?= number_format($total, 2) ?></span></div>
                <hr>
                <div class="d-flex justify-content-between"><strong>Cash:</strong><span>₱<?= number_format($cash, 2) ?></span></div>
                <div class="d-flex justify-content-between"><strong>Change:</strong><span>₱<?= number_format($change, 2) ?></span></div>
                <p class="text-center mt-3">Thank you! Come again!</p>
            </div>
        </div>

        <div class="mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary btn-lg">🖨️ Print Receipt</button>
            <a href="pos.php" class="btn btn-success btn-lg ms-3">New Sale</a>
            <a href="sales.php" class="btn btn-secondary btn-lg ms-3">View Sales</a>
        </div>
    </div>
</div>

</body>
</html>