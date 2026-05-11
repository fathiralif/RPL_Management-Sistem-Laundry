-- =========================================================
-- PATCH: Buat tabel order_items (jika belum ada)
-- Jalankan di phpMyAdmin -> washwell_db -> Import
-- =========================================================

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    cloth_type ENUM('reguler','katun','jas','sutra','jeans','batik','wool','lainnya') NOT NULL DEFAULT 'reguler',
    weight DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
