<?php
// ==================== SESSION & HANDLERS ====================
if (session_status() === PHP_SESSION_NONE) session_start();
include 'header.php';
include 'db_connect.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// ==================== ADD TO CART WITH STOCK VALIDATION ====================
if (isset($_POST['add'])) {
    $id = (int)$_POST['id'];
    $qty = (int)($_POST['qty'] ?? 1);

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();

    if ($p) {
        if ($qty > $p['stock']) {
            echo "<script>alert('⚠️ Not enough stock!\\nOnly " . $p['stock'] . " units available for " . $p['name'] . "');</script>";
        } else {
            $_SESSION['cart'][$id] = [
                'name'  => $p['name'],
                'price' => $p['price'],
                'cost'  => $p['cost'],
                'qty'   => $qty
            ];
        }
    }
}

// ==================== UPDATE QUANTITY ====================
if (isset($_GET['update'])) {
    $id = $_GET['update'];
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty'] = max(1, (int)$_GET['qty']);
    }
}

// Remove item
if (isset($_GET['remove'])) unset($_SESSION['cart'][$_GET['remove']]);

// ==================== BARCODE SCANNING ====================
if (isset($_GET['barcode'])) {
    $code = trim($_GET['barcode']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE barcode = ? LIMIT 1");
    $stmt->execute([$code]);
    $p = $stmt->fetch();

    if ($p) {
        $id = $p['id'];
        $qty = isset($_SESSION['cart'][$id]) ? $_SESSION['cart'][$id]['qty'] + 1 : 1;
        if ($qty <= $p['stock']) {
            $_SESSION['cart'][$id] = [
                'name' => $p['name'],
                'price' => $p['price'],
                'cost' => $p['cost'],
                'qty' => $qty
            ];
            $_SESSION['scan_msg'] = "✅ {$p['name']} added!";
        } else {
            $_SESSION['scan_msg'] = "⚠️ Not enough stock for {$p['name']}!";
        }
    } else {
        $_SESSION['scan_msg'] = "❌ Barcode not found!";
    }
    header("Location: pos.php");
    exit;
}
?>

<div class="container">

    <!-- BARCODE SCANNER -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="input-group input-group-lg shadow">
                <span class="input-group-text bg-primary text-white"><i class="fas fa-barcode"></i></span>
                <input type="text" id="barcode-input" class="form-control" placeholder="SCAN BARCODE HERE..." autofocus>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['scan_msg'])): ?>
        <div class="alert alert-info"><?= $_SESSION['scan_msg'] ?></div>
        <?php unset($_SESSION['scan_msg']); ?>
    <?php endif; ?>

    <div class="row">

        <div class="row">
        <!-- LEFT: PRODUCTS WITH PHOTOS -->
        <div class="col-md-7">

            <!-- Dynamic Category Tabs -->
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#all">All Products</a></li>
                <?php
                $catStmt = $pdo->query("SELECT name FROM categories ORDER BY name");
                while ($c = $catStmt->fetch()) {
                    $clean = str_replace(' ', '_', $c['name']);
                    echo "<li class='nav-item'><a class='nav-link' data-bs-toggle='tab' href='#$clean'>{$c['name']}</a></li>";
                }
                ?>
            </ul>

            <div class="tab-content">
                <!-- All Products -->
                <div class="tab-pane fade show active" id="all">
                    <div class="row">
                        <?php
                        $stmt = $pdo->query("SELECT * FROM products ORDER BY name");
                        while ($p = $stmt->fetch()) {
                            $photo = $p['photo'] ? "uploads/products/{$p['photo']}" : "https://via.placeholder.com/120x120?text=No+Image";
                            echo "
                            <div class='col-md-4 mb-3'>
                                <div class='card product-card'>
                                    <div class='card-body text-center'>
                                        <img src='{$photo}' class='img-fluid rounded mb-2' style='height: 100px; object-fit: cover;'>
                                        <h6>{$p['name']}</h6>
                                        <p class='text-success fw-bold'>₱".number_format($p['price'],2)."</p>
                                        <small>Stock: {$p['stock']}</small><br>
                                        <form method='post'>
                                            <input type='hidden' name='id' value='{$p['id']}'>
                                            <input type='number' name='qty' value='1' class='form-control d-inline w-50' min='1' max='{$p['stock']}'>
                                            <button type='submit' name='add' class='btn btn-primary btn-sm'>Add</button>
                                        </form>
                                    </div>
                                </div>
                            </div>";
                        }
                        ?>
                    </div>
                </div>

                <!-- Category Tabs -->
                <?php
                $catStmt = $pdo->query("SELECT name FROM categories ORDER BY name");
                while ($c = $catStmt->fetch()) {
                    $catName = $c['name'];
                    $cleanId = str_replace(' ', '_', $catName);
                    echo "<div class='tab-pane fade' id='$cleanId'><div class='row'>";
                    
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ?");
                    $stmt->execute([$catName]);
                    while ($p = $stmt->fetch()) {
                        $photo = $p['photo'] ? "uploads/products/{$p['photo']}" : "https://via.placeholder.com/120x120?text=No+Image";
                        echo "
                        <div class='col-md-4 mb-3'>
                            <div class='card product-card'>
                                <div class='card-body text-center'>
                                    <img src='{$photo}' class='img-fluid rounded mb-2' style='height: 100px; object-fit: cover;'>
                                    <h6>{$p['name']}</h6>
                                    <p class='text-success fw-bold'>₱".number_format($p['price'],2)."</p>
                                    <small>Stock: {$p['stock']}</small><br>
                                    <form method='post'>
                                        <input type='hidden' name='id' value='{$p['id']}'>
                                        <input type='number' name='qty' value='1' class='form-control d-inline w-50' min='1' max='{$p['stock']}'>
                                        <button type='submit' name='add' class='btn btn-primary btn-sm'>Add</button>
                                    </form>
                                </div>
                            </div>
                        </div>";
                    }
                    echo "</div></div>";
                }
                ?>
            </div>
        </div>
        <!-- ==================== RIGHT SIDE: CART ==================== -->
        <div class="col-md-5">
            <div class="sticky-top" style="top: 20px;">
                <h4><i class="fas fa-shopping-cart"></i> Cart 
                    <span class="badge bg-danger"><?= count($_SESSION['cart']) ?></span>
                </h4>

                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php
                    $subtotal = 0;
                    foreach ($_SESSION['cart'] as $id => $item) {
                        $itemtotal = $item['qty'] * $item['price'];
                        $subtotal += $itemtotal;
                        echo "<tr>
                            <td>{$item['name']}</td>
                            <td><input type='number' value='{$item['qty']}' class='form-control form-control-sm' style='width:70px' onchange='updateQty($id, this.value)'></td>
                            <td>₱".number_format($item['price'],2)."</td>
                            <td>₱".number_format($itemtotal,2)."</td>
                            <td><button class='btn btn-danger btn-sm' onclick='removeItem($id)'>×</button></td>
                        </tr>";
                    }
                    $tax = $subtotal * 0.12;
                    $total = $subtotal + $tax;
                    ?>
                    </tbody>
                </table>

                <div class="bg-white p-3 rounded border shadow-sm">
                    <div class="d-flex justify-content-between"><strong>Subtotal</strong><span>₱<?= number_format($subtotal,2) ?></span></div>
                    <div class="d-flex justify-content-between"><strong>12% VAT</strong><span>₱<?= number_format($tax,2) ?></span></div>
                    <hr>
                    <div class="d-flex justify-content-between fs-4 text-danger">
                        <strong>GRAND TOTAL</strong>
                        <span>₱<?= number_format($total,2) ?></span>
                    </div>
                </div>

                <form method="post" action="checkout.php" id="checkoutForm">
                    <div class="input-group mb-2">
                        <span class="input-group-text">Cash Tendered ₱</span>
                        <input type="number" name="cash" id="cashInput" step="0.01" class="form-control" placeholder="0.00" required>
                    </div>
    
                    <div id="cashWarning" class="text-danger small mb-2" style="display:none;">
                      ⚠️ Cash must be greater than or equal to Grand Total
                    </div>

                    <button type="submit" id="completeBtn" class="btn btn-success w-100 btn-lg" disabled>
                    <i class="fas fa-check"></i> COMPLETE SALE
                </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
// Real-time Cash Validation + Button Control
const cashInput = document.getElementById('cashInput');
const completeBtn = document.getElementById('completeBtn');
const cashWarning = document.getElementById('cashWarning');

const grandTotal = <?= $total ?? 0 ?>;   // PHP value

function checkCash() {
    const cash = parseFloat(cashInput.value) || 0;

    if (cash >= grandTotal) {
        completeBtn.disabled = false;
        cashWarning.style.display = 'none';
    } else {
        completeBtn.disabled = true;
        cashWarning.style.display = 'block';
    }
}

// Listen to cash input changes
cashInput.addEventListener('input', checkCash);
cashInput.addEventListener('keyup', checkCash);

// Run once on load
window.onload = function() {
    document.getElementById('barcode-input').focus();
    checkCash();   // Initial check
};

// Quantity validation when adding product
document.querySelectorAll('form[method="post"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        const addBtn = this.querySelector('button[name="add"]');
        if (addBtn) {
            const qtyInput = this.querySelector('input[name="qty"]');
            const qty = parseInt(qtyInput.value);
            const maxStock = parseInt(qtyInput.getAttribute('max') || 9999);

            if (qty > maxStock) {
                e.preventDefault();
                alert(`⚠️ Not enough stock!\nOnly ${maxStock} units available.`);
            }
        }
    });
});

// Barcode scanner
document.getElementById('barcode-input').addEventListener('keypress', function(e) {
    if (e.key === "Enter") {
        window.location = `pos.php?barcode=${encodeURIComponent(this.value.trim())}`;
        this.value = '';
    }
});

function updateQty(id, qty) {
    window.location = `pos.php?update=${id}&qty=${qty}`;
}

function removeItem(id) {
    if(confirm('Remove this item?')) window.location = `pos.php?remove=${id}`;
}
</script>
<?php include 'footer.php'; ?>