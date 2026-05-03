<?php
require_once '../includes/auth.php';
requireRole('customer', '../pages/login.php');
$db = getDB();
$user = getCurrentUser();
$uid = (int)$user['id'];

$msg = ''; $msgType = '';
$selectedOrder = null;

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='pay') {
    $oid = (int)$_POST['order_id'];
    $method = $_POST['payment_method'];
    $order = $db->query("SELECT o.*,s.name as service_name FROM orders o JOIN services s ON o.service_id=s.id WHERE o.id=$oid AND o.user_id=$uid AND o.payment_status='unpaid'")->fetch_assoc();
    if ($order) {
        $trxCode = generateTrxCode();
        $stmt = $db->prepare("INSERT INTO transactions (order_id,transaction_code,amount,payment_method,status) VALUES (?,?,?,?,'success')");
        $stmt->bind_param('isds', $oid, $trxCode, $order['amount'], $method);
        if ($stmt->execute()) {
            $db->query("UPDATE orders SET payment_status='paid', payment_method='".addslashes($method)."' WHERE id=$oid AND user_id=$uid");
            $db->query("INSERT INTO notifications (user_id,title,message) VALUES ($uid,'Pembayaran Berhasil','Pembayaran untuk ".$order['order_code']." sebesar ".formatRupiah($order['amount'])." berhasil dikonfirmasi.')");
            $msg = "Pembayaran berhasil! Kode transaksi: <strong>$trxCode</strong>"; $msgType = 'success';
        } else {
            $msg = 'Gagal memproses pembayaran.'; $msgType = 'danger';
        }
    } else {
        $msg = 'Pesanan tidak ditemukan atau sudah dibayar.'; $msgType = 'danger';
    }
}

// Pre-select order from URL
if (isset($_GET['order_id'])) {
    $oid = (int)$_GET['order_id'];
    $selectedOrder = $db->query("SELECT o.*,s.name as service_name FROM orders o JOIN services s ON o.service_id=s.id WHERE o.id=$oid AND o.user_id=$uid")->fetch_assoc();
}

// All unpaid orders
$unpaidOrders = $db->query("SELECT o.*,s.name as service_name FROM orders o JOIN services s ON o.service_id=s.id WHERE o.user_id=$uid AND o.payment_status='unpaid' AND o.status!='cancelled' ORDER BY o.created_at DESC");

// Transaction history
$transactions = $db->query("SELECT t.*,o.order_code,s.name as service_name FROM transactions t JOIN orders o ON t.order_id=o.id JOIN services s ON o.service_id=s.id WHERE o.user_id=$uid ORDER BY t.created_at DESC LIMIT 20");

$notifCount = getUnreadNotifs($uid);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pembayaran - WashWell</title>
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
                <div class="topbar-title">Pembayaran</div>
            </div>
            <div class="topbar-right">
                <a href="notifications.php" class="notif-btn" style="position:relative;display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:9px;background:var(--gray-100);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if ($notifCount > 0): ?><span style="position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:#EF4444;border:2px solid white;"></span><?php endif; ?>
                </a>
            </div>
        </header>
        <main class="page-content">
            <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?>"><?= $msgType==='success'?'✅':'⚠️' ?> <?= $msg ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <!-- Tagihan Belum Bayar -->
                <div class="card">
                    <div class="card-header"><span class="card-title">Tagihan Belum Dibayar</span></div>
                    <div class="card-body" style="padding-top:12px;">
                        <?php if ($unpaidOrders->num_rows === 0): ?>
                        <div style="text-align:center;padding:40px 0;color:var(--gray-400);">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:10px;opacity:0.4;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <p style="font-size:13px;">Semua tagihan sudah lunas!</p>
                        </div>
                        <?php else: ?>
                        <?php while($o = $unpaidOrders->fetch_assoc()): ?>
                        <div class="pay-card <?= ($selectedOrder && $selectedOrder['id']==$o['id']) ? 'selected' : '' ?>" style="border:2px solid var(--gray-200);border-radius:12px;padding:16px;margin-bottom:12px;">
                            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:10px;">
                                <div>
                                    <div style="font-weight:700;color:var(--primary)"><?= htmlspecialchars($o['order_code']) ?></div>
                                    <div style="font-size:12px;color:var(--gray-500);"><?= htmlspecialchars($o['service_name']) ?> · <?= $o['weight'] ?> kg</div>
                                </div>
                                <span class="badge badge-<?= $o['status'] ?>"><?= ['pending'=>'Menunggu','in_progress'=>'Diproses','ready'=>'Siap'][($o['status'])] ?? $o['status'] ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <div style="font-size:20px;font-weight:800;font-family:'Sora',sans-serif;color:var(--gray-800);"><?= formatRupiah($o['amount']) ?></div>
                                <button class="btn btn-orange btn-sm" onclick="openPayModal(<?= $o['id'] ?>,'<?= htmlspecialchars($o['order_code']) ?>','<?= htmlspecialchars($o['service_name']) ?>',<?= $o['amount'] ?>,'<?= $o['payment_method'] ?>')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                    Bayar Sekarang
                                </button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Riwayat Transaksi -->
                <div class="card">
                    <div class="card-header"><span class="card-title">Riwayat Transaksi</span></div>
                    <div class="card-body" style="padding-top:12px;">
                        <?php if ($transactions->num_rows === 0): ?>
                        <div style="text-align:center;padding:40px 0;color:var(--gray-400);font-size:13px;">Belum ada transaksi</div>
                        <?php else: ?>
                        <?php while($t = $transactions->fetch_assoc()): ?>
                        <div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--gray-100);">
                            <div style="width:38px;height:38px;border-radius:10px;background:<?= $t['status']==='success'?'var(--green-light)':'#FEE2E2' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <?php if($t['status']==='success'): ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                <?php else: ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($t['order_code']) ?> - <?= htmlspecialchars($t['service_name']) ?></div>
                                <div style="font-size:11px;color:var(--gray-500);"><?= htmlspecialchars($t['transaction_code']) ?> · <?= ucfirst($t['payment_method']) ?> · <?= date('d M Y', strtotime($t['created_at'])) ?></div>
                            </div>
                            <div style="font-weight:700;font-size:14px;color:<?= $t['status']==='success'?'var(--green)':'#EF4444' ?>"><?= formatRupiah($t['amount']) ?></div>
                        </div>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Pembayaran -->
<div id="payModal" class="modal-overlay" style="display:none;">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header">
            <h3 class="modal-title">Konfirmasi Pembayaran</h3>
            <button class="modal-close" onclick="closePayModal()">✕</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="pay">
                <input type="hidden" name="order_id" id="modalOrderId">
                <div style="background:var(--gray-50);border-radius:12px;padding:16px;margin-bottom:16px;">
                    <div style="font-size:12px;color:var(--gray-500);margin-bottom:4px;">Pesanan</div>
                    <div style="font-weight:700;color:var(--primary)" id="modalOrderCode"></div>
                    <div style="font-size:13px;color:var(--gray-600)" id="modalServiceName"></div>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-100);">
                    <span style="font-size:13px;color:var(--gray-500);">Total Tagihan</span>
                    <span style="font-size:18px;font-weight:800;font-family:'Sora',sans-serif;color:var(--primary)" id="modalAmount"></span>
                </div>
                <div class="form-group" style="margin-top:16px;">
                    <label class="form-label">Metode Pembayaran</label>
                    <div style="display:grid;gap:8px;">
                        <label class="pay-method-opt">
                            <input type="radio" name="payment_method" value="cash" checked>
                            <span class="pay-method-inner">💵 Tunai (Cash)</span>
                        </label>
                        <label class="pay-method-opt">
                            <input type="radio" name="payment_method" value="transfer">
                            <span class="pay-method-inner">🏦 Transfer Bank</span>
                        </label>
                        <label class="pay-method-opt">
                            <input type="radio" name="payment_method" value="e-wallet">
                            <span class="pay-method-inner">📱 E-Wallet (OVO/Dana/GoPay)</span>
                        </label>
                    </div>
                </div>
                <div id="transferInfo" style="display:none;background:#EFF6FF;border-radius:10px;padding:14px;font-size:13px;color:var(--gray-700);">
                    <div style="font-weight:700;margin-bottom:6px;">Info Rekening:</div>
                    <div>BCA: <strong>1234 5678 9012</strong> a.n. WashWell Laundry</div>
                    <div>Mandiri: <strong>9876 5432 1098</strong> a.n. WashWell Laundry</div>
                    <div style="margin-top:6px;font-size:12px;color:var(--gray-500);">Kirim bukti transfer ke WhatsApp kami.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closePayModal()">Batal</button>
                <button type="submit" class="btn btn-orange">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Konfirmasi Bayar
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.pay-method-opt input{display:none}
.pay-method-inner{display:block;padding:10px 14px;border:1.5px solid var(--gray-200);border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;transition:all 0.2s;}
.pay-method-opt input:checked + .pay-method-inner{border-color:var(--orange);background:#FFF7ED;color:var(--orange);}
@media(max-width:800px){.main-content div[style*="grid-template-columns:1fr 1fr"]{grid-template-columns:1fr!important;}}
</style>
<script>
function openPayModal(id,code,service,amount,method){
    document.getElementById('modalOrderId').value=id;
    document.getElementById('modalOrderCode').textContent=code;
    document.getElementById('modalServiceName').textContent=service;
    document.getElementById('modalAmount').textContent='Rp '+Math.round(amount).toLocaleString('id-ID');
    document.getElementById('payModal').style.display='flex';
    // Set default method
    document.querySelectorAll('[name=payment_method]').forEach(r=>{r.checked=(r.value===method);});
}
function closePayModal(){document.getElementById('payModal').style.display='none';}
document.querySelectorAll('[name=payment_method]').forEach(r=>{
    r.addEventListener('change',function(){
        document.getElementById('transferInfo').style.display=(this.value==='transfer')?'block':'none';
    });
});
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('open');}
<?php if(isset($_GET['order_id']) && $selectedOrder): ?>
window.onload=function(){openPayModal(<?= $selectedOrder['id'] ?>,'<?= htmlspecialchars($selectedOrder['order_code']) ?>','<?= htmlspecialchars($selectedOrder['service_name']) ?>',<?= $selectedOrder['amount'] ?>,'<?= $selectedOrder['payment_method'] ?>');}
<?php endif; ?>
</script>
</body>
</html>
