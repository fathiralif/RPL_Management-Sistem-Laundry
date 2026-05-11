-- =========================================================
-- PATCH: Fix Status Lama & Duplikat Transaksi
-- WashWell - Jalankan di phpMyAdmin
-- =========================================================

-- 1. KONVERSI STATUS LAMA KE STATUS BARU
-- (Fix data yang masih pakai status delivered/ready/in_progress)
UPDATE orders SET status = 'pending'       WHERE status = 'waiting';
UPDATE orders SET status = 'washing'       WHERE status = 'in_progress';
UPDATE orders SET status = 'ready_pickup'  WHERE status = 'ready';
UPDATE orders SET status = 'done'          WHERE status = 'delivered';

-- 2. HAPUS DUPLIKAT TRANSAKSI
-- Pertahankan hanya 1 transaksi per order (yang terbaru/terbesar ID-nya)
DELETE t1 FROM transactions t1
INNER JOIN transactions t2
WHERE t1.order_id = t2.order_id
  AND t1.id < t2.id;

-- 3. PASTIKAN TRANSAKSI YANG ADA PUNYA KODE UNIK
-- Update kode yang kosong atau duplikat
UPDATE transactions
SET transaction_code = CONCAT('TRX-FIX-', id, '-', DATE_FORMAT(created_at, '%Y%m%d%H%i%s'))
WHERE transaction_code IS NULL
   OR transaction_code = ''
   OR transaction_code IN (
       SELECT tc FROM (
           SELECT transaction_code as tc FROM transactions GROUP BY transaction_code HAVING COUNT(*) > 1
       ) dup
   );

-- 4. SYNC payment_status ORDERS berdasarkan transaksi sukses
-- Tandai order sebagai paid jika ada transaksi success
UPDATE orders o
INNER JOIN transactions t ON t.order_id = o.id AND t.status = 'success'
SET o.payment_status = 'paid'
WHERE o.payment_status = 'unpaid';

-- 5. TAMBAH NOTIFIKASI ADMIN yang lebih bervariasi (opsional - hapus dulu yang lama)
-- DELETE FROM notifications WHERE user_id = 1; -- Uncomment jika ingin reset

-- Selesai. Cek hasilnya:
SELECT status, COUNT(*) as total FROM orders GROUP BY status;
SELECT transaction_code, COUNT(*) as dup FROM transactions GROUP BY transaction_code HAVING dup > 1;
