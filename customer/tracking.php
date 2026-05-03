<?php
require_once '../includes/auth.php';
requireRole('customer', '../pages/login.php');
$db = getDB();
$user = getCurrentUser();
$uid = (int)$user['id'];

$order = null;
$searchCode = trim($_GET['order'] ?? '');

if ($searchCode) {
    $sc = $db->real_escape_string($searchCode);
    $order = $db->query("SELECT o.*,s.name as service_name,s.duration_hours,u2.name as staff_name FROM orders o JOIN services s ON o.service_id=s.id LEFT JOIN users u2 ON o.staff_id=u2.id WHERE o.order_code='$sc' AND o.user_id=$uid")->fetch_assoc();
}

// Progress map
$statusSteps = [
    'pending'     => 1,
    'in_progress' => 2,
    'ready'       => 3,
    'delivered'   => 4,
    'cancelled'   => 0,
];

$notifCount = getUnreadNotifs($uid);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lacak Pesanan - WashWell</title>
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
                <div class="topbar-title">Lacak Pesanan</div>
            </div>
            <div class="topbar-right">
                <a href="notifications.php" style="position:relative;display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:9px;background:var(--gray-100);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if ($notifCount > 0): ?><span style="position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:#EF4444;border:2px solid white;"></span><?php endif; ?>
                </a>
            </div>
        </header>
        <main class="page-content">

            <!-- Search -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-body">
                    <form method="GET" style="display:flex;gap:10px;">
                        <div class="input-group" style="flex:1;">
                            <span class="input-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </span>
                            <input type="text" name="order" class="form-control" placeholder="Masukkan kode pesanan, contoh: ORD-02001" value="<?= htmlspecialchars($searchCode) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Lacak
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($searchCode && !$order): ?>
            <div class="alert alert-danger">⚠️ Pesanan dengan kode <strong><?= htmlspecialchars($searchCode) ?></strong> tidak ditemukan.</div>
            <?php endif; ?>

            <?php if ($order): ?>
            <?php
            $step = $statusSteps[$order['status']] ?? 0;
            $isCancelled = $order['status'] === 'cancelled';
            $steps = [
                ['label'=>'Pesanan Diterima','icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>','desc'=>'Laundry menerima pesanan Anda'],
                ['label'=>'Sedang Dicuci','icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>','desc'=>'Pakaian Anda sedang dalam proses pencucian'],
                ['label'=>'Siap Diambil','icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>','desc'=>'Pakaian sudah bersih dan siap diambil'],
                ['label'=>'Selesai / Terkirim','icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>','desc'=>'Pesanan telah diterima / dikirim ke alamat Anda'],
            ];
            ?>
            <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">
                <div>
                    <!-- Status Header -->
                    <div class="card" style="margin-bottom:16px;">
                        <div class="card-body">
                            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                                <div>
                                    <div style="font-size:12px;color:var(--gray-500);margin-bottom:4px;">Kode Pesanan</div>
                                    <div style="font-family:'Sora',sans-serif;font-size:22px;font-weight:800;color:var(--primary);"><?= htmlspecialchars($order['order_code']) ?></div>
                                </div>
                                <span class="badge badge-<?= $order['status'] ?>" style="font-size:14px;padding:8px 16px;">
                                    <?= ['pending'=>'Menunggu','in_progress'=>'Sedang Dicuci','ready'=>'Siap Diambil','delivered'=>'Terkirim','cancelled'=>'Dibatalkan'][$order['status']] ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Tracker -->
                    <?php if (!$isCancelled): ?>
                    <div class="card" style="margin-bottom:16px;">
                        <div class="card-header"><span class="card-title">Status Pesanan</span></div>
                        <div class="card-body">
                            <div class="tracker">
                                <?php foreach($steps as $i => $s): ?>
                                <?php $snum = $i+1; $done = $step >= $snum; $active = $step === $snum; ?>
                                <div class="track-step">
                                    <div class="track-line-wrap">
                                        <div class="track-dot <?= $done?'done':($active?'active':'') ?>">
                                            <?php if($done): ?><?= $s['icon'] ?><?php elseif($active): ?><div class="dot-pulse"></div><?php else: ?><span style="font-size:12px;font-weight:700;"><?= $snum ?></span><?php endif; ?>
                                        </div>
                                        <?php if($i < count($steps)-1): ?><div class="track-connector <?= $step>$snum?'done':'' ?>"></div><?php endif; ?>
                                    </div>
                                    <div class="track-label">
                                        <div style="font-weight:700;font-size:13px;color:<?= $done?'var(--gray-800)':'var(--gray-400)' ?>;"><?= $s['label'] ?></div>
                                        <div style="font-size:12px;color:var(--gray-500);"><?= $s['desc'] ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-danger">❌ Pesanan ini telah dibatalkan.</div>
                    <?php endif; ?>

                    <!-- Catatan -->
                    <?php if ($order['notes']): ?>
                    <div class="card">
                        <div class="card-header"><span class="card-title">Catatan</span></div>
                        <div class="card-body"><p style="font-size:13px;color:var(--gray-600);"><?= nl2br(htmlspecialchars($order['notes'])) ?></p></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Detail Card -->
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div class="card">
                        <div class="card-header"><span class="card-title">Detail Pesanan</span></div>
                        <div class="card-body">
                            <?php $details = [
                                ['Layanan', htmlspecialchars($order['service_name'])],
                                ['Berat', $order['weight'] . ' kg'],
                                ['Tgl Pickup', $order['pickup_date'] ? date('d M Y', strtotime($order['pickup_date'])) : '-'],
                                ['Estimasi Selesai', $order['delivery_date'] ? date('d M Y', strtotime($order['delivery_date'])) : '-'],
                                ['Petugas', $order['staff_name'] ? htmlspecialchars($order['staff_name']) : 'Belum ditugaskan'],
                                ['Metode Bayar', ucfirst($order['payment_method'])],
                            ]; ?>
                            <?php foreach($details as $d): ?>
                            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:13px;">
                                <span style="color:var(--gray-500);"><?= $d[0] ?></span>
                                <span style="font-weight:600;text-align:right;"><?= $d[1] ?></span>
                            </div>
                            <?php endforeach; ?>
                            <div style="display:flex;justify-content:space-between;padding:12px 0;font-size:14px;">
                                <span style="font-weight:700;">Total</span>
                                <span style="font-weight:800;font-family:'Sora',sans-serif;color:var(--primary)"><?= formatRupiah($order['amount']) ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;font-size:13px;">
                                <span>Status Bayar</span>
                                <span class="badge badge-<?= $order['payment_status'] ?>"><?= $order['payment_status']==='paid'?'Lunas':'Belum Bayar' ?></span>
                            </div>
                            <?php if($order['payment_status']==='unpaid' && $order['status']!=='cancelled'): ?>
                            <a href="payments.php?order_id=<?= $order['id'] ?>" class="btn btn-orange btn-full" style="margin-top:14px;">Bayar Sekarang</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="orders.php" class="btn btn-ghost btn-full">← Kembali ke Pesanan</a>
                </div>
            </div>
            <?php else: ?>
            <!-- Pilih dari daftar -->
            <?php
            $activeOrders = $db->query("SELECT o.*,s.name as service_name FROM orders o JOIN services s ON o.service_id=s.id WHERE o.user_id=$uid AND o.status NOT IN ('delivered','cancelled') ORDER BY o.created_at DESC");
            ?>
            <?php if ($activeOrders->num_rows > 0): ?>
            <div class="card">
                <div class="card-header"><span class="card-title">Pesanan Aktif Anda</span></div>
                <div class="card-body">
                    <div style="display:grid;gap:10px;">
                    <?php while($o=$activeOrders->fetch_assoc()): ?>
                    <a href="tracking.php?order=<?= urlencode($o['order_code']) ?>" style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border:1.5px solid var(--gray-200);border-radius:12px;transition:all 0.2s;cursor:pointer;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--gray-200)'">
                        <div>
                            <div style="font-weight:700;color:var(--primary);"><?= htmlspecialchars($o['order_code']) ?></div>
                            <div style="font-size:12px;color:var(--gray-500);"><?= htmlspecialchars($o['service_name']) ?> · <?= $o['weight'] ?> kg</div>
                        </div>
                        <span class="badge badge-<?= $o['status'] ?>"><?= ['pending'=>'Menunggu','in_progress'=>'Diproses','ready'=>'Siap Diambil'][$o['status']] ?? $o['status'] ?></span>
                    </a>
                    <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:60px 0;color:var(--gray-400);">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:0.4;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <p style="font-size:14px;font-weight:600;margin-bottom:6px;">Tidak ada pesanan aktif</p>
                <a href="new_order.php" class="btn btn-primary" style="margin-top:10px;">Buat Pesanan Baru</a>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
.tracker{display:flex;flex-direction:column;gap:0;}
.track-step{display:flex;gap:16px;align-items:flex-start;}
.track-line-wrap{display:flex;flex-direction:column;align-items:center;flex-shrink:0;}
.track-dot{width:40px;height:40px;border-radius:50%;border:2px solid var(--gray-200);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--gray-400);transition:all 0.3s;}
.track-dot.done{background:var(--primary);border-color:var(--primary);color:white;}
.track-dot.active{border-color:var(--primary);background:var(--primary-bg);color:var(--primary);}
.track-connector{width:2px;height:40px;background:var(--gray-200);margin:4px 0;transition:background 0.3s;}
.track-connector.done{background:var(--primary);}
.track-label{padding:8px 0 32px;}
.dot-pulse{width:12px;height:12px;border-radius:50%;background:var(--primary);animation:pulse 1.5s infinite;}
@keyframes pulse{0%,100%{transform:scale(1);opacity:1;}50%{transform:scale(1.3);opacity:0.6;}}
</style>
<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('open');}
</script>
</body>
</html>
