-- ============================================================
-- PATCH: Tambah kolom unit_type ke tabel services
-- Jalankan query ini 1x di phpMyAdmin / MySQL CLI
-- ============================================================

-- Tambah kolom unit_type (skip jika sudah ada)
ALTER TABLE services
    ADD COLUMN unit_type ENUM('kg','item') NOT NULL DEFAULT 'kg'
    AFTER price_per_kg;

-- Cuci Sepatu dan Cuci Karpet jadi per pcs/satuan
UPDATE services SET unit_type = 'item'
WHERE name IN ('Cuci Sepatu', 'Cuci Karpet');

-- Verifikasi
SELECT id, name, price_per_kg, unit_type FROM services ORDER BY name;
