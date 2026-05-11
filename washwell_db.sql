-- ============================================================
-- WashWell Laundry Management System
-- Database: washwell_db
-- Versi gabungan dari semua file SQL (washwell_db.sql,
-- patch_unit_type.sql, patch_order_items.sql,
-- patch_fix_status_and_transactions.sql, db_tes.sql)
-- ============================================================

CREATE DATABASE IF NOT EXISTS washwell_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE washwell_db;

-- ============================================================
-- TABEL: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    phone      VARCHAR(20),
    address    TEXT,
    role       ENUM('customer','admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- TABEL: services
-- Sudah include kolom unit_type (dari patch_unit_type.sql)
-- dan requires_cloth_type
-- ============================================================
CREATE TABLE IF NOT EXISTS services (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(100) NOT NULL,
    description         TEXT,
    price_per_kg        DECIMAL(10,2) NOT NULL,
    unit_type           ENUM('kg','item') NOT NULL DEFAULT 'kg',
    requires_cloth_type TINYINT(1) DEFAULT 0,
    duration_hours      INT DEFAULT 24,
    is_active           TINYINT(1) DEFAULT 1,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABEL: orders
-- Sudah include status terbaru, delivery fields
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_code      VARCHAR(20) UNIQUE NOT NULL,
    user_id         INT NOT NULL,
    service_id      INT NOT NULL,
    weight          DECIMAL(5,2) DEFAULT 1.00,
    amount          DECIMAL(10,2) NOT NULL,
    status          ENUM('pending','washing','drying','ironing','ready_pickup','ready_deliver','done','cancelled') DEFAULT 'pending',
    pickup_date     DATE,
    delivery_date   DATE,
    notes           TEXT,
    delivery_type   ENUM('pickup','delivery') DEFAULT 'pickup',
    delivery_address TEXT,
    driver_name     VARCHAR(100),
    driver_phone    VARCHAR(20),
    delivery_status ENUM('pending','on_way','arrived') DEFAULT 'pending',
    payment_status  ENUM('unpaid','paid') DEFAULT 'unpaid',
    payment_method  ENUM('cash','transfer','e-wallet') DEFAULT 'cash',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- ============================================================
-- TABEL: order_items (dari patch_order_items.sql)
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL,
    cloth_type ENUM('reguler','katun','jas','sutra','jeans','batik','wool','lainnya') NOT NULL DEFAULT 'reguler',
    weight     DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    note       TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ============================================================
-- TABEL: transactions
-- ============================================================
CREATE TABLE IF NOT EXISTS transactions (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    order_id         INT NOT NULL,
    transaction_code VARCHAR(30) UNIQUE NOT NULL,
    amount           DECIMAL(10,2) NOT NULL,
    payment_method   ENUM('cash','transfer','e-wallet','cod') DEFAULT 'cash',
    status           ENUM('pending','success','failed') DEFAULT 'success',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- ============================================================
-- TABEL: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    title      VARCHAR(200) NOT NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- DATA: users
-- ============================================================
INSERT INTO users (name, email, password, phone, role) VALUES
('Admin WashWell',  'admin@washwell.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', 'admin'),
('John Mith',       'john@example.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567892', 'customer'),
('Customer Demo',   'customer@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567893', 'customer');

-- ============================================================
-- DATA: services
-- unit_type sudah ditentukan langsung (tidak perlu UPDATE patch)
-- ============================================================
INSERT INTO services (name, description, price_per_kg, unit_type, duration_hours) VALUES
('Cuci Reguler',  'Cuci biasa dengan deterjen standar berkualitas',       7000,  'kg',   24),
('Cuci Express',  'Cuci cepat selesai dalam 6 jam',                      12000,  'kg',    6),
('Cuci + Setrika','Cuci bersih dan setrika rapi sempurna',               10000,  'kg',   48),
('Dry Cleaning',  'Dry cleaning untuk pakaian khusus dan delicate',      25000,  'kg',   72),
('Cuci Sepatu',   'Cuci sepatu bersih, wangi, dan seperti baru',         35000,  'item', 48),
('Cuci Karpet',   'Cuci karpet dan permadani ukuran besar',              15000,  'item', 72);

-- ============================================================
-- DATA: orders
-- Status sudah pakai ENUM terbaru (washing, drying, dst.)
-- ============================================================
INSERT INTO orders (order_code, user_id, service_id, weight, amount, status, pickup_date, delivery_date, payment_status, payment_method) VALUES
('ORD-02001', 2, 3, 2.5, 50000, 'pending',       CURDATE(),                      DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'unpaid', 'cash'),
('ORD-02002', 3, 1, 4.0, 30000, 'ready_pickup',  CURDATE(),                      DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'paid',   'transfer'),
('ORD-02003', 2, 2, 1.0, 10000, 'ready_pickup',  CURDATE(),                      CURDATE(),                           'paid',   'cash'),
('ORD-02004', 2, 3, 3.0, 30000, 'pending',       CURDATE(),                      DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'unpaid', 'cash'),
('ORD-02005', 3, 1, 4.0, 32000, 'ready_deliver', CURDATE(),                      DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'paid',   'e-wallet'),
('ORD-02006', 2, 4, 2.0, 50000, 'washing',       CURDATE(),                      DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'unpaid', 'transfer'),
('ORD-02007', 2, 1, 3.0, 21000, 'ironing',       DATE_SUB(CURDATE(), INTERVAL 1 DAY), CURDATE(),                      'paid',   'cash'),
('ORD-02008', 3, 5, 1.0, 35000, 'done',          DATE_SUB(CURDATE(), INTERVAL 2 DAY), CURDATE(),                      'paid',   'transfer'),
('ORD-02009', 2, 6, 5.0, 75000, 'done',          DATE_SUB(CURDATE(), INTERVAL 3 DAY), CURDATE(),                      'paid',   'e-wallet'),
('ORD-02010', 3, 2, 2.0, 24000, 'drying',        CURDATE(),                      CURDATE(),                           'unpaid', 'cash');

-- ============================================================
-- DATA: order_items
-- ============================================================
INSERT INTO order_items (order_id, cloth_type, weight, note) VALUES
(1, 'katun',  1.5, NULL),
(1, 'jas',    1.0, 'Jangan diperas'),
(3, 'reguler',1.0, NULL),
(7, 'jeans',  2.0, NULL),
(7, 'sutra',  1.0, 'Cuci dengan lembut');

-- ============================================================
-- DATA: transactions
-- Kode unik, tidak ada duplikat
-- ============================================================
INSERT INTO transactions (order_id, transaction_code, amount, payment_method, status, created_at) VALUES
(2,  'TRX-20260501001', 30000, 'transfer', 'success', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(3,  'TRX-20260503002', 10000, 'cash',     'success', DATE_SUB(NOW(), INTERVAL 8  DAY)),
(5,  'TRX-20260504003', 32000, 'e-wallet', 'success', DATE_SUB(NOW(), INTERVAL 7  DAY)),
(7,  'TRX-20260505004', 21000, 'cash',     'success', DATE_SUB(NOW(), INTERVAL 6  DAY)),
(8,  'TRX-20260506005', 35000, 'transfer', 'success', DATE_SUB(NOW(), INTERVAL 4  DAY)),
(9,  'TRX-20260508006', 75000, 'e-wallet', 'success', DATE_SUB(NOW(), INTERVAL 2  DAY));

-- ============================================================
-- DATA: notifications
-- ============================================================
INSERT INTO notifications (user_id, title, message, created_at) VALUES
(2, 'Pesanan Diterima',      'Pesanan ORD-02001 Anda telah diterima dan sedang diproses.',                                                       NOW()),
(2, 'Pesanan Siap',          'Pesanan ORD-02003 Anda sudah siap diambil!',                                                                       NOW()),
(3, 'Pembayaran Berhasil',   'Pembayaran untuk ORD-02002 sebesar Rp30.000 berhasil.',                                                             NOW()),
(1, 'Pesanan Baru',          'Ada pesanan baru dari John Mith - ORD-02001',                                                                       NOW()),
(1, '💳 Pembayaran Diterima','Sari Dewi telah membayar via Transfer Bank untuk pesanan ORD-02002 sebesar Rp30.000. Status pesanan sekarang LUNAS.', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(1, '💳 Pembayaran Diterima','John Mith telah membayar via E-Wallet untuk pesanan ORD-02005 sebesar Rp32.000. Status pesanan sekarang LUNAS.',      DATE_SUB(NOW(), INTERVAL 7  DAY)),
(1, '📦 Pesanan Baru',       'Customer Demo membuat pesanan baru ORD-02006 - Dry Cleaning 2kg (Rp50.000)',                                         DATE_SUB(NOW(), INTERVAL 5  DAY));