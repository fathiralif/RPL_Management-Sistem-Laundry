<?php
require_once '../includes/auth.php';
requireRole('customer', '../pages/login.php');
$db = getDB();
$user = getCurrentUser();
$uid = (int)$user['id'];

// Stats
$totalOrders   = $db->query("SELECT COUNT(*) as c FROM orders WHERE user_id=$uid")->fetch_assoc()['c'];
$activeOrders  = $db->query("SELECT COUNT(*) as c FROM orders WHERE user_id=$uid AND status IN ('pending','in_progress','ready')")->fetch_assoc()['c'];
$totalSpent    = $db->query("SELECT COALESCE(SUM(t.amount),0) as s FROM transactions t JOIN orders o ON t.order_id=o.id WHERE o.user_id=$uid AND t.status='success'")->fetch_assoc()['s'];
$unpaidOrders  = $db->query("SELECT COUNT(*) as c FROM orders WHERE user_id=$uid AND payment_status='unpaid' AND status != 'cancelled'")->fetch_assoc()['c'];

// Recent orders
$recentOrders = $db->query("SELECT o.*, s.name as service_name FROM orders o JOIN services s ON o.service_id=s.id WHERE o.user_id=$uid ORDER BY o.created_at DESC LIMIT 5");

// Notifications
$notifs = $db->query("SELECT * FROM notifications WHERE user_id=$uid AND is_read=0 ORDER BY created_at DESC LIMIT 3");
$notifCount = getUnreadNotifs($uid);

$statusLabels = ['pending'=>'Menunggu','in_progress'=>'Diproses','ready'=>'Siap Diambil','delivered'=>'Terkirim','cancelled'=>'Dibatalkan'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - WashWell</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php require_once('../customer/sidebar.php'); ?>
    <div class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="hamburger" onclick="openSidebar()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="topbar-title">Dashboard</div>
            </div>
            <div class="topbar-right">
                <a href="notifications.php" class="notif-btn" style="position:relative;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if ($notifCount > 0): ?><span class="notif-dot"></span><?php endif; ?>
                </a>
                <a href="profile.php" style="display:flex;align-items:center;gap:8px;padding:6px 12px;border-radius:10px;background:var(--gray-100);text-decoration:none;">
                    <div class="avatar avatar-sm"><?= strtoupper(substr($user['name'],0,1)) ?></div>
                    <span style="font-size:13px;font-weight:600"><?= htmlspecialchars(explode(' ',$user['name'])[0]) ?></span>
                </a>
            </div>
        </header>
        <main class="page-content">
            <!-- Welcome Banner -->
            <div style="background:linear-gradient(135deg,#2563EB,#1D4ED8);border-radius:16px;padding:28px 32px;margin-bottom:24px;color:white;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
                <div>
                    <div style="font-size:13px;opacity:0.8;margin-bottom:4px;">Selamat datang kembali 👋</div>
                    <div style="font-family:'Sora',sans-serif;font-size:22px;font-weight:700;"><?= htmlspecialchars($user['name']) ?></div>
                    <div style="font-size:13px;opacity:0.75;margin-top:4px;"><?= htmlspecialchars($user['email']) ?></div>
                </div>
                <a href="new_order.php" class="btn btn-orange btn-lg">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Buat Pesanan Baru
                </a>
            </div>

            <!-- Stats -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;">
                <div class="card">
                    <div class="card-body" style="display:flex;align-items:center;gap:16px;">
                        <div style="width:48px;height:48px;border-radius:12px;background:#EFF6FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        </div>
                        <div>
                            <div style="font-size:24px;font-weight:800;font-family:'Sora',sans-serif;"><?= $totalOrders ?></div>
                            <div style="font-size:12px;color:var(--gray-500);">Total Pesanan</div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body" style="display:flex;align-items:center;gap:16px;">
                        <div style="width:48px;height:48px;border-radius:12px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div>
                            <div style="font-size:24px;font-weight:800;font-family:'Sora',sans-serif;"><?= $activeOrders ?></div>
                            <div style="font-size:12px;color:var(--gray-500);">Sedang Aktif</div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body" style="display:flex;align-items:center;gap:16px;">
                        <div style="width:48px;height:48px;border-radius:12px;background:#DCFCE7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        </div>
                        <div>
                            <div style="font-size:18px;font-weight:800;font-family:'Sora',sans-serif;"><?= formatRupiah($totalSpent) ?></div>
                            <div style="font-size:12px;color:var(--gray-500);">Total Pengeluaran</div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body" style="display:flex;align-items:center;gap:16px;">
                        <div style="width:48px;height:48px;border-radius:12px;background:#FEE2E2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        </div>
                        <div>
                            <div style="font-size:24px;font-weight:800;font-family:'Sora',sans-serif;"><?= $unpaidOrders ?></div>
                            <div style="font-size:12px;color:var(--gray-500);">Belum Dibayar</div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;flex-wrap:wrap;">
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Pesanan Terbaru</span>
                        <a href="orders.php" class="btn btn-ghost btn-sm">Lihat Semua</a>
                    </div>
                    <div class="card-body" style="padding-top:12px;">
                        <?php if ($recentOrders->num_rows === 0): ?>
                        <div style="text-align:center;padding:40px 0;color:var(--gray-400);">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:10px;opacity:0.4;"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            <p style="font-size:13px;">Belum ada pesanan</p>
                            <a href="new_order.php" class="btn btn-primary btn-sm" style="margin-top:10px;">Buat Pesanan</a>
                        </div>
                        <?php else: ?>
                        <div class="table-wrapper">
                        <table>
                            <thead><tr><th>Kode</th><th>Layanan</th><th>Status</th><th>Bayar</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php while($o = $recentOrders->fetch_assoc()): ?>
                            <tr>
                                <td><span style="font-weight:700;color:var(--primary)"><?= htmlspecialchars($o['order_code']) ?></span><br><small style="color:var(--gray-400)"><?= date('d M Y',strtotime($o['created_at'])) ?></small></td>
                                <td><?= htmlspecialchars($o['service_name']) ?><br><small style="color:var(--gray-500)"><?= $o['weight'] ?> kg</small></td>
                                <td><span class="badge badge-<?= $o['status'] ?>"><?= $statusLabels[$o['status']] ?></span></td>
                                <td><span class="badge badge-<?= $o['payment_status'] ?>"><?= $o['payment_status']==='paid'?'Lunas':'Belum' ?></span></td>
                                <td><a href="tracking.php?order=<?= $o['order_code'] ?>" class="btn btn-ghost btn-sm">Lacak</a></td>
                            </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="card" style="align-self:start;">
                    <div class="card-header">
                        <span class="card-title">Notifikasi</span>
                        <a href="notifications.php" class="btn btn-ghost btn-sm">Semua</a>
                    </div>
                    <div class="card-body" style="padding-top:12px;">
                        <?php if ($notifs->num_rows === 0): ?>
                        <div style="text-align:center;padding:24px 0;color:var(--gray-400);font-size:13px;">Tidak ada notifikasi baru</div>
                        <?php else: ?>
                        <?php while($n = $notifs->fetch_assoc()): ?>
                        <div style="padding:12px;border-radius:10px;background:var(--primary-bg);margin-bottom:8px;border-left:3px solid var(--primary);">
                            <div style="font-size:13px;font-weight:600;color:var(--gray-800);margin-bottom:3px;"><?= htmlspecialchars($n['title']) ?></div>
                            <div style="font-size:12px;color:var(--gray-600);"><?= htmlspecialchars($n['message']) ?></div>
                            <div style="font-size:11px;color:var(--gray-400);margin-top:4px;"><?= timeAgo($n['created_at']) ?></div>
                        </div>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.notif-dot{position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:#EF4444;border:2px solid white;}
.notif-btn{position:relative;display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:9px;background:var(--gray-100);color:var(--gray-600);transition:all 0.2s;}
.notif-btn:hover{background:var(--gray-200);}
@media(max-width:900px){.main-content div[style*="grid-template-columns:1fr 340px"]{grid-template-columns:1fr!important;}}
</style>
<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('open');}
</script>
</body>
</html>
