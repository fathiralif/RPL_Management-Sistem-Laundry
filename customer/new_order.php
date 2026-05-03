<?php
require_once '../includes/auth.php';
requireRole('customer', '../pages/login.php');
$db = getDB();
$user = getCurrentUser();
$uid = (int)$user['id'];

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid      = (int)$_POST['service_id'];
    $weight   = max(0.5, (float)$_POST['weight']);
    $pickup   = trim($_POST['pickup_date']);
    $notes    = trim($_POST['notes'] ?? '');
    $payment  = $_POST['payment_method'];
    $delivery_type = $_POST['delivery_type'] ?? 'pickup'; // pickup or delivery
    $address  = trim($_POST['delivery_address'] ?? $user['address'] ?? '');

    if (!$sid || !$pickup) {
        $msg = 'Mohon lengkapi semua data yang diperlukan.'; $msgType = 'danger';
    } else {
        $svc = $db->query("SELECT * FROM services WHERE id=$sid AND is_active=1")->fetch_assoc();
        if (!$svc) {
            $msg = 'Layanan tidak ditemukan.'; $msgType = 'danger';
        } else {
            $amount = $svc['price_per_kg'] * $weight;
            $duration = (int)$svc['duration_hours'];
            $delivery_date = date('Y-m-d', strtotime($pickup . ' + ' . $duration . ' hours'));
            $code = generateOrderCode();

            // Include delivery info in notes if delivery chosen
            $fullNotes = $notes;
            if ($delivery_type === 'delivery') {
                $fullNotes = "[ANTAR KE: " . $address . "] " . $notes;
            }

            $stmt = $db->prepare("INSERT INTO orders (order_code,user_id,service_id,weight,amount,pickup_date,delivery_date,notes,payment_method) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('siiddssss', $code,$uid,$sid,$weight,$amount,$pickup,$delivery_date,$fullNotes,$payment);
            if ($stmt->execute()) {
                $orderId = $stmt->insert_id;
                // Notification to customer
                $db->query("INSERT INTO notifications (user_id,title,message) VALUES ($uid,'Pesanan Berhasil Dibuat','Pesanan $code telah diterima dan sedang menunggu konfirmasi.')");
                // Notification to admin (user id=1)
                $custName = $db->real_escape_string($user['name']);
                $db->query("INSERT INTO notifications (user_id,title,message) VALUES (1,'Pesanan Baru','Ada pesanan baru dari $custName - $code')");
                $msg = "Pesanan <strong>$code</strong> berhasil dibuat! Total: <strong>" . formatRupiah($amount) . "</strong>"; $msgType = 'success';
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
            <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?>"><?= $msgType==='success'?'✅':'⚠️' ?> <?= $msg ?> <?php if($msgType==='success'): ?><a href="orders.php" style="margin-left:8px;font-weight:700;color:inherit;text-decoration:underline;">Lihat Pesanan →</a><?php endif; ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">
                <!-- Form -->
                <div class="card">
                    <div class="card-header"><span class="card-title">Detail Pesanan</span></div>
                    <div class="card-body">
                        <form method="POST">
                            <!-- Layanan -->
                            <div class="form-group">
                                <label class="form-label">Pilih Layanan *</label>
                                <div id="serviceCards" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;">
                                <?php while($s = $services->fetch_assoc()): ?>
                                <label class="service-option" data-id="<?= $s['id'] ?>" data-price="<?= $s['price_per_kg'] ?>" data-duration="<?= $s['duration_hours'] ?>">
                                    <input type="radio" name="service_id" value="<?= $s['id'] ?>" style="display:none;" required>
                                    <div class="service-card-inner">
                                        <div style="font-weight:700;font-size:13px;color:var(--gray-800);margin-bottom:3px;"><?= htmlspecialchars($s['name']) ?></div>
                                        <div style="font-size:11px;color:var(--gray-500);margin-bottom:8px;"><?= htmlspecialchars($s['description']) ?></div>
                                        <div style="font-size:14px;font-weight:800;color:var(--primary);"><?= formatRupiah($s['price_per_kg']) ?>/kg</div>
                                        <div style="font-size:11px;color:var(--gray-400);">Estimasi <?= $s['duration_hours'] >= 24 ? ($s['duration_hours']/24) . ' hari' : $s['duration_hours'] . ' jam' ?></div>
                                    </div>
                                </label>
                                <?php endwhile; ?>
                                </div>
                            </div>

                            <!-- Berat -->
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                <div class="form-group">
                                    <label class="form-label">Berat Cucian (kg) *</label>
                                    <input type="number" name="weight" id="weightInput" class="form-control" min="0.5" max="50" step="0.5" value="1" required oninput="calcTotal()">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tanggal Antar/Pickup *</label>
                                    <input type="date" name="pickup_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                                </div>
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

                            <!-- Alamat (muncul jika delivery) -->
                            <div class="form-group" id="deliveryAddressGroup" style="display:none;">
                                <label class="form-label">Alamat Penjemputan / Pengantaran *</label>
                                <textarea name="delivery_address" class="form-control" rows="3" placeholder="Masukkan alamat lengkap..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>

                            <!-- Metode Bayar -->
                            <div class="form-group">
                                <label class="form-label">Metode Pembayaran *</label>
                                <select name="payment_method" class="form-control form-select" required>
                                    <option value="cash">💵 Tunai (Cash)</option>
                                    <option value="transfer">🏦 Transfer Bank</option>
                                    <option value="e-wallet">📱 E-Wallet (OVO/Dana/GoPay)</option>
                                </select>
                            </div>

                            <!-- Catatan -->
                            <div class="form-group">
                                <label class="form-label">Catatan Tambahan</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: Ada pakaian berbahan sutra, jangan diperas..."></textarea>
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
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--gray-100);">
                                <span style="font-size:13px;color:var(--gray-500);">Harga per kg</span>
                                <span id="pricePerKg" style="font-size:13px;font-weight:600;">-</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--gray-100);">
                                <span style="font-size:13px;color:var(--gray-500);">Berat</span>
                                <span id="weightDisplay" style="font-size:13px;font-weight:600;">1 kg</span>
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
                                <li>Berat minimal 0.5 kg</li>
                                <li>Pembayaran saat pengambilan</li>
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

<style>
.service-option input[type=radio]{display:none}
.service-card-inner{border:2px solid var(--gray-200);border-radius:12px;padding:14px;cursor:pointer;transition:all 0.2s;}
.service-option.selected .service-card-inner{border-color:var(--primary);background:var(--primary-bg);}
.service-card-inner:hover{border-color:var(--primary);background:var(--primary-bg);}
.delivery-option input[type=radio]{display:none}
.delivery-inner{border:2px solid var(--gray-200);border-radius:10px;padding:12px;cursor:pointer;display:flex;align-items:center;gap:10px;transition:all 0.2s;}
.delivery-option.active .delivery-inner{border-color:var(--primary);background:var(--primary-bg);}
.delivery-inner:hover{border-color:var(--primary);}
@media(max-width:900px){.main-content div[style*="grid-template-columns:1fr 320px"]{grid-template-columns:1fr!important;}}
</style>
<script>
let selectedPrice = 0;
document.querySelectorAll('.service-option').forEach(opt => {
    opt.addEventListener('click', function(){
        document.querySelectorAll('.service-option').forEach(o=>o.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input').checked = true;
        selectedPrice = parseFloat(this.dataset.price);
        document.getElementById('summaryService').textContent = this.querySelector('.service-card-inner div').textContent;
        document.getElementById('summaryPrice').textContent = 'Rp ' + Number(selectedPrice).toLocaleString('id-ID') + '/kg';
        document.getElementById('pricePerKg').textContent = 'Rp ' + Number(selectedPrice).toLocaleString('id-ID');
        calcTotal();
    });
});
function calcTotal(){
    const w = parseFloat(document.getElementById('weightInput').value)||0;
    document.getElementById('weightDisplay').textContent = w + ' kg';
    const total = selectedPrice * w;
    document.getElementById('totalDisplay').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
}
function toggleDelivery(type){
    document.getElementById('opt-pickup').classList.toggle('active', type==='pickup');
    document.getElementById('opt-delivery').classList.toggle('active', type==='delivery');
    document.getElementById('deliveryAddressGroup').style.display = type==='delivery'?'block':'none';
}
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('open');}
</script>
</body>
</html>
