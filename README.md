# WashWell - Sistem Manajemen Laundry

## 🗄️ Setup Database

**Hanya ada SATU file database: `washwell_db.sql`**

1. Buka phpMyAdmin
2. Import file `washwell_db.sql`
3. Database `washwell_db` akan otomatis terbuat

> ⚠️ Jika sudah punya database lama, file ini sudah menyertakan perintah ALTER TABLE di bagian bawah untuk memperbaiki ENUM status secara otomatis.

---

## 🔑 Login Default

| Role  | Email                | Password |
|-------|----------------------|----------|
| Admin | admin@washwell.com   | password |
| User  | john@example.com     | password |

---

## 📦 Status Pesanan

| Status          | Label             | Keterangan                    |
|-----------------|-------------------|-------------------------------|
| pending         | Menunggu          | Baru masuk, belum diproses    |
| washing         | Sedang Dicuci     | Dalam proses pencucian        |
| drying          | Pengeringan       | Sedang dikeringkan            |
| ironing         | Setrika           | Sedang disetrika              |
| ready_pickup    | Siap Diambil      | Bisa diambil di tempat        |
| ready_deliver   | Siap Diantar      | Dalam perjalanan ke customer  |
| done            | Selesai           | Pesanan selesai (final)       |
| cancelled       | Dibatalkan        | Pesanan dibatalkan (final)    |

---

## 🐛 Bug yang Diperbaiki (versi ini)

1. Error line 57 orders.php - Validasi status sebelum UPDATE ke DB
2. Not Found order-detail.php - File dibuat ulang dengan progress tracker
3. Status tidak konsisten - Semua halaman pakai label Bahasa Indonesia seragam
4. Banyak file SQL membingungkan - Digabung jadi satu washwell_db.sql
