<?php
require_once '../includes/auth.php';
requireRole('customer', '../pages/login.php');
$db = getDB();
$user = getCurrentUser();
$uid = (int)$user['id'];

$msg = ''; $msgType = '';
$orderSuccess = null;

// Jenis pakaian yang tersedia
$clothTypes = [
    ['id'=>'reguler','label'=>'Reguler','icon'=>'👕','desc'=>'Kaos, kemeja biasa'],
    ['id'=>'katun','label'=>'Katun','icon'=>'🧥','desc'=>'Bahan katun halus'],
    ['id'=>'jas','label'=>'Jas/Blazer','icon'=>'🥼','desc'=>'Jas, blazer, coat'],
    ['id'=>'sutra','label'=>'Sutra','icon'=>'✨','desc'=>'Bahan sutra & silk'],
    ['id'=>'jeans','label'=>'Jeans','icon'=>'👖','desc'=>'Celana & jaket jeans'],
    ['id'=>'batik','label'=>'Batik','icon'=>'🎨','desc'=>'Batik & kain motif'],
    ['id'=>'wool','label'=>'Wool','icon'=>'🧶','desc'=>'Sweater & bahan wool'],
    ['id'=>'lainnya','label'=>'Lainnya','icon'=>'🧺','desc'=>'Jenis pakaian lain'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid      = (int)$_POST['service_id'];
    $pickup   = trim($_POST['pickup_date']);
    $notes    = trim($_POST['notes'] ?? '');
    $delivery_type = $_POST['delivery_type'] ?? 'pickup';
    $address  = trim($_POST['delivery_address'] ?? $user['address'] ?? '');
    
   // Ambil items dari POST
    $itemTypes  = $_POST['item_type'] ?? [];
    $itemWeights = $_POST['item_weight'] ?? [];
    $itemNotes  = $_POST['item_note'] ?? [];

    // Validasi minimal 1 item
    $validItems = [];
    foreach ($itemTypes as $i => $type) {
        $w = max(0, (float)($itemWeights[$i] ?? 0));
        if ($type && $w > 0) {
            $validItems[] = [
                'type'   => $type,
                'weight' => $w,
                'note'   => trim($itemNotes[$i] ?? ''),
            ];
        }
    }

    if (!$sid || !$pickup) {
        $msg = 'Mohon lengkapi semua data yang diperlukan.'; $msgType = 'danger';
    } elseif (empty($validItems)) {
        $msg = 'Tambahkan minimal 1 item pakaian.'; $msgType = 'danger';
    } else {
        $svc = $db->query("SELECT * FROM services WHERE id=$sid AND is_active=1")->fetch_assoc();
        if (!$svc) {
            $msg = 'Layanan tidak ditemukan.'; $msgType = 'danger';
        } else {
            $totalWeight = array_sum(array_column($validItems, 'weight'));
            $amount = $svc['price_per_kg'] * $totalWeight;
            $duration = (int)$svc['duration_hours'];
            $delivery_date = date('Y-m-d', strtotime($pickup . ' + ' . $duration . ' hours'));
            $code = generateOrderCode();

            // Bangun ringkasan item untuk notes
            $itemSummary = implode(', ', array_map(fn($it) => ucfirst($it['type']).' ('.$it['weight'].'kg)'.($it['note']?': '.$it['note']:''), $validItems));
            $fullNotes = "Items: $itemSummary";
            if ($delivery_type === 'delivery') {
                $fullNotes = "[ANTAR KE: $address] " . $fullNotes;
            }
            if ($notes) $fullNotes .= " | Catatan: $notes";

            // Payment method + COD otomatis jika jasa antar
            $payment = $_POST['payment_method'] ?? 'transfer';
            if ($delivery_type === 'delivery' && !in_array($payment, ['transfer','e-wallet'])) {
                $payment = 'cod';
            }

            $stmt = $db->prepare("INSERT INTO orders (order_code,user_id,service_id,weight,amount,pickup_date,delivery_date,notes,payment_method,delivery_type,delivery_address) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('siiddssssss', $code,$uid,$sid,$totalWeight,$amount,$pickup,$delivery_date,$fullNotes,$payment,$delivery_type,$address);
            if ($stmt->execute()) {
                $orderId = $stmt->insert_id;

                // INSERT ke order_items
                foreach ($validItems as $item) {
                    $ctype   = $db->real_escape_string($item['type']);
                    $cweight = (float)$item['weight'];
                    $cnote   = $db->real_escape_string($item['note']);
                    $db->query("INSERT INTO order_items (order_id, cloth_type, weight, note) VALUES ($orderId, '$ctype', $cweight, '$cnote')");
                }

                // Notifikasi customer - sukses buat pesanan
                $custName = $db->real_escape_string($user['name']);
                $amtFmt   = formatRupiah($amount);
                $svcName  = $db->real_escape_string($svc['name']);
                $db->query("INSERT INTO notifications (user_id,title,message) VALUES ($uid,'🎉 Pesanan Berhasil Dibuat!','Halo $custName! Pesanan Anda ($code) untuk layanan $svcName sebesar $amtFmt telah kami terima dan sedang menunggu konfirmasi. Terima kasih telah mempercayai WashWell! 🧺')");

                // Notifikasi ke admin
                $db->query("INSERT INTO notifications (user_id,title,message) VALUES (1,'Pesanan Baru','Ada pesanan baru dari $custName - $code | $svcName | " . round($totalWeight,1) . " kg | $amtFmt')");

                $orderSuccess = [
                    'code'   => $code,
                    'svc'    => $svc['name'],
                    'weight' => $totalWeight,
                    'amount' => $amount,
                    'items'  => $validItems,
                    'pickup' => $pickup,
                    'payment'=> $payment,
                ];
                $msgType = 'success';
            } else {
                $msg = 'Gagal membuat pesanan. Silakan coba lagi.'; $msgType = 'danger';
            }
        }
    }
}


$services = $db->query("SELECT * FROM services WHERE is_active=1 ORDER BY name");
$notifCount = getUnreadNotifs($uid);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buat Pesanan - WashWell</title>
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
                <div class="topbar-title">Buat Pesanan Baru</div>
            </div>
            <div class="topbar-right">
                <a href="notifications.php" class="notif-btn" style="position:relative;display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:9px;background:var(--gray-100);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if ($notifCount > 0): ?><span style="position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:#EF4444;border:2px solid white;"></span><?php endif; ?>
                </a>
                <a href="profile.php" style="display:flex;align-items:center;gap:8px;padding:6px 12px;border-radius:10px;background:var(--gray-100);">
                    <div class="avatar avatar-sm"><?= strtoupper(substr($user['name'],0,1)) ?></div>
                    <span style="font-size:13px;font-weight:600"><?= htmlspecialchars(explode(' ',$user['name'])[0]) ?></span>
                </a>
            </div>
        </header>
        <main class="page-content">

        <?php if ($msgType === 'danger'): ?>
        <div class="alert alert-danger">⚠️ <?= $msg ?></div>
        <?php endif; ?>

        <?php if ($orderSuccess): ?>
        <!-- SUKSES MODAL / BANNER -->
        <div id="successBanner" style="position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;">
            <div style="background:white;border-radius:20px;max-width:480px;width:100%;padding:0;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.25);">
                <!-- Header hijau -->
                <div style="background:linear-gradient(135deg,#22C55E,#16A34A);padding:28px 28px 20px;text-align:center;">
                    <div style="width:64px;height:64px;background:rgba(255,255,255,0.25);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:30px;">✅</div>
                    <div style="color:white;font-size:20px;font-weight:800;font-family:'Sora',sans-serif;margin-bottom:4px;">Pesanan Berhasil Dibuat!</div>
                    <div style="color:rgba(255,255,255,0.85);font-size:13px;">Terima kasih telah mempercayai WashWell 🧺</div>
                </div>
                <!-- Body -->
                <div style="padding:20px 24px;">
                    <div style="background:#F0FDF4;border-radius:12px;padding:16px;margin-bottom:16px;">
                        <div style="font-size:11px;color:#16A34A;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Kode Pesanan</div>
                        <div style="font-size:22px;font-weight:800;font-family:'Sora',sans-serif;color:#15803D;letter-spacing:1px;"><?= htmlspecialchars($orderSuccess['code']) ?></div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
                        <div style="background:var(--gray-50);border-radius:10px;padding:12px;">
                            <div style="font-size:11px;color:var(--gray-500);margin-bottom:3px;">Layanan</div>
                            <div style="font-size:13px;font-weight:700;"><?= htmlspecialchars($orderSuccess['svc']) ?></div>
                        </div>
                        <div style="background:var(--gray-50);border-radius:10px;padding:12px;">
                            <div style="font-size:11px;color:var(--gray-500);margin-bottom:3px;">Total Berat</div>
                            <div style="font-size:13px;font-weight:700;"><?= number_format($orderSuccess['weight'],1) ?> kg</div>
                        </div>
                    </div>

                    <!-- Item List -->
                    <div style="margin-bottom:16px;">
                        <div style="font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Item Pakaian</div>
                        <div style="display:flex;flex-direction:column;gap:6px;">
                        <?php foreach($orderSuccess['items'] as $it): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--gray-50);border-radius:8px;">
                            <span style="font-size:13px;color:var(--gray-700);">
                                <?php
                                $icons = ['reguler'=>'👕','katun'=>'🧥','jas'=>'🥼','sutra'=>'✨','jeans'=>'👖','batik'=>'🎨','wool'=>'🧶','lainnya'=>'🧺'];
                                echo ($icons[$it['type']] ?? '👕') . ' ' . ucfirst(htmlspecialchars($it['type']));
                                if ($it['note']) echo ' <span style="color:var(--gray-400);font-size:11px;">· '.htmlspecialchars($it['note']).'</span>';
                                ?>
                            </span>
                            <span style="font-size:13px;font-weight:600;"><?= $it['weight'] ?> kg</span>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Total & Pembayaran -->
                    <div style="border-top:1px solid var(--gray-100);padding-top:14px;margin-bottom:16px;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                            <span style="font-size:13px;color:var(--gray-500);">Total Tagihan</span>
                            <span style="font-size:18px;font-weight:800;font-family:'Sora',sans-serif;color:var(--primary);"><?= formatRupiah($orderSuccess['amount']) ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:13px;color:var(--gray-500);">Metode Bayar</span>
                            <?php
                            $payLabels = ['transfer'=>'🏦 Transfer Bank','e-wallet'=>'📱 E-Wallet'];
                            echo '<span style="font-size:13px;font-weight:600;">'.($payLabels[$orderSuccess['payment']] ?? $orderSuccess['payment']).'</span>';
                            ?>
                        </div>
                    </div>

                    <!-- Info Pembayaran Cash -->
                    <div style="background:#FFF7ED;border:1.5px solid #FED7AA;border-radius:10px;padding:12px;margin-bottom:16px;font-size:12px;color:#92400E;">
                        <div style="font-weight:700;margin-bottom:3px;">💡 Informasi Pembayaran</div>
                        Pesanan Anda sudah tercatat. Pembayaran <strong>tunai (cash)</strong> dapat dilakukan langsung kepada staff kami saat pengambilan pakaian. Terima kasih telah bertransaksi di WashWell!
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <a href="orders.php" class="btn btn-ghost" style="text-align:center;">Lihat Pesanan</a>
                        <button onclick="document.getElementById('successBanner').remove();" class="btn btn-primary">Buat Pesanan Lagi</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">
                <!-- Form -->
                <div class="card">
                    <div class="card-header"><span class="card-title">Detail Pesanan</span></div>
                    <div class="card-body">
                        <form method="POST" id="orderForm">

                            <!-- Layanan -->
                            <div class="form-group">
                                <label class="form-label">Pilih Layanan *</label>
                                <div id="serviceCards" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;">
                                <?php $services->data_seek(0); while($s = $services->fetch_assoc()): ?>
                                <label class="service-option" data-id="<?= $s['id'] ?>" data-price="<?= $s['price_per_kg'] ?>" data-duration="<?= $s['duration_hours'] ?>" data-unit="<?= $s['unit_type'] ?? 'kg' ?>">
                                    <input type="radio" name="service_id" value="<?= $s['id'] ?>" style="display:none;" required>
                                    <div class="service-card-inner">
                                        <div style="font-weight:700;font-size:13px;color:var(--gray-800);margin-bottom:3px;"><?= htmlspecialchars($s['name']) ?></div>
                                        <div style="font-size:11px;color:var(--gray-500);margin-bottom:8px;"><?= htmlspecialchars($s['description']) ?></div>
                                        <div style="font-size:14px;font-weight:800;color:var(--primary);"><?= formatRupiah($s['price_per_kg']) ?>/<?= ($s['unit_type'] ?? 'kg') === 'item' ? 'pcs' : 'kg' ?></div>
                                        <div style="font-size:11px;color:var(--gray-400);">Estimasi <?= $s['duration_hours'] >= 24 ? ($s['duration_hours']/24) . ' hari' : $s['duration_hours'] . ' jam' ?></div>
                                    </div>
                                </label>
                                <?php endwhile; ?>
                                </div>
                            </div>

                            <!-- ITEM PAKAIAN SECTION -->
                            <div class="form-group">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                    <label class="form-label" style="margin:0;">Item Pakaian *</label>
                                    <button type="button" class="btn btn-ghost btn-sm" onclick="addItem()" style="font-size:12px;gap:4px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        Tambah Item
                                    </button>
                                </div>

                                <!-- Tipe Pakaian Chips -->
                                <div id="clothTypeRef" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;">
                                    <?php foreach($clothTypes as $ct): ?>
                                    <span style="background:var(--gray-100);padding:4px 10px;border-radius:20px;font-size:11px;font-weight:500;color:var(--gray-600);"><?= $ct['icon'].' '.$ct['label'] ?></span>
                                    <?php endforeach; ?>
                                </div>

                                <div id="itemList" style="display:flex;flex-direction:column;gap:10px;">
                                    <!-- Item pertama default -->
                                </div>
                                <div id="totalWeightInfo" style="margin-top:10px;text-align:right;font-size:13px;color:var(--gray-500);">Total berat: <strong id="totalWeightText">0 kg</strong></div>
                            </div>

                            <!-- Tanggal Pickup -->
                            <div class="form-group">
                                <label class="form-label">Tanggal Antar/Pickup *</label>
                                <input type="date" name="pickup_date" id="pickupDateInput" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required onchange="updateEstimasi()">
                            </div>
                            <!-- Estimasi selesai real-time -->
                            <div id="estimasiBox" style="display:none;background:#F0FDF4;border:1.5px solid #86EFAC;border-radius:10px;padding:12px 14px;margin-top:-8px;margin-bottom:16px;font-size:13px;color:#166534;">
                                <div style="font-weight:700;margin-bottom:2px;">📅 Estimasi Selesai</div>
                                <div id="estimasiTanggal" style="font-size:15px;font-weight:800;font-family:'Sora',sans-serif;"></div>
                                <div id="estimasiDurasi" style="font-size:11px;opacity:0.8;margin-top:2px;"></div>
                            </div>

                            <!-- Jenis Pengiriman -->
                            <div class="form-group">
                                <label class="form-label">Jenis Layanan Pengiriman *</label>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                    <label class="delivery-option active" id="opt-pickup">
                                        <input type="radio" name="delivery_type" value="pickup" checked onchange="toggleDelivery('pickup')">
                                        <div class="delivery-inner">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                            <div>
                                                <div style="font-weight:700;font-size:13px;">Antar Sendiri</div>
                                                <div style="font-size:11px;color:var(--gray-500);">Bawa ke laundry</div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="delivery-option" id="opt-delivery">
                                        <input type="radio" name="delivery_type" value="delivery" onchange="toggleDelivery('delivery')">
                                        <div class="delivery-inner">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                            <div>
                                                <div style="font-weight:700;font-size:13px;">Jemput & Antar</div>
                                                <div style="font-size:11px;color:var(--gray-500);">Kami yang datang</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Alamat -->
                            <div class="form-group" id="deliveryAddressGroup" style="display:none;">
                                <label class="form-label">Alamat Penjemputan / Pengantaran *</label>
                                <textarea name="delivery_address" class="form-control" rows="3" placeholder="Masukkan alamat lengkap..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>

                            <!-- Metode Bayar: TANPA CASH -->
                            <div class="form-group">
                                <label class="form-label">Metode Pembayaran *</label>
                                <div style="background:#EFF6FF;border:1.5px solid #BFDBFE;border-radius:10px;padding:12px;margin-bottom:10px;font-size:12px;color:#1D4ED8;display:flex;align-items:center;gap:8px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    Pembayaran <strong>tunai (cash)</strong> dilakukan langsung ke staff saat pengambilan. Pilih metode non-tunai jika ingin transfer terlebih dahulu.
                                </div>
                                <select name="payment_method" id="paymentSelect" class="form-control form-select" required>
                                    <option value="transfer">🏦 Transfer Bank</option>
                                    <option value="e-wallet">📱 E-Wallet (OVO/Dana/GoPay)</option>
                                </select>
                            </div>

                            <!-- Catatan -->
                            <div class="form-group">
                                <label class="form-label">Catatan Tambahan</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Misal: ada noda membandel di bagian kerah..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-full btn-lg">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                Buat Pesanan Sekarang
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Ringkasan -->
                <div style="position:sticky;top:90px;">
                    <div class="card">
                        <div class="card-header"><span class="card-title">Ringkasan Pesanan</span></div>
                        <div class="card-body">
                            <div style="background:var(--gray-50);border-radius:10px;padding:16px;margin-bottom:16px;">
                                <div id="summaryService" style="font-size:13px;color:var(--gray-500);margin-bottom:4px;">Pilih layanan terlebih dahulu</div>
                                <div id="summaryPrice" style="font-size:13px;color:var(--gray-600);"></div>
                            </div>

                            <!-- Item Summary -->
                            <div id="itemSummaryList" style="margin-bottom:12px;display:flex;flex-direction:column;gap:5px;"></div>

                            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--gray-100);">
                                <span style="font-size:13px;color:var(--gray-500);">Harga per kg</span>
                                <span id="pricePerKg" style="font-size:13px;font-weight:600;">-</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--gray-100);">
                                <span style="font-size:13px;color:var(--gray-500);">Total Berat</span>
                                <span id="weightDisplay" style="font-size:13px;font-weight:600;">0 kg</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;margin-top:4px;">
                                <span style="font-size:14px;font-weight:700;">Total</span>
                                <span id="totalDisplay" style="font-size:20px;font-weight:800;font-family:'Sora',sans-serif;color:var(--primary);">Rp 0</span>
                            </div>
                        </div>
                    </div>
                    <div class="card" style="margin-top:14px;">
                        <div class="card-body">
                            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--gray-500);margin-bottom:10px;">Info Penting</div>
                            <ul style="font-size:12px;color:var(--gray-600);line-height:2;padding-left:16px;">
                                <li>Berat minimal 0.5 kg per item</li>
                                <li>Cash dibayar langsung ke staff</li>
                                <li>Konfirmasi via notifikasi</li>
                                <li>Hubungi kami jika ada masalah</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Template item (hidden) -->
<template id="itemTemplate">
    <div class="item-row" style="background:var(--gray-50);border:1.5px solid var(--gray-200);border-radius:12px;padding:14px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <span style="font-size:12px;font-weight:700;color:var(--gray-500);">ITEM #<span class="item-num">1</span></span>
            <button type="button" onclick="removeItem(this)" class="btn btn-ghost btn-sm" style="font-size:11px;color:#EF4444;padding:2px 8px;">✕ Hapus</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <!-- Jenis Pakaian — hanya muncul untuk unit_type='kg' -->
            <div class="cloth-type-field">
                <label style="font-size:11px;font-weight:600;color:var(--gray-600);margin-bottom:4px;display:block;">Jenis Pakaian *</label>
                <select name="item_type[]" class="form-control form-select item-type-sel" required onchange="updateSummary()">
                    <option value="">-- Pilih Jenis --</option>
                    <?php foreach($clothTypes as $ct): ?>
                    <option value="<?= $ct['id'] ?>"><?= $ct['icon'].' '.$ct['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:var(--gray-600);margin-bottom:4px;display:block;" class="weight-label">Berat (kg) *</label>
                <div style="display:flex;align-items:center;gap:6px;">
                    <input type="number" name="item_weight[]" class="form-control item-weight-inp" min="0.5" max="50" step="0.5" value="1" required oninput="updateSummary()">
                    <span class="weight-unit-label" style="font-size:12px;font-weight:600;color:var(--gray-500);white-space:nowrap;">kg</span>
                </div>
            </div>
        </div>
        <div>
            <label style="font-size:11px;font-weight:600;color:var(--gray-600);margin-bottom:4px;display:block;">Catatan Item (opsional)</label>
            <input type="text" name="item_note[]" class="form-control" placeholder="Misal: ada noda, jangan diperas...">
        </div>
    </div>
</template>

<style>
.service-option input[type=radio]{display:none}
.service-card-inner{border:2px solid var(--gray-200);border-radius:12px;padding:14px;cursor:pointer;transition:all 0.2s;}
.service-option.selected .service-card-inner{border-color:var(--primary);background:var(--primary-bg);}
.service-card-inner:hover{border-color:var(--primary);background:var(--primary-bg);}
.delivery-option input[type=radio]{display:none}
.delivery-inner{border:2px solid var(--gray-200);border-radius:10px;padding:12px;cursor:pointer;display:flex;align-items:center;gap:10px;transition:all 0.2s;}
.delivery-option.active .delivery-inner{border-color:var(--primary);background:var(--primary-bg);}
.delivery-inner:hover{border-color:var(--primary);}
.cloth-type-field{transition:all 0.2s ease;}
.cloth-type-field.hidden{display:none!important;}
@media(max-width:900px){.main-content div[style*="grid-template-columns:1fr 320px"]{grid-template-columns:1fr!important;}}
</style>

<script>
let selectedPrice    = 0;
let selectedUnit     = 'kg';      // 'kg' atau 'item'
let selectedDuration = 0;         // jam
let itemCount        = 0;

const clothIcons = {
    reguler:'👕',katun:'🧥',jas:'🥼',sutra:'✨',jeans:'👖',batik:'🎨',wool:'🧶',lainnya:'🧺'
};

// ── Tambah item ──────────────────────────────────────────────
function addItem(defaultType='') {
    itemCount++;
    const tpl = document.getElementById('itemTemplate').content.cloneNode(true);
    tpl.querySelector('.item-num').textContent = itemCount;
    if (defaultType) tpl.querySelector('.item-type-sel').value = defaultType;
    document.getElementById('itemList').appendChild(tpl);
    updateItemFields();   // terapkan state unit saat ini ke item baru
    updateSummary();
}

function removeItem(btn) {
    btn.closest('.item-row').remove();
    document.querySelectorAll('.item-num').forEach((el,i)=>el.textContent=i+1);
    updateSummary();
}

// ── Update tampilan field berdasarkan unit (kg vs item) ──────
function updateItemFields() {
    const isItem = selectedUnit === 'item';

    document.querySelectorAll('.cloth-type-field').forEach(el => {
        el.classList.toggle('hidden', isItem);
        const sel = el.querySelector('select');
        if (sel) {
            sel.required = !isItem;
            if (isItem) sel.value = 'lainnya';   // nilai default untuk satuan
        }
    });

    document.querySelectorAll('.weight-label').forEach(el => {
        el.textContent = isItem ? 'Jumlah (pcs/pasang) *' : 'Berat (kg) *';
    });

    document.querySelectorAll('.item-weight-inp').forEach(el => {
        el.step  = isItem ? '1'   : '0.5';
        el.min   = isItem ? '1'   : '0.5';
        el.value = '1';
    });

    document.querySelectorAll('.weight-unit-label').forEach(el => {
        el.textContent = isItem ? 'pcs' : 'kg';
    });

    // Update label harga ringkasan
    document.getElementById('pricePerKg').textContent = selectedPrice
        ? 'Rp ' + Number(selectedPrice).toLocaleString('id-ID') + '/' + (isItem ? 'pcs' : 'kg')
        : '-';
}

// ── Estimasi tanggal selesai ─────────────────────────────────
function updateEstimasi() {
    if (!selectedDuration) return;
    const pickupVal = document.getElementById('pickupDateInput').value;
    if (!pickupVal) return;
    const pickupDate = new Date(pickupVal + 'T00:00:00');
    const selesai    = new Date(pickupDate.getTime() + selectedDuration * 3600 * 1000);
    const hari   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const bulan  = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const label  = hari[selesai.getDay()] + ', ' + selesai.getDate() + ' ' + bulan[selesai.getMonth()] + ' ' + selesai.getFullYear();
    const durLabel = selectedDuration >= 24
        ? Math.round(selectedDuration / 24) + ' hari pengerjaan'
        : selectedDuration + ' jam pengerjaan';
    document.getElementById('estimasiTanggal').textContent = label;
    document.getElementById('estimasiDurasi').textContent  = durLabel;
    document.getElementById('estimasiBox').style.display   = 'block';
}

// ── Update ringkasan harga ───────────────────────────────────
function updateSummary() {
    let totalW = 0;
    const summaryList = document.getElementById('itemSummaryList');
    summaryList.innerHTML = '';
    const isItem = selectedUnit === 'item';

    document.querySelectorAll('.item-row').forEach(row => {
        const type = row.querySelector('.item-type-sel').value;
        const w    = parseFloat(row.querySelector('.item-weight-inp').value) || 0;
        totalW += w;
        if (w > 0) {
            const icon  = clothIcons[type] || '📦';
            const label = isItem ? type || 'Item' : (type || 'Item');
            const unit  = isItem ? 'pcs' : 'kg';
            summaryList.innerHTML += `<div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;border-bottom:1px dashed var(--gray-100);">
                <span style="color:var(--gray-700);">${icon} ${label.charAt(0).toUpperCase()+label.slice(1)}</span>
                <span style="font-weight:600;">${w} ${unit}</span>
            </div>`;
        }
    });

    const unitLabel = isItem ? 'pcs' : 'kg';
    document.getElementById('weightDisplay').textContent   = totalW.toFixed(isItem?0:1) + ' ' + unitLabel;
    document.getElementById('totalWeightText').textContent = totalW.toFixed(isItem?0:1) + ' ' + unitLabel;
    const total = selectedPrice * totalW;
    document.getElementById('totalDisplay').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
}

// ── Klik layanan ─────────────────────────────────────────────
document.querySelectorAll('.service-option').forEach(opt => {
    opt.addEventListener('click', function() {
        document.querySelectorAll('.service-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input').checked = true;

        selectedPrice    = parseFloat(this.dataset.price);
        selectedUnit     = this.dataset.unit || 'kg';
        selectedDuration = parseInt(this.dataset.duration) || 0;

        const svcName = this.querySelector('.service-card-inner div').textContent;
        document.getElementById('summaryService').textContent = svcName;

        const unitLabel = selectedUnit === 'item' ? 'pcs' : 'kg';
        document.getElementById('summaryPrice').textContent = 'Rp ' + Number(selectedPrice).toLocaleString('id-ID') + '/' + unitLabel;

        updateItemFields();
        updateEstimasi();
        updateSummary();
    });
});

// ── Toggle delivery + COD ────────────────────────────────────
function toggleDelivery(type) {
    document.getElementById('opt-pickup').classList.toggle('active', type === 'pickup');
    document.getElementById('opt-delivery').classList.toggle('active', type === 'delivery');
    document.getElementById('deliveryAddressGroup').style.display = type === 'delivery' ? 'block' : 'none';

    const paySelect = document.getElementById('paymentSelect');
    if (type === 'delivery') {
        if (!paySelect.querySelector('option[value="cod"]')) {
            const opt = document.createElement('option');
            opt.value = 'cod';
            opt.textContent = '🚗 COD — Bayar saat diantar';
            paySelect.appendChild(opt);
        }
        paySelect.value = 'cod';
    } else {
        const codOpt = paySelect.querySelector('option[value="cod"]');
        if (codOpt) codOpt.remove();
        if (paySelect.value === 'cod') paySelect.value = 'transfer';
    }
}

function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('open');}

// Init: 1 item default
addItem('reguler');
</script>
</body>
</html>
