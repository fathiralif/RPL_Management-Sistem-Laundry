<?php
require_once '../includes/auth.php';
requireAdminOrStaff('../pages/login.php');
$db = getDB();
$user = getCurrentUser();

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $uid = (int)$_POST['user_id'];
        $sid = (int)$_POST['service_id'];
        $weight = (float)$_POST['weight'];
        $pickup = $_POST['pickup_date'];
        $delivery = $_POST['delivery_date'];
        $notes = trim($_POST['notes'] ?? '');
        $payment = $_POST['payment_method'];
        $svc = $db->query("SELECT price_per_kg FROM services WHERE id=$sid")->fetch_assoc();
        $amount = $svc['price_per_kg'] * $weight;
        $code = generateOrderCode();
        $stmt = $db->prepare("INSERT INTO orders (order_code,user_id,service_id,weight,amount,pickup_date,delivery_date,notes,payment_method) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('siiddssss', $code,$uid,$sid,$weight,$amount,$pickup,$delivery,$notes,$payment);
        if ($stmt->execute()) { $msg='Pesanan berhasil ditambahkan!'; $msgType='success'; }
        else { $msg='Gagal menambahkan pesanan.'; $msgType='danger'; }
    }

    if ($action === 'update_status') {
        $oid = (int)$_POST['order_id'];
        $status = $_POST['status'];
        $db->query("UPDATE orders SET status='$status' WHERE id=$oid");

        // Notif ke customer saat status berubah jadi 'ready' atau 'delivered'
        $order = $db->query("SELECT o.*,u.id as cust_id,u.name as cust_name FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=$oid")->fetch_assoc();
        if ($order) {
            $cid = (int)$order['cust_id'];
            $ocode = $db->real_escape_string($order['order_code']);
            $cname = $db->real_escape_string($order['cust_name']);
            if ($status === 'ready') {
                $db->query("INSERT INTO notifications (user_id,title,message) VALUES ($cid,'✅ Pakaian Siap Diambil!','Halo $cname! Pesanan <b>$ocode</b> Anda sudah selesai dicuci dan siap diambil. Silakan datang ke laundry kami. Terima kasih! 🧺')");
            } elseif ($status === 'delivered') {
                $db->query("INSERT INTO notifications (user_id,title,message) VALUES ($cid,'🚚 Pesanan Telah Diantar!','Halo $cname! Pesanan <b>$ocode</b> Anda telah berhasil diantarkan. Terima kasih telah mempercayai WashWell! 😊')");
            } elseif ($status === 'in_progress') {
                $db->query("INSERT INTO notifications (user_id,title,message) VALUES ($cid,'🧼 Pakaian Sedang Dicuci','Halo $cname! Pesanan <b>$ocode</b> Anda sedang dalam proses pencucian. Kami akan memberi tahu jika sudah selesai.')");
            }
        }
        $msg='Status berhasil diperbarui!'; $msgType='success';
    }

    if ($action === 'delete') {
        $oid = (int)$_POST['order_id'];
        $db->query("DELETE FROM orders WHERE id=$oid");
        $msg='Pesanan berhasil dihapus!'; $msgType='success';
    }
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$dateFilter = trim($_GET['date'] ?? '');
$where = ['1=1'];
if ($statusFilter) $where[] = "o.status='".addslashes($statusFilter)."'";
if ($search) $where[] = "(o.order_code LIKE '%".addslashes($search)."%' OR u.name LIKE '%".addslashes($search)."%')";
if ($dateFilter) $where[] = "(o.delivery_date='".addslashes($dateFilter)."' OR o.pickup_date='".addslashes($dateFilter)."')";
$whereStr = implode(' AND ', $where);

// Pagination
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page-1) * $perPage;
$total = $db->query("SELECT COUNT(*) as c FROM orders o JOIN users u ON o.user_id=u.id WHERE $whereStr")->fetch_assoc()['c'];
$pages = ceil($total / $perPage);

$orders = $db->query("SELECT o.*,u.name as customer_name,u.phone as customer_phone,s.name as service_name FROM orders o JOIN users u ON o.user_id=u.id JOIN services s ON o.service_id=s.id WHERE $whereStr ORDER BY o.delivery_date ASC, o.created_at DESC LIMIT $perPage OFFSET $offset");

$customers = $db->query("SELECT id,name FROM users WHERE role='customer' ORDER BY name");
$services = $db->query("SELECT id,name,price_per_kg FROM services WHERE is_active=1 ORDER BY name");

// === JADWAL PENGANTARAN (hari ini & 7 hari ke depan) ===
$deliverySchedule = $db->query("
    SELECT o.*, u.name as customer_name, u.phone as customer_phone, u.address as customer_address, s.name as service_name
    FROM orders o
    JOIN users u ON o.user_id=u.id
    JOIN services s ON o.service_id=s.id
    WHERE o.delivery_date >= CURDATE()
      AND o.delivery_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
      AND o.status NOT IN ('delivered','cancelled')
    ORDER BY o.delivery_date ASC, o.id ASC
");

// Kelompokkan per tanggal
$scheduleByDate = [];
while ($row = $deliverySchedule->fetch_assoc()) {
    $scheduleByDate[$row['delivery_date']][] = $row;
}

// Waktu estimasi antar (slot per jam berdasarkan urutan)
$timeSlots = ['08:00','09:30','11:00','13:00','14:30','16:00','17:30'];

$statusLabels = ['pending'=>'Pending','in_progress'=>'Diproses','ready'=>'Siap','delivered'=>'Terkirim','cancelled'=>'Dibatalkan'];
$finalStatuses = ['delivered','cancelled']; // status yang sudah final — tombol update disembunyikan
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan - WashWell Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
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
                <div class="topbar-title">Manajemen Pesanan</div>
            </div>
            <div class="topbar-right">
                <button class="notif-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </button>
                <div style="display:flex;align-items:center;gap:8px;padding:6px 12px;border-radius:10px;background:var(--gray-100)">
                    <div class="avatar avatar-sm"><?= strtoupper(substr($user['name'],0,1)) ?></div>
                    <span style="font-size:13px;font-weight:600"><?= htmlspecialchars(explode(' ',$user['name'])[0]) ?></span>
                </div>
            </div>
        </header>
        <main class="page-content">
            <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?>"><?= $msgType==='success'?'✅':'⚠️' ?> <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <div class="page-header">
                <div>
                    <h1>Pesanan</h1>
                    <p>Total <?= $total ?> pesanan ditemukan</p>
                </div>
                <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah Pesanan
                </button>
            </div>

            <!-- ===== JADWAL PENGANTARAN ===== -->
            <?php if (!empty($scheduleByDate)): ?>
            <div class="card" style="margin-bottom:24px;border:none;background:linear-gradient(135deg,#EFF6FF 0%,#F0FDF4 100%);box-shadow:0 2px 16px rgba(59,130,246,0.10);">
                <div class="card-header" style="background:transparent;border-bottom:1.5px solid rgba(59,130,246,0.12);">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;background:linear-gradient(135deg,#3B82F6,#2563EB);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        </div>
                        <div>
                            <div style="font-weight:800;font-size:15px;color:#1E40AF;">Jadwal Pengantaran</div>
                            <div style="font-size:12px;color:#3B82F6;">7 hari ke depan · <?= array_sum(array_map('count',$scheduleByDate)) ?> pesanan akan diantar</div>
                        </div>
                    </div>
                    <a href="?tab=jadwal" style="font-size:12px;color:#3B82F6;font-weight:600;">Lihat semua →</a>
                </div>
                <div class="card-body" style="padding:16px;background:transparent;">
                    <div style="display:flex;flex-direction:column;gap:14px;">
                    <?php foreach ($scheduleByDate as $date => $items): ?>
                    <?php
                        $isToday = ($date === date('Y-m-d'));
                        $isTomorrow = ($date === date('Y-m-d', strtotime('+1 day')));
                        $dayLabel = $isToday ? '🔴 Hari Ini' : ($isTomorrow ? '🟡 Besok' : '🔵 '.date('l', strtotime($date)));
                        $dayStr = date('d F Y', strtotime($date));
                    ?>
                    <div style="background:white;border-radius:14px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,0.06);">
                        <!-- Date Header -->
                        <div style="background:<?= $isToday?'linear-gradient(90deg,#EF4444,#DC2626)':($isTomorrow?'linear-gradient(90deg,#F59E0B,#D97706)':'linear-gradient(90deg,#3B82F6,#2563EB)') ?>;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;">
                            <div style="color:white;">
                                <span style="font-weight:800;font-size:14px;"><?= $dayLabel ?></span>
                                <span style="font-size:12px;opacity:0.85;margin-left:10px;"><?= $dayStr ?></span>
                            </div>
                            <span style="background:rgba(255,255,255,0.25);color:white;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700;"><?= count($items) ?> pesanan</span>
                        </div>
                        <!-- Orders for this date -->
                        <div style="padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
                        <?php foreach ($items as $idx => $item): ?>
                        <?php
                            // Cek apakah di notes ada "ANTAR KE:"
                            $isDelivery = strpos($item['notes'] ?? '', '[ANTAR KE:') !== false;
                            $deliveryAddr = '';
                            if ($isDelivery) {
                                preg_match('/\[ANTAR KE: ([^\]]+)\]/', $item['notes'], $m);
                                $deliveryAddr = $m[1] ?? $item['customer_address'] ?? '-';
                            }
                            $timeSlot = $timeSlots[$idx % count($timeSlots)];
                        ?>
                        <div style="display:flex;align-items:center;gap:12px;padding:10px 12px;border:1.5px solid <?= $isDelivery?'#BFDBFE':'#D1FAE5' ?>;border-radius:10px;background:<?= $isDelivery?'#EFF6FF':'#F0FDF4' ?>;">
                            <!-- Jam -->
                            <div style="min-width:52px;text-align:center;background:<?= $isDelivery?'#2563EB':'#16A34A' ?>;border-radius:8px;padding:6px 4px;">
                                <div style="color:white;font-size:13px;font-weight:800;font-family:'Sora',sans-serif;"><?= $timeSlot ?></div>
                                <div style="color:rgba(255,255,255,0.75);font-size:9px;text-transform:uppercase;letter-spacing:0.5px;">WIB</div>
                            </div>
                            <!-- Info -->
                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                    <span style="font-weight:700;font-size:13px;color:var(--gray-800);"><?= htmlspecialchars($item['customer_name']) ?></span>
                                    <span style="font-size:10px;font-weight:700;padding:1px 7px;border-radius:10px;background:<?= $isDelivery?'#2563EB':'#16A34A' ?>;color:white;"><?= $isDelivery?'ANTAR':'AMBIL SENDIRI' ?></span>
                                    <span style="font-size:10px;color:var(--gray-400);">#<?= htmlspecialchars($item['order_code']) ?></span>
                                </div>
                                <div style="font-size:12px;color:var(--gray-600);margin-top:2px;"><?= htmlspecialchars($item['service_name']) ?> · <strong><?= $item['weight'] ?> kg</strong> · <?= formatRupiah($item['amount']) ?></div>
                                <?php if ($isDelivery && $deliveryAddr): ?>
                                <div style="font-size:11px;color:#2563EB;margin-top:3px;display:flex;align-items:center;gap:4px;">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <?= htmlspecialchars($deliveryAddr) ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($item['customer_phone']): ?>
                                <div style="font-size:11px;color:var(--gray-400);margin-top:2px;">📞 <?= htmlspecialchars($item['customer_phone']) ?></div>
                                <?php endif; ?>
                            </div>
                            <!-- Status + Quick Update -->
                            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                                <span class="badge badge-<?= $item['status'] ?>"><?= $statusLabels[$item['status']] ?? $item['status'] ?></span>
                                <?php if (!in_array($item['status'], $finalStatuses)): ?>
                                <button onclick="openUpdate(<?= $item['id'] ?>,'<?= $item['status'] ?>','<?= htmlspecialchars($item['customer_name']) ?>','<?= htmlspecialchars($item['order_code']) ?>')" class="btn btn-sm" style="font-size:11px;padding:3px 10px;background:#EFF6FF;color:#2563EB;border:1px solid #BFDBFE;">
                                    Update Status
                                </button>
                                <?php else: ?>
                                <span style="font-size:10px;color:var(--gray-400);font-style:italic;">Selesai</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div style="background:#F0FDF4;border:1.5px solid #BBF7D0;border-radius:14px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <div>
                    <div style="font-weight:700;font-size:13px;color:#15803D;">Tidak ada jadwal pengantaran minggu ini</div>
                    <div style="font-size:12px;color:#16A34A;">Semua pesanan sudah selesai atau belum ada yang dijadwalkan.</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- FILTER STATUS TABS -->
            <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
                <?php
                $tabs = [
                    ['' ,'Semua', $db->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c']],
                    ['pending','Pending', $db->query("SELECT COUNT(*) as c FROM orders WHERE status='pending'")->fetch_assoc()['c']],
                    ['in_progress','Diproses', $db->query("SELECT COUNT(*) as c FROM orders WHERE status='in_progress'")->fetch_assoc()['c']],
                    ['ready','Siap', $db->query("SELECT COUNT(*) as c FROM orders WHERE status='ready'")->fetch_assoc()['c']],
                    ['delivered','Terkirim', $db->query("SELECT COUNT(*) as c FROM orders WHERE status='delivered'")->fetch_assoc()['c']],
                    ['cancelled','Dibatalkan', $db->query("SELECT COUNT(*) as c FROM orders WHERE status='cancelled'")->fetch_assoc()['c']],
                ];
                foreach ($tabs as [$s,$l,$c]):
                    $active = $statusFilter===$s;
                ?>
                <a href="?status=<?= $s ?>&search=<?= urlencode($search) ?>" class="btn <?= $active?'btn-primary':'btn-ghost' ?> btn-sm">
                    <?= $l ?> <span style="background:<?= $active?'rgba(255,255,255,0.25)':'var(--gray-200)' ?>;border-radius:10px;padding:0 6px;font-size:11px;font-weight:700;"><?= $c ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Daftar Semua Pesanan</span>
                    <form method="GET" style="display:flex;gap:8px;align-items:center;">
                        <input type="hidden" name="status" value="<?= $statusFilter ?>">
                        <div style="position:relative">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--gray-400)"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" name="search" placeholder="Cari pesanan / pelanggan..." value="<?= htmlspecialchars($search) ?>" style="padding:8px 14px 8px 34px;border:1.5px solid var(--gray-200);border-radius:8px;font-size:13px;font-family:var(--font);outline:none;width:220px;">
                        </div>
                        <input type="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>" style="padding:8px 12px;border:1.5px solid var(--gray-200);border-radius:8px;font-size:13px;font-family:var(--font);outline:none;" title="Filter tanggal">
                        <button type="submit" class="btn btn-ghost btn-sm">Cari</button>
                        <?php if ($search || $dateFilter): ?>
                        <a href="?status=<?= $statusFilter ?>" class="btn btn-ghost btn-sm" style="color:#EF4444;">✕ Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="card-body" style="padding-top:16px;">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Pelanggan</th>
                                    <th>Layanan</th>
                                    <th>Status</th>
                                    <th>Tgl Antar</th>
                                    <th>Total</th>
                                    <th>Bayar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($orders && $orders->num_rows > 0): ?>
                                <?php while ($o = $orders->fetch_assoc()):
                                    $isFinal = in_array($o['status'], $finalStatuses);
                                    $isDelivery = strpos($o['notes'] ?? '', '[ANTAR KE:') !== false;
                                ?>
                                <tr style="<?= $isFinal ? 'opacity:0.65;' : '' ?>">
                                    <td>
                                        <span style="font-weight:700;font-family:monospace;color:var(--primary)"><?= $o['order_code'] ?></span>
                                        <?php if ($isDelivery): ?>
                                        <span style="display:block;font-size:10px;color:#2563EB;font-weight:600;margin-top:2px;">🚚 Diantar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <div class="avatar avatar-sm"><?= strtoupper(substr($o['customer_name'],0,1)) ?></div>
                                            <div>
                                                <div><?= htmlspecialchars($o['customer_name']) ?></div>
                                                <?php if ($o['customer_phone']): ?>
                                                <div style="font-size:11px;color:var(--gray-400);"><?= htmlspecialchars($o['customer_phone']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($o['service_name']) ?>
                                        <div style="font-size:11px;color:var(--gray-400);"><?= $o['weight'] ?> kg</div>
                                    </td>
                                    <td><span class="badge badge-<?= $o['status'] ?>"><?= $statusLabels[$o['status']] ?? $o['status'] ?></span></td>
                                    <td style="font-size:12.5px;">
                                        <?= $o['delivery_date'] ? date('d/m/Y', strtotime($o['delivery_date'])) : '-' ?>
                                        <?php if ($o['delivery_date'] === date('Y-m-d') && !$isFinal): ?>
                                        <span style="display:block;font-size:10px;font-weight:700;color:#EF4444;">Hari ini!</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= formatRupiah($o['amount']) ?></strong></td>
                                    <td><span class="badge badge-<?= $o['payment_status'] ?>"><?= $o['payment_status']==='paid'?'Lunas':'Belum Bayar' ?></span></td>
                                    <td>
                                        <div style="display:flex;gap:6px;align-items:center;">
                                            <?php if (!$isFinal): ?>
                                            <!-- Tombol Update: HANYA muncul jika belum final -->
                                            <button onclick="openUpdate(<?= $o['id'] ?>,'<?= $o['status'] ?>','<?= htmlspecialchars($o['customer_name']) ?>','<?= htmlspecialchars($o['order_code']) ?>')" class="btn btn-primary btn-sm">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/></svg>
                                                Update
                                            </button>
                                            <?php else: ?>
                                            <!-- Status final: badge saja, tombol update disembunyikan -->
                                            <span style="font-size:11px;color:var(--gray-400);font-style:italic;white-space:nowrap;">
                                                <?= $o['status']==='delivered'?'✅ Selesai':'❌ Batal' ?>
                                            </span>
                                            <?php endif; ?>
                                            <form method="POST" onsubmit="return confirm('Hapus pesanan <?= $o['order_code'] ?>?')" style="display:inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400)">Tidak ada pesanan ditemukan</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i=1; $i<=$pages; $i++): ?>
                        <a href="?status=<?= $statusFilter ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>" class="page-btn <?= $page===$i?'active':'' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ADD ORDER MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <div class="modal-title">Tambah Pesanan Baru</div>
            <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Pelanggan</label>
                    <select name="user_id" class="form-control form-select" required>
                        <option value="">-- Pilih Pelanggan --</option>
                        <?php $customers->data_seek(0); while ($c = $customers->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Layanan</label>
                    <select name="service_id" class="form-control form-select" required id="serviceSelect" onchange="calcAmount()">
                        <option value="">-- Pilih Layanan --</option>
                        <?php $services->data_seek(0); while ($s = $services->fetch_assoc()): ?>
                        <option value="<?= $s['id'] ?>" data-price="<?= $s['price_per_kg'] ?>"><?= htmlspecialchars($s['name']) ?> - <?= formatRupiah($s['price_per_kg']) ?>/kg</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label class="form-label">Berat (kg)</label>
                        <input type="number" name="weight" class="form-control" min="0.5" step="0.5" value="1" required id="weightInput" oninput="calcAmount()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Estimasi</label>
                        <input type="text" id="amountDisplay" class="form-control" readonly placeholder="Rp 0" style="background:var(--gray-50);font-weight:700;color:var(--primary)">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label class="form-label">Tanggal Pickup</label>
                        <input type="date" name="pickup_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="delivery_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Metode Pembayaran</label>
                    <select name="payment_method" class="form-control form-select">
                        <option value="cash">💵 Cash (Bayar ke Staff)</option>
                        <option value="transfer">🏦 Transfer Bank</option>
                        <option value="e-wallet">📱 E-Wallet</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan (opsional)</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Catatan khusus..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addModal').classList.remove('open')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Pesanan</button>
            </div>
        </form>
    </div>
</div>

<!-- UPDATE STATUS MODAL -->
<div class="modal-overlay" id="updateModal">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <div class="modal-title">Update Status Pesanan</div>
            <button class="modal-close" onclick="document.getElementById('updateModal').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" id="updateOrderId">
            <div class="modal-body">
                <!-- Info pesanan -->
                <div style="background:var(--gray-50);border-radius:10px;padding:14px;margin-bottom:16px;">
                    <div style="font-size:11px;color:var(--gray-500);margin-bottom:2px;">Pesanan</div>
                    <div style="font-weight:700;color:var(--primary);font-size:15px;" id="updateOrderCode"></div>
                    <div style="font-size:12px;color:var(--gray-600);" id="updateCustomerName"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status Baru</label>
                    <div style="display:flex;flex-direction:column;gap:8px;" id="statusOptions">
                        <?php
                        $statusOpts = [
                            'pending'     => ['🕐', 'Pending', 'Menunggu konfirmasi', 'var(--gray-200)', 'var(--gray-700)'],
                            'in_progress' => ['🧼', 'Sedang Diproses', 'Pakaian sedang dicuci', '#FEF3C7', '#92400E'],
                            'ready'       => ['✅', 'Siap Diambil', 'Pakaian siap, info ke customer', '#D1FAE5', '#065F46'],
                            'delivered'   => ['🚚', 'Terkirim / Selesai', 'Pesanan selesai — tidak dapat diubah lagi', '#DBEAFE', '#1E40AF'],
                            'cancelled'   => ['❌', 'Dibatalkan', 'Batalkan pesanan — tidak dapat diubah lagi', '#FEE2E2', '#991B1B'],
                        ];
                        foreach ($statusOpts as $val => [$icon,$label,$desc,$bg,$color]):
                        ?>
                        <label style="cursor:pointer;">
                            <input type="radio" name="status" value="<?= $val ?>" style="display:none;" class="status-radio">
                            <div class="status-opt-inner" data-val="<?= $val ?>" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:2px solid var(--gray-200);border-radius:10px;transition:all 0.2s;">
                                <span style="font-size:20px;"><?= $icon ?></span>
                                <div style="flex:1;">
                                    <div style="font-weight:700;font-size:13px;color:var(--gray-800);"><?= $label ?></div>
                                    <div style="font-size:11px;color:var(--gray-500);"><?= $desc ?></div>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Warning untuk status final -->
                <div id="finalWarning" style="display:none;background:#FEF3C7;border:1.5px solid #FCD34D;border-radius:10px;padding:12px;font-size:12px;color:#92400E;margin-top:8px;">
                    ⚠️ <strong>Perhatian!</strong> Status ini bersifat final. Setelah disimpan, tombol Update tidak akan muncul lagi untuk pesanan ini.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('updateModal').classList.remove('open')">Batal</button>
                <button type="submit" class="btn btn-primary" id="updateSaveBtn">Simpan Status</button>
            </div>
        </form>
    </div>
</div>

<style>
.status-radio:checked + .status-opt-inner {
    border-color: var(--primary) !important;
    background: var(--primary-bg) !important;
}
.status-opt-inner:hover { border-color: var(--primary) !important; }
</style>

<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('open');}

function openUpdate(id, status, customerName, orderCode) {
    document.getElementById('updateOrderId').value = id;
    document.getElementById('updateOrderCode').textContent = orderCode;
    document.getElementById('updateCustomerName').textContent = '👤 ' + customerName;

    // Set current status as checked
    document.querySelectorAll('.status-radio').forEach(r => {
        r.checked = (r.value === status);
        if (r.checked) r.closest('label').querySelector('.status-opt-inner').style.borderColor = 'var(--primary)';
    });

    checkFinalWarning(status);
    document.getElementById('updateModal').classList.add('open');
}

function checkFinalWarning(val) {
    const isFinal = (val === 'delivered' || val === 'cancelled');
    document.getElementById('finalWarning').style.display = isFinal ? 'block' : 'none';
}

document.querySelectorAll('.status-radio').forEach(r => {
    r.addEventListener('change', function() {
        document.querySelectorAll('.status-opt-inner').forEach(el => el.style.borderColor = '');
        this.closest('label').querySelector('.status-opt-inner').style.borderColor = 'var(--primary)';
        checkFinalWarning(this.value);
    });
});

function calcAmount() {
    const sel = document.getElementById('serviceSelect');
    const opt = sel.options[sel.selectedIndex];
    const price = parseFloat(opt.getAttribute('data-price') || 0);
    const weight = parseFloat(document.getElementById('weightInput').value || 0);
    document.getElementById('amountDisplay').value = 'Rp ' + Math.round(price * weight).toLocaleString('id-ID');
}

document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function(e){ if(e.target===this) this.classList.remove('open'); });
});
</script>
</body>
</html>
