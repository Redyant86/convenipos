<?php
// db_connect.php - FULLY FIXED & CLEAN VERSION
$host = 'localhost';
$dbname = 'convenipos';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Database doesn't exist → create it
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4");
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
}

// ==================== CREATE ALL TABLES ====================
$pdo->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) UNIQUE NOT NULL
    );

    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        barcode VARCHAR(50) UNIQUE,
        price DECIMAL(10,2) NOT NULL,
        cost DECIMAL(10,2) NOT NULL,
        stock INT DEFAULT 100,
        category VARCHAR(50) DEFAULT 'Others'
    );

    CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        subtotal DECIMAL(10,2) NOT NULL,
        tax DECIMAL(10,2) NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        profit DECIMAL(10,2) NOT NULL,
        cash_tendered DECIMAL(10,2) NOT NULL,
        change_amount DECIMAL(10,2) NOT NULL
    );

    CREATE TABLE IF NOT EXISTS sale_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT,
        product_id INT,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL
    );
");

// ==================== DEFAULT DATA ====================
$pdo->exec("
    INSERT IGNORE INTO categories (name) VALUES 
    ('Beverages'), ('Snacks'), ('Bakery'), ('Dairy'), ('Others');
");

$pdo->exec("
    INSERT IGNORE INTO products (name, barcode, price, cost, stock, category) VALUES 
    ('Coca-Cola 1L', '4801234567890', 55.00, 38.00, 80, 'Beverages'),
    ('Jack & Jill Chips', '8991234567890', 28.00, 18.00, 150, 'Snacks'),
    ('Gardenia Bread', '4800552000123', 18.00, 12.00, 60, 'Bakery'),
    ('Magnolia Fresh Milk', '4800123456789', 65.00, 45.00, 40, 'Dairy'),
    ('Nescafe Coffee 3in1', '4800361234567', 12.00, 8.00, 200, 'Beverages');
");
// ==================== DELIVERIES TABLE FOR RESTOCKING ====================
$pdo->exec("
    CREATE TABLE IF NOT EXISTS deliveries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        delivery_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        supplier_name VARCHAR(100) NOT NULL,
        invoice_number VARCHAR(50),
        dr_number VARCHAR(50),
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        cost_per_unit DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (product_id) REFERENCES products(id)
    );
");
// Fix any old products that don't have category
$pdo->exec("UPDATE products SET category = 'Others' WHERE category IS NULL OR category = ''");

// Add Minimum Stock Level
$pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS min_stock INT DEFAULT 10");

// Add photo column for products
$pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS photo VARCHAR(255) DEFAULT NULL");
// Add status for refund feature
$pdo->exec("ALTER TABLE sales ADD COLUMN IF NOT EXISTS status ENUM('completed', 'refunded') DEFAULT 'completed'");

// ==================== USER MANAGEMENT TABLE ====================
$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'cashier') NOT NULL DEFAULT 'cashier',
        full_name VARCHAR(100)
    );
");

// Create default Admin user (username: admin, password: admin123)
$adminPass = password_hash('admin123', PASSWORD_DEFAULT);
$pdo->prepare("INSERT IGNORE INTO users (username, password, role, full_name) VALUES (?, ?, 'admin', 'System Administrator')")
    ->execute(['admin', $adminPass]);
// ==================== ADD USER_ID TO SALES TABLE ====================
$pdo->exec("ALTER TABLE sales ADD COLUMN IF NOT EXISTS user_id INT NULL");
$pdo->exec("ALTER TABLE sales ADD FOREIGN KEY (user_id) REFERENCES users(id)");

// ==================== PROFILE PHOTO & ACTIVITY LOG ====================
$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT NULL");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
");
?>