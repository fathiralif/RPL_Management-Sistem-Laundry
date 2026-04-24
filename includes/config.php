<?php
// WashWell - Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'washwell_db');
define('SITE_NAME', 'WashWell');
define('SITE_URL', 'http://localhost/LAUNDRY 2');
define('CURRENCY', 'Rp');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('<div style="font-family:sans-serif;padding:40px;text-align:center;background:#fff0f0;color:#c00;">
                <h2>⚠️ Koneksi Database Gagal</h2>
                <p>Pastikan MySQL aktif dan database <strong>washwell_db</strong> sudah dibuat.</p>
                <p>Import file <code>database.sql</code> ke phpMyAdmin terlebih dahulu.</p>
                <small>Error: ' . $conn->connect_error . '</small>
            </div>');
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function generateOrderCode() {
    return 'ORD-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

function generateTrxCode() {
    return 'TRX-' . date('YmdHis') . rand(10,99);
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff/60) . ' menit lalu';
    if ($diff < 86400) return floor($diff/3600) . ' jam lalu';
    return floor($diff/86400) . ' hari lalu';
}
?>