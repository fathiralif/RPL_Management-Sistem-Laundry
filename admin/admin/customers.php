<?php
require_once '../includes/auth.php';
requireAdminOrStaff('../pages/login.php');
$db = getDB();
$user = getCurrentUser();

$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name,email,password,phone,address,role) VALUES (?,?,?,?,?,'customer')");
        $stmt->bind_param('sssss',$name,$email,$password,$phone,$address);
        if ($stmt->execute()) { $msg='Pelanggan berhasil ditambahkan!'; $msgType='success'; }
        else { $msg='Gagal: '.$db->error; $msgType='danger'; }
    }
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $db->query("UPDATE users SET name='".addslashes($name)."',phone='".addslashes($phone)."',address='".addslashes($address)."' WHERE id=$id AND role='customer'");
        $msg='Data pelanggan diperbarui!'; $msgType='success';
    }
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM users WHERE id=$id AND role='customer'");
        $msg='Pelanggan dihapus.'; $msgType='success';
    }
}

$search = trim($_GET['search'] ?? '');
$where = "role='customer'";
if ($search) $where .= " AND (name LIKE '%".addslashes($search)."%' OR email LIKE '%".addslashes($search)."%' OR phone LIKE '%".addslashes($search)."%')";
$perPage = 10; $page = max(1,(int)($_GET['page'] ?? 1)); $offset = ($page-1)*$perPage;
$total = $db->query("SELECT COUNT(*) as c FROM users WHERE $where")->fetch_assoc()['c'];
$pages = ceil($total/$perPage);
$customers = $db->query("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) as total_orders, (SELECT COALESCE(SUM(t.amount),0) FROM transactions t JOIN orders o ON t.order_id=o.id WHERE o.user_id=u.id AND t.status='success') as total_spent FROM users u WHERE $where ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset");
$totalCustomers = $db->query("SELECT COUNT(*) as c FROM users WHERE role='customer'")->fetch_assoc()['c'];
$newThisMonth = $db->query("SELECT COUNT(*) as c FROM users WHERE role='customer' AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetch_assoc()['c'];
$activeCustomers = $db->query("SELECT COUNT(DISTINCT user_id) as c FROM orders WHERE status IN ('pending','in_progress','ready')")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pelanggan - WashWell Admin</title>
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
                <div class="topbar-title">Pelanggan</div>
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

            <!-- HERO -->
            <div class="page-hero animate-in">
                <img src="../assets/img/bubble-deco.svg" class="page-hero-img" alt="">
                <div class="page-hero-title">👥 Manajemen Pelanggan</div>
                <div class="page-hero-sub">Kelola data pelanggan, riwayat pesanan, dan informasi kontak</div>
            </div>

            <!-- STATS -->
            <div class="metric-row">
                <div class="metric-card animate-in">
                    <div class="metric-icon" style="background:#EFF6FF;color:var(--primary)">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="metric-value" style="color:var(--primary)"><?= $totalCustomers ?></div>
                    <div class="metric-label">Total Pelanggan</div>
                    <div class="metric-trend up">↑ Terdaftar</div>
                </div>
                <div class="metric-card animate-in">
                    <div class="metric-icon" style="background:var(--green-light);color:var(--green)">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
                    </div>
                    <div class="metric-value" style="color:var(--green)"><?= $activeCustomers ?></div>
                    <div class="metric-label">Pelanggan Aktif</div>
                    <div class="metric-trend up">↑ Ada pesanan aktif</div>
                </div>
                <div class="metric-card animate-in">
                    <div class="metric-icon" style="background:#FEF3C7;color:#D97706">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div class="metric-value" style="color:#D97706"><?= $newThisMonth ?></div>
                    <div class="metric-label">Baru Bulan Ini</div>
                    <div class="metric-trend up">↑ Pendaftaran baru</div>
                </div>
                <div class="metric-card animate-in">
                    <div class="metric-icon" style="background:var(--teal-light);color:var(--teal)">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <div class="metric-value" style="color:var(--teal)"><?= $total ?></div>
                    <div class="metric-label">Hasil Pencarian</div>
                    <div class="metric-trend">Data ditampilkan</div>
                </div>
            </div>

            <!-- TABLE CARD -->
            <div class="card animate-in">
                <div class="card-header">
                    <span class="card-title">👤 Daftar Pelanggan</span>
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <form method="GET" style="display:flex;gap:8px;">
                            <div style="position:relative">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--gray-400)"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" name="search" placeholder="Cari nama, email..." value="<?= htmlspecialchars($search) ?>" style="padding:8px 14px 8px 34px;border:1.5px solid var(--gray-200);border-radius:8px;font-size:13px;font-family:var(--font);outline:none;width:220px;">
                            </div>
                            <button type="submit" class="btn btn-ghost btn-sm">Cari</button>
                        </form>
                        <?php if ($user['role']==='admin'): ?>
                        <button class="btn btn-primary btn-sm" onclick="document.getElementById('addModal').classList.add('open')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Tambah Pelanggan
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body" style="padding-top:16px;">
                    <div class="table-wrapper">
                        <table id="customerTable">
                            <thead>
                                <tr>
                                    <th>Pelanggan</th>
                                    <th>Kontak</th>
                                    <th>Alamat</th>
                                    <th>Total Pesanan</th>
                                    <th>Total Belanja</th>
                                    <th>Bergabung</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($customers && $customers->num_rows > 0): ?>
                                <?php while ($c = $customers->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <div class="avatar" style="background:hue-rotate(<?= (ord($c['name'][0]) * 15) % 360 ?>deg)"><?= strtoupper(substr($c['name'],0,1)) ?></div>
                                            <div>
                                                <div style="font-weight:700;color:var(--gray-900)"><?= htmlspecialchars($c['name']) ?></div>
                                                <div style="font-size:11.5px;color:var(--gray-500)"><?= htmlspecialchars($c['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="color:var(--gray-600)"><?= htmlspecialchars($c['phone'] ?: '-') ?></td>
                                    <td style="font-size:12.5px;color:var(--gray-500);max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($c['address'] ?: '-') ?></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <span style="font-weight:700;color:var(--gray-800)"><?= $c['total_orders'] ?></span>
                                            <div class="mini-chart">
                                                <?php for($i=0;$i<5;$i++): $h = rand(4,20); ?>
                                                <div class="mini-bar" style="height:<?= $h ?>px"></div>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong style="color:var(--green)"><?= formatRupiah($c['total_spent']) ?></strong></td>
                                    <td style="font-size:12px;color:var(--gray-500)"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                                    <td>
                                        <div style="display:flex;gap:6px;">
                                            <button onclick="openEdit(<?= htmlspecialchars(json_encode($c)) ?>)" class="btn btn-ghost btn-sm">Edit</button>
                                            <?php if ($user['role']==='admin'): ?>
                                            <form method="POST" onsubmit="return confirm('Hapus pelanggan ini?')" style="display:inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7">
                                    <div class="empty-state">
                                        <img src="../assets/img/empty-state.svg" alt="" width="120">
                                        <h3>Tidak ada pelanggan</h3>
                                        <p>Belum ada data pelanggan yang sesuai pencarian.</p>
                                    </div>
                                </td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i=1;$i<=$pages;$i++): ?>
                        <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>" class="page-btn <?= $page===$i?'active':'' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Tambah Pelanggan Baru</div>
            <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Nama Lengkap</label><input type="text" name="name" class="form-control" required placeholder="Nama pelanggan"></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required placeholder="email@contoh.com"></div>
                <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required placeholder="Min. 6 karakter" minlength="6"></div>
                <div class="form-group"><label class="form-label">No. HP</label><input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx"></div>
                <div class="form-group"><label class="form-label">Alamat</label><textarea name="address" class="form-control" rows="2" placeholder="Alamat lengkap..."></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addModal').classList.remove('open')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Edit Pelanggan</div>
            <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Nama Lengkap</label><input type="text" name="name" id="editName" class="form-control" required></div>
                <div class="form-group"><label class="form-label">No. HP</label><input type="text" name="phone" id="editPhone" class="form-control"></div>
                <div class="form-group"><label class="form-label">Alamat</label><textarea name="address" id="editAddress" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editModal').classList.remove('open')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('open');}
function openEdit(c) {
    document.getElementById('editId').value = c.id;
    document.getElementById('editName').value = c.name;
    document.getElementById('editPhone').value = c.phone || '';
    document.getElementById('editAddress').value = c.address || '';
    document.getElementById('editModal').classList.add('open');
}
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function(e){ if(e.target===this) this.classList.remove('open'); });
});
</script>
</body>
</html>
