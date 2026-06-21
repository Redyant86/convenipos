<?php
include 'db_connect.php';
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    echo "Invalid Invoice";
    exit;
}

// Get sale info
$sale = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
$sale->execute([$id]);
$s = $sale->fetch();

if (!$s) {
    echo "Receipt not found";
    exit;
}

// Get items
$items = $pdo->prepare("
    SELECT p.name, si.quantity, si.price 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    WHERE si.sale_id = ?
");
$items->execute([$id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= str_pad($id,6,'0',STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Courier New', monospace; font-size: 13px; }
        .receipt { max-width: 80mm; margin: 20px auto; padding: 15px; border: 1px solid #000; }
        @media print { body * { visibility: hidden; } .receipt, .receipt * { visibility: visible; } }
    </style>
</head>
<body onload="window.print()">

<div class="receipt">
    <h5 class="text-center">REDYANT CONVENIENCE STORE</h5>
    <p class="text-center">Balulang, Cagayan de Oro City<br>
    <?= date('Y-m-d H:i:s', strtotime($s['sale_date'])) ?><br>
    Invoice #<?= str_pad($id,6,'0',STR_PAD_LEFT) ?></p>
    <hr>

    <?php while($item = $items->fetch()): ?>
        <div class="d-flex justify-content-between">
            <span><?= $item['name'] ?> × <?= $item['quantity'] ?></span>
            <span>₱<?= number_format($item['quantity'] * $item['price'], 2) ?></span>
        </div>
    <?php endwhile; ?>

    <hr>
    <div class="d-flex justify-content-between"><strong>Subtotal</strong><span>₱<?= number_format($s['subtotal'],2) ?></span></div>
    <div class="d-flex justify-content-between"><strong>VAT 12%</strong><span>₱<?= number_format($s['tax'],2) ?></span></div>
    <div class="d-flex justify-content-between fs-5"><strong>TOTAL</strong><span>₱<?= number_format($s['total'],2) ?></span></div>
    <div class="d-flex justify-content-between"><strong>Cash</strong><span>₱<?= number_format($s['cash_tendered'],2) ?></span></div>
    <div class="d-flex justify-content-between"><strong>Change</strong><span>₱<?= number_format($s['change_amount'],2) ?></span></div>
    <p class="text-center mt-4">Thank you! Come again!</p>
</div>

</body>
</html>