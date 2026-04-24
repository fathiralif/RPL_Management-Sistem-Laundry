<?php
require_once '../includes/auth.php';
requireAdminOrStaff('../pages/login.php');
$db = getDB();
$user = getCurrentUser();

// Handle actions
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
        // Get price
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
$where = ['1=1'];
if ($statusFilter) $where[] = "o.status='".addslashes($statusFilter)."'";
if ($search) $where[] = "(o.order_code LIKE '%".addslashes($search)."%' OR u.name LIKE '%".addslashes($search)."%')";
$whereStr = implode(' AND ', $where);

// Pagination
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page-1) * $perPage;
$total = $db->query("SELECT COUNT(*) as c FROM orders o JOIN users u ON o.user_id=u.id WHERE $whereStr")->fetch_assoc()['c'];
$pages = ceil($total / $perPage);

$orders = $db->query("SELECT o.*,u.name as customer_name,s.name as service_name FROM orders o JOIN users u ON o.user_id=u.id JOIN services s ON o.service_id=s.id WHERE $whereStr ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset");
$customers = $db->query("SELECT id,name FROM users WHERE role='customer' ORDER BY name");
$services = $db->query("SELECT id,name,price_per_kg FROM services WHERE is_active=1 ORDER BY name");

$statusLabels = ['pending'=>'Pending','in_progress'=>'Diproses','ready'=>'Siap','delivered'=>'Terkirim','cancelled'=>'Dibatalkan'];
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
                <button class="notif-btn"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></button>
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

            <!-- FILTER STATUS TABS -->
            <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
                <?php
                $tabs = [
                    ['' ,'Semua', $db->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c']],
                    ['pending','Pending', $db->query("SELECT COUNT(*) as c FROM orders WHERE status='pending'")->fetch_assoc()['c']],
                    ['in_progress','Diproses', $db->query("SELECT COUNT(*) as c FROM orders WHERE status='in_progress'")->fetch_assoc()['c']],
                    ['ready','Siap', $db->query("SELECT COUNT(*) as c FROM orders WHERE status='ready'")->fetch_assoc()['c']],
                    ['delivered','Terkirim', $db->query("SELECT COUNT(*) as c FROM orders WHERE status='delivered'")->fetch_assoc()['c']],
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
                    <span class="card-title">Daftar Pesanan</span>
                    <form method="GET" style="display:flex;gap:8px;">
                        <input type="hidden" name="status" value="<?= $statusFilter ?>">
                        <div style="position:relative">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--gray-400)"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" name="search" placeholder="Cari pesanan..." value="<?= htmlspecialchars($search) ?>" style="padding:8px 14px 8px 34px;border:1.5px solid var(--gray-200);border-radius:8px;font-size:13px;font-family:var(--font);outline:none;width:200px;">
                        </div>
                        <button type="submit" class="btn btn-ghost btn-sm">Cari</button>
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
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Pembayaran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($orders && $orders->num_rows > 0): ?>
                                <?php while ($o = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td><span style="font-weight:700;font-family:monospace;color:var(--primary)"><?= $o['order_code'] ?></span></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <div class="avatar avatar-sm"><?= strtoupper(substr($o['customer_name'],0,1)) ?></div>
                                            <?= htmlspecialchars($o['customer_name']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($o['service_name']) ?></td>
                                    <td><span class="badge badge-<?= $o['status'] ?>"><?= $statusLabels[$o['status']] ?? $o['status'] ?></span></td>
                                    <td style="color:var(--gray-500);font-size:12.5px"><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                                    <td><strong><?= formatRupiah($o['amount']) ?></strong></td>
                                    <td><span class="badge badge-<?= $o['payment_status'] ?>"><?= $o['payment_status']==='paid'?'Lunas':'Belum Bayar' ?></span></td>
                                    <td>
                                        <div style="display:flex;gap:6px;">
                                            <button onclick="openUpdate(<?= $o['id'] ?>, '<?= $o['status'] ?>')" class="btn btn-primary btn-sm">Update</button>
                                            <form method="POST" onsubmit="return confirm('Hapus pesanan ini?')" style="display:inline">
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
                    <!-- PAGINATION -->
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
                        <?php while ($c = $customers->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Layanan</label>
                    <select name="service_id" class="form-control form-select" required id="serviceSelect" onchange="calcAmount()">
                        <option value="">-- Pilih Layanan --</option>
                        <?php
                        $services->data_seek(0);
                        while ($s = $services->fetch_assoc()): ?>
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
                        <option value="cash">Cash</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="e-wallet">E-Wallet</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan (opsional)</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Catatan khusus untuk pesanan ini..."></textarea>
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
    <div class="modal" style="max-width:400px">
        <div class="modal-header">
            <div class="modal-title">Update Status Pesanan</div>
            <button class="modal-close" onclick="document.getElementById('updateModal').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" id="updateOrderId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Status Baru</label>
                    <select name="status" class="form-control form-select" id="updateStatus">
                        <option value="pending">Pending</option>
                        <option value="in_progress">Diproses</option>
                        <option value="ready">Siap Diambil</option>
                        <option value="delivered">Terkirim</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('updateModal').classList.remove('open')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('open');}
function openUpdate(id, status) {
    document.getElementById('updateOrderId').value = id;
    document.getElementById('updateStatus').value = status;
    document.getElementById('updateModal').classList.add('open');
}
function calcAmount() {
    const sel = document.getElementById('serviceSelect');
    const opt = sel.options[sel.selectedIndex];
    const price = parseFloat(opt.getAttribute('data-price') || 0);
    const weight = parseFloat(document.getElementById('weightInput').value || 0);
    const total = price * weight;
    document.getElementById('amountDisplay').value = 'Rp ' + total.toLocaleString('id-ID');
}
// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function(e){ if(e.target===this) this.classList.remove('open'); });
});
</script>
</body>
</html>