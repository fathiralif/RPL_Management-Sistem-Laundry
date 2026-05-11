<?php
require_once '../includes/auth.php';
requireAdmin('../pages/login.php');
$db = getDB();
$user = getCurrentUser();

// Stats
$totalOrders   = $db->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$pendingOrders = $db->query("SELECT COUNT(*) as c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$inProgress    = $db->query("SELECT COUNT(*) as c FROM orders WHERE status IN ('washing','drying','ironing')")->fetch_assoc()['c'];
$readyOrders   = $db->query("SELECT COUNT(*) as c FROM orders WHERE status IN ('ready_pickup','ready_deliver')")->fetch_assoc()['c'];
$delivered     = $db->query("SELECT COUNT(*) as c FROM orders WHERE status='done'")->fetch_assoc()['c'];
$totalRevenue  = $db->query("SELECT COALESCE(SUM(amount),0) as r FROM transactions WHERE status='success'")->fetch_assoc()['r'];
$todayRevenue  = $db->query("SELECT COALESCE(SUM(t.amount),0) as r FROM transactions t WHERE t.status='success' AND DATE(t.created_at)=CURDATE()")->fetch_assoc()['r'];

// Recent orders
$recentOrders = $db->query("SELECT o.*, u.name as customer_name, s.name as service_name FROM orders o JOIN users u ON o.user_id=u.id JOIN services s ON o.service_id=s.id ORDER BY o.created_at DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - WashWell Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php require_once 'partials/sidebar.php'; ?>
    <div class="main-content">
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="hamburger" onclick="openSidebar()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div>
                    <div class="topbar-title">Dashboard</div>
                </div>
            </div>
            <div class="topbar-right">
                <button class="notif-btn" onclick="window.location='notifications.php'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if (getUnreadNotifs($user['id']) > 0): ?>
                    <span class="notif-dot"></span>
                    <?php endif; ?>
                </button>
                <div style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:6px 12px;border-radius:10px;background:var(--gray-100)">
                    <div class="avatar avatar-sm"><?= strtoupper(substr($user['name'],0,1)) ?></div>
                    <div>
                        <div style="font-size:13px;font-weight:700;color:var(--gray-800);line-height:1.2"><?= htmlspecialchars($user['name']) ?></div>
                        <div style="font-size:11px;color:var(--gray-500);text-transform:capitalize"><?= $user['role'] ?></div>
                    </div>
                </div>
            </div>
        </header>
        <!-- PAGE CONTENT -->
        <main class="page-content">
            <div class="page-header">
                <div>
                    <h1>Selamat Datang, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋</h1>
                    <p>Berikut ringkasan operasional laundry hari ini, <?= date('l, d F Y') ?></p>
                </div>
                <a href="orders.php?action=add" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Pesanan Baru
                </a>
            </div>

            <!-- ORDER STATUS STATS -->
            <div style="margin-bottom:8px;font-size:12.5px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:0.5px;">Status Pesanan</div>
            <div class="stats-grid" style="grid-template-columns:repeat(5,1fr);">
                <div class="stat-card stat-orange">
                    <div class="stat-card-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                    </div>
                    <div class="stat-count"><?= $totalOrders ?></div>
                    <div class="stat-label">Total Pesanan</div>
                    <div class="stat-action"><a href="orders.php" class="btn btn-orange btn-sm">Lihat Semua</a></div>
                </div>
                <div class="stat-card stat-blue">
                    <div class="stat-card-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="stat-count"><?= $pendingOrders ?></div>
                    <div class="stat-label">Menunggu</div>
                    <div class="stat-action"><a href="orders.php?status=pending" class="btn btn-primary btn-sm">Kelola</a></div>
                </div>
                <div class="stat-card stat-teal">
                    <div class="stat-card-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    </div>
                    <div class="stat-count"><?= $inProgress ?></div>
                    <div class="stat-label">Diproses</div>
                    <div class="stat-action"><a href="orders.php?status=washing" class="btn btn-sm" style="background:var(--teal);color:white">Kelola</a></div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-card-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="stat-count"><?= $readyOrders ?></div>
                    <div class="stat-label">Siap Diambil</div>
                    <div class="stat-action"><a href="orders.php?status=ready_pickup" class="btn btn-green btn-sm">Kelola</a></div>
                </div>
                <div class="stat-card stat-gray">
                    <div class="stat-card-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>
                    <div class="stat-count"><?= $delivered ?></div>
                    <div class="stat-label">Selesai</div>
                    <div class="stat-action"><a href="orders.php?status=done" class="btn btn-ghost btn-sm">Lihat</a></div>
                </div>
            </div>

            <!-- REVENUE STATS -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
                <div class="card">
                    <div class="card-body" style="display:flex;align-items:center;gap:16px;">
                        <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#22C55E,#16A34A);display:flex;align-items:center;justify-content:center;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <div>
                            <div style="font-size:12px;color:var(--gray-500);font-weight:600;margin-bottom:4px;">Total Pendapatan</div>
                            <div style="font-family:var(--font-display);font-size:22px;font-weight:800;color:var(--green)"><?= formatRupiah($totalRevenue) ?></div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body" style="display:flex;align-items:center;gap:16px;">
                        <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#F97316,#EA580C);display:flex;align-items:center;justify-content:center;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                        <div>
                            <div style="font-size:12px;color:var(--gray-500);font-weight:600;margin-bottom:4px;">Pendapatan Hari Ini</div>
                            <div style="font-family:var(--font-display);font-size:22px;font-weight:800;color:var(--orange)"><?= formatRupiah($todayRevenue) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECENT ORDERS TABLE -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">📋 Transaksi Data Terbaru</span>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <div class="search-input" style="position:relative">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--gray-400)"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" placeholder="Filter..." id="searchInput" oninput="filterTable(this.value)" style="padding:8px 14px 8px 34px;border:1.5px solid var(--gray-200);border-radius:8px;font-size:13px;font-family:var(--font);outline:none;width:180px;">
                        </div>
                        <a href="orders.php" class="btn btn-ghost btn-sm">Lihat Semua</a>
                    </div>
                </div>
                <div class="card-body" style="padding-top:16px;">
                    <div class="table-wrapper">
                        <table id="ordersTable">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" style="cursor:pointer;"></th>
                                    <th>Order ID</th>
                                    <th>Pelanggan</th>
                                    <th>Layanan</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($o = $recentOrders->fetch_assoc()): ?>
                                <tr>
                                    <td><input type="checkbox" style="cursor:pointer;"></td>
                                    <td><span style="font-weight:700;color:var(--gray-900);font-family:monospace"><?= $o['order_code'] ?></span></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <div class="avatar avatar-sm"><?= strtoupper(substr($o['customer_name'],0,1)) ?></div>
                                            <span><?= htmlspecialchars($o['customer_name']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($o['service_name']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $o['status'] ?>">
                                            <?php
                                            $labels = [
                                                'pending'       => 'Menunggu',
                                                'washing'       => 'Sedang Dicuci',
                                                'drying'        => 'Pengeringan',
                                                'ironing'       => 'Setrika',
                                                'ready_pickup'  => 'Siap Diambil',
                                                'ready_deliver' => 'Siap Diantar',
                                                'done'          => 'Selesai',
                                                'cancelled'     => 'Dibatalkan',
                                            ];
                                            echo $labels[$o['status']] ?? $o['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td style="color:var(--gray-500)"><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                                    <td><strong><?= formatRupiah($o['amount']) ?></strong></td>
                                    <td>
                                        <div style="display:flex;gap:6px;">
                                            <a href="order-detail.php?id=<?= $o['id'] ?>" class="btn btn-primary btn-sm">Detail</a>
                                            <a href="orders.php" class="btn btn-ghost btn-sm">Update</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('open');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
}
function filterTable(q) {
    const rows = document.querySelectorAll('#ordersTable tbody tr');
    q = q.toLowerCase();
    rows.forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
// Select all checkbox
document.querySelector('thead input[type=checkbox]').addEventListener('change', function() {
    document.querySelectorAll('tbody input[type=checkbox]').forEach(cb => cb.checked = this.checked);
});
</script>
</body>
</html>