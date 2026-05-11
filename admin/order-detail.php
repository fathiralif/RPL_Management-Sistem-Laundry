<?php
require_once '../includes/auth.php';
requireAdmin('../pages/login.php');
$db = getDB();
$user = getCurrentUser();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: orders.php');
    exit;
}

$order = $db->query("
    SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
           s.name as service_name, s.price_per_kg, s.unit_type
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN services s ON o.service_id = s.id
    WHERE o.id = $id
")->fetch_assoc();

if (!$order) {
    header('Location: orders.php');
    exit;
}

$statusLabels = [
    'pending'       => 'Menunggu',
    'washing'       => 'Sedang Dicuci',
    'drying'        => 'Pengeringan',
    'ironing'       => 'Setrika',
    'ready_pickup'  => 'Siap Diambil',
    'ready_deliver' => 'Siap Diantar',
    'done'          => 'Selesai',
    'cancelled'     => 'Dibatalkan',
];

$statusSteps = ['pending','washing','drying','ironing',
    ($order['delivery_type'] ?? 'pickup') === 'delivery' ? 'ready_deliver' : 'ready_pickup',
    'done'];
$currentStep = array_search($order['status'], $statusSteps);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Pesanan <?= htmlspecialchars($order['order_code']) ?> - WashWell</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.info-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--gray-100); font-size:13.5px; }
.info-row:last-child { border-bottom:none; }
.info-label { color:var(--gray-500); font-weight:600; }
.info-value { color:var(--gray-800); font-weight:700; text-align:right; }
.progress-steps { display:flex; align-items:center; gap:0; margin:20px 0; overflow-x:auto; padding:4px 0; }
.step { display:flex; flex-direction:column; align-items:center; flex:1; min-width:80px; }
.step-circle { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:700; border:3px solid var(--gray-200); background:white; color:var(--gray-300); position:relative; z-index:1; }
.step-circle.done { background:var(--primary); border-color:var(--primary); color:white; }
.step-circle.current { background:white; border-color:var(--primary); color:var(--primary); box-shadow:0 0 0 4px var(--primary-bg); }
.step-label { font-size:10px; color:var(--gray-400); margin-top:6px; text-align:center; font-weight:600; line-height:1.3; }
.step-label.done, .step-label.current { color:var(--primary); }
.step-line { flex:1; height:3px; background:var(--gray-200); margin-top:-18px; }
.step-line.done { background:var(--primary); }
@media(max-width:700px) { .detail-grid { grid-template-columns:1fr; } }
</style>
</head>
<body>
<div class="app-layout">
    <?php require_once 'partials/sidebar.php'; ?>
    <div class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="hamburger" onclick="openSidebar()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div>
                    <div class="topbar-title">Detail Pesanan</div>
                </div>
            </div>
            <div class="topbar-right">
                <div style="display:flex;align-items:center;gap:10px;padding:6px 12px;border-radius:10px;background:var(--gray-100)">
                    <div class="avatar avatar-sm"><?= strtoupper(substr($user['name'],0,1)) ?></div>
                    <div>
                        <div style="font-size:13px;font-weight:700;color:var(--gray-800)"><?= htmlspecialchars($user['name']) ?></div>
                        <div style="font-size:11px;color:var(--gray-500)">Admin</div>
                    </div>
                </div>
            </div>
        </header>
        <main class="page-content">
            <div class="page-header">
                <div>
                    <h1 style="display:flex;align-items:center;gap:10px;">
                        <?= htmlspecialchars($order['order_code']) ?>
                        <span class="badge badge-<?= $order['status'] ?>"><?= $statusLabels[$order['status']] ?? $order['status'] ?></span>
                    </h1>
                    <p>Dibuat: <?= date('d F Y, H:i', strtotime($order['created_at'])) ?></p>
                </div>
                <a href="orders.php" class="btn btn-ghost btn-sm">← Kembali ke Pesanan</a>
            </div>

            <!-- Progress Steps -->
            <?php if ($order['status'] !== 'cancelled'): ?>
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><span class="card-title">🔄 Progress Pesanan</span></div>
                <div class="card-body">
                    <div class="progress-steps">
                        <?php
                        $icons = ['⏳','🧼','💨','🔥','✅','🚚','🎉'];
                        $names = ['Menunggu','Dicuci','Dikeringkan','Disetrika','Siap Diambil','Siap Diantar','Selesai'];
                        for ($i = 0; $i < count($statusSteps); $i++):
                            $s = $currentStep === false ? -1 : $currentStep;
                            $cls = $i < $s ? 'done' : ($i === $s ? 'current' : '');
                        ?>
                        <?php if ($i > 0): ?>
                        <div class="step-line <?= $i <= $s ? 'done' : '' ?>"></div>
                        <?php endif; ?>
                        <div class="step">
                            <div class="step-circle <?= $cls ?>"><?= $icons[$i] ?></div>
                            <div class="step-label <?= $cls ?>"><?= $names[$i] ?></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="detail-grid">
                <!-- Informasi Pesanan -->
                <div class="card">
                    <div class="card-header"><span class="card-title">📋 Informasi Pesanan</span></div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Order ID</span>
                            <span class="info-value" style="font-family:monospace;color:var(--primary)"><?= htmlspecialchars($order['order_code']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Layanan</span>
                            <span class="info-value"><?= htmlspecialchars($order['service_name']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Berat</span>
                            <span class="info-value"><?= number_format((float)$order['weight'], 2) ?> <?= $order['unit_type'] === 'item' ? 'item' : 'kg' ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Harga/<?= $order['unit_type'] === 'item' ? 'item' : 'kg' ?></span>
                            <span class="info-value"><?= formatRupiah($order['price_per_kg']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Total</span>
                            <span class="info-value" style="color:var(--green);font-size:16px"><?= formatRupiah($order['amount']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status</span>
                            <span class="info-value"><span class="badge badge-<?= $order['status'] ?>"><?= $statusLabels[$order['status']] ?? $order['status'] ?></span></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Pembayaran</span>
                            <span class="info-value"><span class="badge badge-<?= $order['payment_status'] ?>"><?= $order['payment_status'] === 'paid' ? 'Lunas' : 'Belum Bayar' ?></span></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Metode Bayar</span>
                            <span class="info-value" style="text-transform:capitalize"><?= htmlspecialchars($order['payment_method']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Informasi Pelanggan & Jadwal -->
                <div style="display:flex;flex-direction:column;gap:20px;">
                    <div class="card">
                        <div class="card-header"><span class="card-title">👤 Pelanggan</span></div>
                        <div class="card-body">
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                                <div class="avatar" style="width:48px;height:48px;font-size:18px;"><?= strtoupper(substr($order['customer_name'],0,1)) ?></div>
                                <div>
                                    <div style="font-weight:700;font-size:15px;color:var(--gray-800)"><?= htmlspecialchars($order['customer_name']) ?></div>
                                    <div style="font-size:12px;color:var(--gray-400)"><?= htmlspecialchars($order['customer_email']) ?></div>
                                </div>
                            </div>
                            <?php if ($order['customer_phone']): ?>
                            <div class="info-row">
                                <span class="info-label">Telepon</span>
                                <span class="info-value"><?= htmlspecialchars($order['customer_phone']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><span class="card-title">📅 Jadwal</span></div>
                        <div class="card-body">
                            <div class="info-row">
                                <span class="info-label">Tgl Antar</span>
                                <span class="info-value"><?= $order['pickup_date'] ? date('d F Y', strtotime($order['pickup_date'])) : '-' ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Estimasi Selesai</span>
                                <span class="info-value"><?= $order['delivery_date'] ? date('d F Y', strtotime($order['delivery_date'])) : '-' ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Tipe Pengambilan</span>
                                <span class="info-value" style="text-transform:capitalize"><?= $order['delivery_type'] === 'delivery' ? '🚚 Diantar' : '🏪 Ambil Sendiri' ?></span>
                            </div>
                            <?php if ($order['driver_name']): ?>
                            <div class="info-row">
                                <span class="info-label">Driver</span>
                                <span class="info-value"><?= htmlspecialchars($order['driver_name']) ?> <?= $order['driver_phone'] ? '(' . htmlspecialchars($order['driver_phone']) . ')' : '' ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($order['notes']): ?>
                            <div class="info-row" style="flex-direction:column;gap:4px;">
                                <span class="info-label">Catatan</span>
                                <span style="color:var(--gray-600);font-size:13px;line-height:1.5"><?= nl2br(htmlspecialchars($order['notes'])) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div style="display:flex;gap:12px;margin-top:8px;">
                <a href="orders.php" class="btn btn-ghost">← Kembali</a>
                <?php if (!in_array($order['status'], ['done','cancelled'])): ?>
                <a href="orders.php?highlight=<?= $order['id'] ?>" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/></svg>
                    Update Status
                </a>
                <?php endif; ?>
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
</script>
</body>
</html>
