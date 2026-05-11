<?php
require_once '../includes/auth.php';
requireRole('customer', '../pages/login.php');
$db = getDB();
$user = getCurrentUser();
$uid = (int)$user['id'];

// Handle cancel
$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='cancel') {
    $oid = (int)$_POST['order_id'];
    $order = $db->query("SELECT * FROM orders WHERE id=$oid AND user_id=$uid")->fetch_assoc();
    if ($order && $order['status']==='pending') {
        $db->query("UPDATE orders SET status='cancelled' WHERE id=$oid AND user_id=$uid");
        $db->query("INSERT INTO notifications (user_id,title,message) VALUES ($uid,'Pesanan Dibatalkan','Pesanan ".$order['order_code']." telah dibatalkan.')");
        $msg = 'Pesanan berhasil dibatalkan.'; $msgType = 'success';
    } else {
        $msg = 'Pesanan tidak dapat dibatalkan (status bukan pending).'; $msgType = 'danger';
    }
}

$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$where = ["o.user_id=$uid"];
if ($statusFilter) $where[] = "o.status='".addslashes($statusFilter)."'";
if ($search) $where[] = "(o.order_code LIKE '%".addslashes($search)."%' OR s.name LIKE '%".addslashes($search)."%')";
$whereStr = implode(' AND ', $where);

$perPage = 10;
$page = max(1,(int)($_GET['page']??1));
$offset = ($page-1)*$perPage;
$total = $db->query("SELECT COUNT(*) as c FROM orders o JOIN services s ON o.service_id=s.id WHERE $whereStr")->fetch_assoc()['c'];
$pages = ceil($total/$perPage);

$orders = $db->query("SELECT o.*,s.name as service_name,s.price_per_kg FROM orders o JOIN services s ON o.service_id=s.id WHERE $whereStr ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset");
$notifCount = getUnreadNotifs($uid);
$statusLabels = [
    // Status baru (digunakan untuk pesanan baru)
    'pending'       => 'Menunggu',
    'washing'       => 'Sedang Dicuci 🧼',
    'drying'        => 'Pengeringan 💨',
    'ironing'       => 'Setrika 🔥',
    'ready_pickup'  => 'Siap Diambil ✅',
    'ready_deliver' => 'Siap Diantar 🚚',
    'done'          => 'Selesai 🎉',
    'cancelled'     => 'Dibatalkan ❌',
    // Status lama (hanya untuk tampilan data lama di DB, tidak tampil di filter)
    'in_progress'   => 'Dalam Proses',
    'ready'         => 'Siap',
    'delivered'     => 'Selesai Diantar',
];
// Status yang tampil di dropdown filter (hanya status baru)
$filterStatusLabels = [
    'pending'       => 'Menunggu',
    'washing'       => 'Sedang Dicuci 🧼',
    'drying'        => 'Pengeringan 💨',
    'ironing'       => 'Setrika 🔥',
    'ready_pickup'  => 'Siap Diambil ✅',
    'ready_deliver' => 'Siap Diantar 🚚',
    'done'          => 'Selesai 🎉',
    'cancelled'     => 'Dibatalkan ❌',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan Saya - WashWell</title>
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
                <div class="topbar-title">Pesanan Saya</div>
            </div>
            <div class="topbar-right">
                <a href="notifications.php" class="notif-btn" style="position:relative;display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:9px;background:var(--gray-100);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if ($notifCount > 0): ?><span style="position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:#EF4444;border:2px solid white;"></span><?php endif; ?>
                </a>
                <a href="new_order.php" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Pesanan Baru
                </a>
            </div>
        </header>
        <main class="page-content">
            <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?>"><?= $msgType==='success'?'✅':'⚠️' ?> <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <div class="card" style="margin-bottom:16px;">
                <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                    <form method="GET" style="display:flex;gap:10px;flex:1;flex-wrap:wrap;">
                        <div class="input-group" style="flex:1;min-width:200px;">
                            <span class="input-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
                            <input type="text" name="search" class="form-control" placeholder="Cari kode pesanan atau layanan..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <select name="status" class="form-control form-select" style="width:170px;">
                            <option value="">Semua Status</option>
                            <?php foreach($filterStatusLabels as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= $statusFilter===$k?'selected':'' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
                        <?php if ($search||$statusFilter): ?><a href="orders.php" class="btn btn-ghost btn-sm">Reset</a><?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Daftar Pesanan <span style="font-size:12px;font-weight:500;color:var(--gray-400);">(<?= $total ?> pesanan)</span></span>
                </div>
                <div class="card-body" style="padding-top:12px;">
                <?php if ($orders->num_rows === 0): ?>
                <div style="text-align:center;padding:60px 0;color:var(--gray-400);">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:0.4;"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    <p style="font-size:14px;font-weight:600;margin-bottom:6px;">Tidak ada pesanan ditemukan</p>
                    <p style="font-size:13px;">Mulai buat pesanan laundry sekarang</p>
                    <a href="new_order.php" class="btn btn-primary" style="margin-top:16px;">Buat Pesanan</a>
                </div>
                <?php else: ?>
                <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Kode Pesanan</th>
                            <th>Layanan</th>
                            <th>Detail</th>
                            <th>Tgl Pickup</th>
                            <th>Tgl Selesai</th>
                            <th>Status</th>
                            <th>Pembayaran</th>
                            <th>Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($o = $orders->fetch_assoc()):
                        $orderJson = json_encode([
                            'code'           => $o['order_code'],
                            'service'        => $o['service_name'],
                            'weight'         => number_format((float)$o['weight'], 1),
                            'amount'         => formatRupiah($o['amount']),
                            'payment'        => $o['payment_method'],
                            'payment_status' => $o['payment_status'],
                            'created'        => date('d/m/Y', strtotime($o['created_at'])),
                            'delivery'       => $o['delivery_date'] ? date('d/m/Y', strtotime($o['delivery_date'])) : '-',
                            'status'         => $o['status'],
                        ], JSON_HEX_APOS | JSON_HEX_QUOT);
                    ?>
                    <tr data-oid="<?= $o['id'] ?>" data-order='<?= $orderJson ?>'>
                        <td>
                            <span style="font-weight:700;color:var(--primary)"><?= htmlspecialchars($o['order_code']) ?></span><br>
                            <small style="color:var(--gray-400)"><?= date('d M Y', strtotime($o['created_at'])) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($o['service_name']) ?>
                            <div style="display:inline-flex;align-items:center;gap:4px;background:var(--primary-bg);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;color:var(--primary);margin-top:3px;">
                                <?= number_format((float)$o['weight'], 1) ?> kg
                            </div>
                        </td>
                        <td>
                            <span style="font-size:13px;"><?= $o['weight'] ?> kg</span><br>
                            <small style="color:var(--gray-500)"><?= ucfirst($o['payment_method']) ?></small>
                        </td>
                        <td><?= $o['pickup_date'] ? date('d M Y', strtotime($o['pickup_date'])) : '-' ?></td>
                        <td><?= $o['delivery_date'] ? date('d M Y', strtotime($o['delivery_date'])) : '-' ?></td>
                        <td><span class="badge badge-<?= $o['status'] ?>"><?= $statusLabels[$o['status']] ?? $o['status'] ?></span></td>
                        <td>
                            <?php if ($o['payment_status'] === 'paid'): ?>
                            <span style="display:inline-flex;align-items:center;gap:5px;background:var(--green-light);color:#166534;border:1.5px solid #86EFAC;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;">
                                ✅ LUNAS
                            </span>
                            <?php elseif ($o['payment_method'] === 'cod'): ?>
                            <span style="display:inline-flex;align-items:center;gap:5px;background:var(--orange-light);color:#C2410C;border:1.5px solid #FED7AA;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;">
                                🚗 COD
                            </span>
                            <?php else: ?>
                            <span style="display:inline-flex;align-items:center;gap:5px;background:#FEE2E2;color:#991B1B;border:1.5px solid #FECACA;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;">
                                ⏳ BELUM BAYAR
                            </span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:700;"><?= formatRupiah($o['amount']) ?></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <a href="tracking.php?order=<?= urlencode($o['order_code']) ?>" class="btn btn-ghost btn-sm">Lacak</a>
                                <?php if($o['status']==='pending'): ?>
                                <form method="POST" onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?')">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Batal</button>
                                </form>
                                <?php endif; ?>
                                <?php if($o['payment_status']==='unpaid' && $o['payment_method']!=='cod' && $o['status']!=='cancelled'): ?>
                                <a href="payments.php?order_id=<?= $o['id'] ?>" class="btn btn-orange btn-sm">Bayar</a>
                                <?php endif; ?>
                                <?php if($o['payment_status']==='paid' || $o['status']==='done'): ?>
                                <button onclick="openResi(<?= $o['id'] ?>)" class="btn btn-ghost btn-sm" style="font-size:11.5px;">
                                    🧾 Resi
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
                <?php if ($pages > 1): ?>
                <div style="display:flex;gap:8px;justify-content:center;margin-top:20px;">
                    <?php for($i=1;$i<=$pages;$i++): ?>
                    <a href="?page=<?= $i ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>" class="btn <?= $i===$page?'btn-primary':'btn-ghost' ?> btn-sm"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<!-- ===== MODAL RESI PEMBAYARAN ===== -->
<div class="modal-overlay" id="resiModal">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div class="modal-title" style="font-family:'Sora',sans-serif;">🧾 Resi Pembayaran</div>
            <button class="modal-close" onclick="document.getElementById('resiModal').classList.remove('open')">✕</button>
        </div>
        <div class="modal-body" id="resiBody"></div>
        <div class="modal-footer">
            <button onclick="document.getElementById('resiModal').classList.remove('open')" class="btn btn-ghost">Tutup</button>
            <button onclick="printResi()" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Cetak Resi
            </button>
        </div>
    </div>
</div>

<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('open');}

const payLabels = {
    'cash'    : '💵 Cash',
    'transfer': '🏦 Transfer Bank',
    'e-wallet': '📱 E-Wallet',
    'cod'     : '🚗 COD – Bayar saat diantar',
};

function openResi(orderId) {
    const row   = document.querySelector('tr[data-oid="' + orderId + '"]');
    if (!row) return;
    const order = JSON.parse(row.dataset.order);
    const isPaid = order.payment_status === 'paid';

    document.getElementById('resiBody').innerHTML = `
    <div style="border:2px dashed var(--gray-200);border-radius:12px;padding:20px;margin-bottom:16px;" id="resiContent">
        <!-- Header -->
        <div style="text-align:center;margin-bottom:16px;padding-bottom:14px;border-bottom:1px dashed var(--gray-200);">
            <div style="font-family:'Sora',sans-serif;font-size:22px;font-weight:800;color:var(--primary);">WashWell</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:2px;">Laundry Management System</div>
        </div>
        <!-- Kode pesanan -->
        <div style="text-align:center;background:var(--gray-50);border-radius:8px;padding:12px;margin-bottom:14px;">
            <div style="font-size:10px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:1px;">Nomor Pesanan</div>
            <div style="font-family:'Sora',sans-serif;font-size:24px;font-weight:800;color:var(--primary);letter-spacing:1px;">${order.code}</div>
        </div>
        <!-- Detail -->
        <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;font-size:13px;">
                <span style="color:var(--gray-500);">Tanggal Pesanan</span>
                <span style="font-weight:600;">${order.created}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;">
                <span style="color:var(--gray-500);">Est. Selesai</span>
                <span style="font-weight:600;">${order.delivery}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;">
                <span style="color:var(--gray-500);">Layanan</span>
                <span style="font-weight:600;">${order.service}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;">
                <span style="color:var(--gray-500);">Total Berat</span>
                <span style="font-weight:600;">${order.weight} kg</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;">
                <span style="color:var(--gray-500);">Metode Bayar</span>
                <span style="font-weight:600;">${payLabels[order.payment] ?? order.payment}</span>
            </div>
        </div>
        <!-- Total -->
        <div style="background:var(--primary-bg);border-radius:8px;padding:14px;display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <span style="font-size:14px;font-weight:700;color:var(--primary);">Total Tagihan</span>
            <span style="font-family:'Sora',sans-serif;font-size:22px;font-weight:800;color:var(--primary);">${order.amount}</span>
        </div>
        <!-- Status bayar -->
        <div style="text-align:center;">
            ${isPaid
                ? '<span style="background:var(--green-light);color:#166534;padding:6px 24px;border-radius:20px;font-size:13px;font-weight:700;">✅ LUNAS</span>'
                : '<span style="background:#FEE2E2;color:#991B1B;padding:6px 24px;border-radius:20px;font-size:13px;font-weight:700;">⏳ BELUM LUNAS</span>'
            }
        </div>
    </div>
    <div style="font-size:11px;color:var(--gray-400);text-align:center;">Terima kasih telah mempercayai WashWell 🧺</div>`;

    document.getElementById('resiModal').classList.add('open');
}

function printResi() {
    const content = document.getElementById('resiContent').outerHTML;
    const w = window.open('', '_blank', 'width=500,height=750');
    w.document.write(`<!DOCTYPE html><html><head><title>Resi WashWell</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Sora:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root{--primary:#2563EB;--primary-bg:#EFF6FF;--green-light:#DCFCE7;--gray-50:#F8FAFC;--gray-200:#E2E8F0;--gray-400:#94A3B8;--gray-500:#64748B;}
        body{font-family:'Plus Jakarta Sans',sans-serif;padding:24px;font-size:13px;color:#1E293B;max-width:420px;margin:auto;}
        @media print{body{padding:0;}}
    </style></head><body>${content}
    <p style="text-align:center;font-size:11px;color:#94A3B8;margin-top:12px;">Terima kasih telah mempercayai WashWell 🧺</p>
    <script>window.onload=function(){window.print();}<\/script>
    </body></html>`);
    w.document.close();
}

document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function(e){ if(e.target===this) this.classList.remove('open'); });
});
</script>
</body>
</html>
