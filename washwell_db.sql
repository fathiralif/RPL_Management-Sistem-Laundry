-- WashWell Laundry Management System Database
CREATE DATABASE IF NOT EXISTS washwell_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE washwell_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer','admin','staff') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price_per_kg DECIMAL(10,2) NOT NULL,
    duration_hours INT DEFAULT 24,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    staff_id INT DEFAULT NULL,
    weight DECIMAL(5,2) DEFAULT 1.00,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','in_progress','ready','delivered','cancelled') DEFAULT 'pending',
    pickup_date DATE,
    delivery_date DATE,
    notes TEXT,
    payment_status ENUM('unpaid','paid') DEFAULT 'unpaid',
    payment_method ENUM('cash','transfer','e-wallet') DEFAULT 'cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    transaction_code VARCHAR(30) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','transfer','e-wallet') DEFAULT 'cash',
    status ENUM('pending','success','failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (name, email, password, phone, role) VALUES
('Admin WashWell', 'admin@washwell.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', 'admin'),
('Budi Santoso', 'staff@washwell.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567891', 'staff'),
('John Mith', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567892', 'customer'),
('Customer Demo', 'customer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567893', 'customer');

INSERT INTO services (name, description, price_per_kg, duration_hours) VALUES
('Cuci Reguler', 'Cuci biasa dengan deterjen standar berkualitas', 7000, 24),
('Cuci Express', 'Cuci cepat selesai dalam 6 jam', 12000, 6),
('Cuci + Setrika', 'Cuci bersih dan setrika rapi sempurna', 10000, 48),
('Dry Cleaning', 'Dry cleaning untuk pakaian khusus dan delicate', 25000, 72),
('Cuci Sepatu', 'Cuci sepatu bersih, wangi, dan seperti baru', 35000, 48),
('Cuci Karpet', 'Cuci karpet dan permadani ukuran besar', 15000, 72);

INSERT INTO orders (order_code, user_id, service_id, staff_id, weight, amount, status, pickup_date, delivery_date, payment_status, payment_method) VALUES
('ORD-02001', 3, 3, 2, 2.5, 50000, 'pending', CURDATE(), DATE_ADD(CURDATE(),INTERVAL 2 DAY), 'unpaid', 'cash'),
('ORD-02002', 4, 1, 2, 4.0, 30000, 'ready', CURDATE(), DATE_ADD(CURDATE(),INTERVAL 1 DAY), 'paid', 'transfer'),
('ORD-02003', 3, 2, NULL, 1.0, 10000, 'ready', CURDATE(), CURDATE(), 'paid', 'cash'),
('ORD-02004', 3, 3, 2, 3.0, 30000, 'pending', CURDATE(), DATE_ADD(CURDATE(),INTERVAL 2 DAY), 'unpaid', 'cash'),
('ORD-02005', 4, 1, 2, 4.0, 32000, 'ready', CURDATE(), DATE_ADD(CURDATE(),INTERVAL 1 DAY), 'paid', 'e-wallet'),
('ORD-02006', 3, 4, NULL, 2.0, 50000, 'ready', CURDATE(), DATE_ADD(CURDATE(),INTERVAL 3 DAY), 'unpaid', 'transfer'),
('ORD-02007', 3, 1, 2, 3.0, 21000, 'in_progress', DATE_SUB(CURDATE(),INTERVAL 1 DAY), CURDATE(), 'paid', 'cash'),
('ORD-02008', 4, 5, 2, 1.0, 35000, 'delivered', DATE_SUB(CURDATE(),INTERVAL 2 DAY), CURDATE(), 'paid', 'transfer'),
('ORD-02009', 3, 6, NULL, 5.0, 75000, 'delivered', DATE_SUB(CURDATE(),INTERVAL 3 DAY), CURDATE(), 'paid', 'e-wallet'),
('ORD-02010', 4, 2, 2, 2.0, 24000, 'in_progress', CURDATE(), CURDATE(), 'unpaid', 'cash');

INSERT INTO transactions (order_id, transaction_code, amount, payment_method, status) VALUES
(2, 'TRX-001', 30000, 'transfer', 'success'),
(3, 'TRX-002', 10000, 'cash', 'success'),
(5, 'TRX-003', 32000, 'e-wallet', 'success'),
(7, 'TRX-004', 21000, 'cash', 'success'),
(8, 'TRX-005', 35000, 'transfer', 'success'),
(9, 'TRX-006', 75000, 'e-wallet', 'success');

INSERT INTO notifications (user_id, title, message) VALUES
(3, 'Pesanan Diterima', 'Pesanan ORD-02001 Anda telah diterima dan sedang diproses.'),
(3, 'Pesanan Siap', 'Pesanan ORD-02003 Anda sudah siap diambil!'),
(4, 'Pembayaran Berhasil', 'Pembayaran untuk ORD-02002 sebesar Rp30.000 berhasil.'),
(1, 'Pesanan Baru', 'Ada pesanan baru dari John Mith - ORD-02001'),
(2, 'Tugas Baru', 'Anda ditugaskan untuk menangani pesanan ORD-02001');
