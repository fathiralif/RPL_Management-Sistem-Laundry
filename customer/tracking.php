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
    $order = $db->query("
        SELECT o.*, s.name as service_name, s.duration_hours,
               o.delivery_type, o.delivery_address,
               o.driver_name, o.driver_phone, o.delivery_status
        FROM orders o
        JOIN services s ON o.service_id = s.id
        WHERE o.order_code = '$sc' AND o.user_id = $uid
    ")->fetch_assoc();
}

// Progress map — 8 status baru
$statusSteps = [
    'pending'       => 1,
    'washing'       => 2,
    'drying'        => 3,
    'ironing'       => 4,
    'ready_pickup'  => 5,
    'ready_deliver' => 5,
    'done'          => 6,
    'cancelled'     => -1,
];

$statusLabels = [
    'pending'       => 'Menunggu',
    'washing'       => 'Sedang Dicuci 🧼',
    'drying'        => 'Pengeringan 💨',
    'ironing'       => 'Setrika 🔥',
    'ready_pickup'  => 'Siap Diambil ✅',
    'ready_deliver' => 'Siap Diantar 🚚',
    'done'          => 'Selesai 🎉',
    'cancelled'     => 'Dibatalkan ❌',
    // Status lama (kompatibilitas)
    'in_progress'   => 'Dalam Proses',
    'ready'         => 'Siap',
    'delivered'     => 'Terkirim',
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
                            <input type="text" name="order" class="form-control" placeholder="Masukkan kode pesanan, contoh: WW-0001" value="<?= htmlspecialchars($searchCode) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Lacak
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($searchCode && !$order): ?>
            <div class="alert alert-danger">&#9888;&#65039; Pesanan dengan kode <strong><?= htmlspecialchars($searchCode) ?></strong> tidak ditemukan.</div>
            <?php endif; ?>

            <?php if ($order): ?>
            <?php
            $currentStatus = $order['status'];
            $currentStep   = $statusSteps[$currentStatus] ?? 1;
            $isCancelled   = ($currentStatus === 'cancelled');

            $steps = [
                1 => ['&#128336;', 'Menunggu'],
                2 => ['&#129532;', 'Dicuci'],
                3 => ['&#128168;', 'Kering'],
                4 => ['&#128293;', 'Setrika'],
                5 => ($order['delivery_type'] ?? 'pickup') === 'delivery'
                     ? ['&#128666;', 'Siap Diantar']
                     : ['&#9989;',   'Siap Diambil'],
                6 => ['&#127881;', 'Selesai'],
            ];
            $totalSteps = count($steps);

            // Hitung persentase progress line
            $progressPct = $totalSteps > 1
                ? round((($currentStep - 1) / ($totalSteps - 1)) * 100, 2)
                : 0;
            if ($currentStep >= $totalSteps) $progressPct = 100;

            $stepDescs = [
                'pending'       => 'Pesanan Anda telah diterima dan sedang menunggu konfirmasi dari laundry.',
                'washing'       => 'Pakaian Anda sedang dalam proses pencucian. Kami merawatnya dengan baik!',
                'drying'        => 'Pakaian sudah selesai dicuci dan sedang dalam proses pengeringan.',
                'ironing'       => 'Pakaian kering dan sedang disetrika/digosok agar rapi.',
                'ready_pickup'  => 'Pakaian sudah bersih dan siap! Silakan datang mengambil ke laundry kami.',
                'ready_deliver' => 'Pakaian sedang disiapkan untuk diantar ke alamat Anda.',
                'done'          => 'Pesanan telah selesai. Terima kasih telah mempercayai WashWell!',
            ];

            $payLabels = [
                'cash'     => '&#128181; Cash',
                'transfer' => '&#127981; Transfer Bank',
                'e-wallet' => '&#128242; E-Wallet',
                'cod'      => '&#128663; COD',
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
                                <span class="badge badge-<?= $currentStatus ?>" style="font-size:14px;padding:8px 16px;">
                                    <?= $statusLabels[$currentStatus] ?? $currentStatus ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Stepper Visual -->
                    <?php if (!$isCancelled): ?>
                    <div class="card" style="margin-bottom:16px;">
                        <div class="card-header"><span class="card-title">Status Pesanan</span></div>
                        <div class="card-body">
                            <!-- Stepper -->
                            <div class="stepper-wrap">
                                <div class="stepper-line-bg"></div>
                                <div class="stepper-line-progress" style="width:<?= $progressPct ?>%;"></div>

                                <?php foreach ($steps as $num => [$icon, $label]):
                                    $isDone   = $num < $currentStep;
                                    $isActive = $num === $currentStep;
                                ?>
                                <div class="stepper-step">
                                    <div class="stepper-dot <?= $isDone ? 'done' : ($isActive ? 'active' : '') ?>">
                                        <?= $isDone ? '&#10003;' : $icon ?>
                                    </div>
                                    <div class="stepper-label <?= $isActive ? 'active' : ($isDone ? 'done' : '') ?>">
                                        <?= $label ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Deskripsi status aktif -->
                            <div style="margin-top:20px;background:var(--gray-50);border-radius:10px;padding:14px 16px;font-size:13px;color:var(--gray-600);line-height:1.6;">
                                <?= $stepDescs[$currentStatus] ?? '' ?>
                            </div>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- Status Cancelled -->
                    <div class="card" style="margin-bottom:16px;">
                        <div class="card-body">
                            <div style="background:#FEE2E2;border:1.5px solid #FECACA;border-radius:12px;padding:28px 20px;text-align:center;">
                                <div style="font-size:40px;margin-bottom:10px;">&#10060;</div>
                                <div style="font-family:'Sora',sans-serif;font-size:17px;font-weight:700;color:#991B1B;margin-bottom:6px;">Pesanan Dibatalkan</div>
                                <div style="font-size:12px;color:#B91C1C;">Pesanan ini telah dibatalkan. Hubungi kami jika ada pertanyaan.</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Info Driver -->
                    <?php if (!empty($order['driver_name']) || ($order['delivery_type'] ?? '') === 'delivery'): ?>
                    <div class="card" style="margin-bottom:16px;">
                        <div class="card-header"><span class="card-title">&#128666; Info Pengantaran</span></div>
                        <div class="card-body">
                            <div style="background:var(--teal-light);border:1.5px solid #A5F3FC;border-radius:12px;padding:16px;">
                                <?php if (!empty($order['driver_name'])): ?>
                                <div style="display:flex;align-items:center;gap:12px;<?= !empty($order['delivery_address']) ? 'margin-bottom:12px;' : '' ?>">
                                    <div style="width:44px;height:44px;border-radius:50%;background:var(--teal);display:flex;align-items:center;justify-content:center;font-family:'Sora',sans-serif;font-size:18px;font-weight:800;color:white;flex-shrink:0;">
                                        <?= strtoupper(mb_substr($order['driver_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-size:14px;font-weight:700;color:var(--gray-800);"><?= htmlspecialchars($order['driver_name']) ?></div>
                                        <?php if (!empty($order['driver_phone'])): ?>
                                        <a href="tel:<?= htmlspecialchars($order['driver_phone']) ?>" style="font-size:12px;color:var(--teal);font-weight:600;display:inline-flex;align-items:center;gap:4px;margin-top:3px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.21 14.92a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                            <?= htmlspecialchars($order['driver_phone']) ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div style="font-size:13px;color:#0E7490;<?= !empty($order['delivery_address']) ? 'margin-bottom:12px;' : '' ?>">
                                    &#128666; Driver belum ditugaskan. Kami akan segera menghubungi Anda.
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($order['delivery_address'])): ?>
                                <div style="padding-top:10px;border-top:1px solid #A5F3FC;font-size:12px;color:#0E7490;display:flex;align-items:flex-start;gap:6px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-top:1px;flex-shrink:0;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <?= htmlspecialchars($order['delivery_address']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Catatan -->
                    <?php if (!empty($order['notes'])): ?>
                    <div class="card">
                        <div class="card-header"><span class="card-title">Catatan</span></div>
                        <div class="card-body"><p style="font-size:13px;color:var(--gray-600);"><?= nl2br(htmlspecialchars($order['notes'])) ?></p></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar Detail Card -->
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div class="card">
                        <div class="card-header"><span class="card-title">Detail Pesanan</span></div>
                        <div class="card-body">
                            <?php
                            $details = [
                                ['Layanan',          htmlspecialchars($order['service_name'])],
                                ['Berat / Jumlah',   number_format((float)$order['weight'], 1) . ' kg'],
                                ['Tgl Pickup',       $order['pickup_date']   ? date('d M Y', strtotime($order['pickup_date']))   : '-'],
                                ['Est. Selesai',     $order['delivery_date'] ? date('d M Y', strtotime($order['delivery_date'])) : '-'],
                                ['Pengiriman',       ($order['delivery_type'] ?? 'pickup') === 'delivery' ? '&#128666; Antar ke Alamat' : '&#127978; Ambil di Laundry'],
                                ['Metode Bayar',     $payLabels[$order['payment_method'] ?? ''] ?? ucfirst($order['payment_method'] ?? '-')],
                            ];
                            ?>
                            <?php foreach ($details as $d): ?>
                            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:13px;">
                                <span style="color:var(--gray-500);"><?= $d[0] ?></span>
                                <span style="font-weight:600;text-align:right;"><?= $d[1] ?></span>
                            </div>
                            <?php endforeach; ?>

                            <div style="display:flex;justify-content:space-between;padding:12px 0;font-size:14px;border-bottom:1px solid var(--gray-100);">
                                <span style="font-weight:700;">Total</span>
                                <span style="font-weight:800;font-family:'Sora',sans-serif;color:var(--primary)"><?= formatRupiah($order['amount']) ?></span>
                            </div>

                            <!-- Badge Status Bayar -->
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;">
                                <span style="font-size:13px;color:var(--gray-500);">Status Bayar</span>
                                <?php if ($order['payment_status'] === 'paid'): ?>
                                <span style="display:inline-flex;align-items:center;gap:5px;background:var(--green-light);color:#166534;border:1.5px solid #86EFAC;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;">&#9989; LUNAS</span>
                                <?php elseif (($order['payment_method'] ?? '') === 'cod'): ?>
                                <span style="display:inline-flex;align-items:center;gap:5px;background:var(--orange-light);color:#C2410C;border:1.5px solid #FED7AA;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;">&#128663; COD</span>
                                <?php else: ?>
                                <span style="display:inline-flex;align-items:center;gap:5px;background:#FEE2E2;color:#991B1B;border:1.5px solid #FECACA;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;">&#8987; BELUM BAYAR</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($order['payment_status'] === 'unpaid' && $order['status'] !== 'cancelled' && ($order['payment_method'] ?? '') !== 'cod'): ?>
                            <a href="payments.php?order_id=<?= $order['id'] ?>" class="btn btn-primary btn-full" style="margin-top:10px;">&#128179; Bayar Sekarang</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="orders.php" class="btn btn-ghost btn-full">&#8592; Kembali ke Pesanan</a>
                </div>
            </div>

            <?php else: ?>
            <!-- Daftar pesanan aktif -->
            <?php
            $activeOrders = $db->query("
                SELECT o.*, s.name as service_name
                FROM orders o
                JOIN services s ON o.service_id = s.id
                WHERE o.user_id = $uid
                  AND o.status NOT IN ('done','cancelled')
                ORDER BY o.created_at DESC
            ");
            ?>
            <?php if ($activeOrders && $activeOrders->num_rows > 0): ?>
            <div class="card">
                <div class="card-header"><span class="card-title">Pesanan Aktif Anda</span></div>
                <div class="card-body">
                    <div style="display:grid;gap:10px;">
                    <?php while ($o = $activeOrders->fetch_assoc()): ?>
                    <a href="tracking.php?order=<?= urlencode($o['order_code']) ?>"
                       style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border:1.5px solid var(--gray-200);border-radius:12px;transition:all 0.2s;cursor:pointer;text-decoration:none;"
                       onmouseover="this.style.borderColor='var(--primary)'"
                       onmouseout="this.style.borderColor='var(--gray-200)'">
                        <div>
                            <div style="font-weight:700;color:var(--primary);"><?= htmlspecialchars($o['order_code']) ?></div>
                            <div style="font-size:12px;color:var(--gray-500);margin-top:2px;">
                                <?= htmlspecialchars($o['service_name']) ?>
                                <span style="display:inline-flex;align-items:center;background:var(--primary-bg);padding:1px 7px;border-radius:20px;font-size:11px;font-weight:700;color:var(--primary);margin-left:4px;">
                                    <?= number_format((float)$o['weight'], 1) ?> kg
                                </span>
                            </div>
                        </div>
                        <span class="badge badge-<?= $o['status'] ?>">
                            <?= $statusLabels[$o['status']] ?? $o['status'] ?>
                        </span>
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
/* ========== STEPPER HORIZONTAL ========== */
.stepper-wrap {
    position: relative;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding-bottom: 8px;
}
.stepper-line-bg,
.stepper-line-progress {
    position: absolute;
    top: 19px;
    left: calc(100% / 12);
    height: 3px;
    border-radius: 3px;
}
.stepper-line-bg {
    right: calc(100% / 12);
    background: var(--gray-200);
    z-index: 0;
}
.stepper-line-progress {
    background: var(--primary);
    z-index: 1;
    right: auto;
    transition: width 0.6s ease;
}
.stepper-step {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    position: relative;
    z-index: 2;
}
.stepper-dot {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--gray-100);
    border: 2px solid var(--gray-200);
    color: var(--gray-400);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: all 0.3s ease;
}
.stepper-dot.done {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
    font-weight: 700;
}
.stepper-dot.active {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
    box-shadow: 0 0 0 5px rgba(37,99,235,0.15);
    animation: stepPulse 2s infinite;
}
@keyframes stepPulse {
    0%,100% { box-shadow: 0 0 0 4px rgba(37,99,235,0.15); }
    50%      { box-shadow: 0 0 0 9px rgba(37,99,235,0.05); }
}
.stepper-label {
    font-size: 10.5px;
    font-weight: 500;
    color: var(--gray-400);
    text-align: center;
    white-space: nowrap;
}
.stepper-label.done   { color: var(--primary); font-weight: 600; }
.stepper-label.active { color: var(--primary); font-weight: 800; }
</style>

<script>
function openSidebar()  { document.getElementById('sidebar').classList.add('open');    document.getElementById('sidebarOverlay').classList.add('open');    }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('open'); }
</script>
</body>
</html>
