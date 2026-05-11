<?php
require_once '../includes/auth.php';
requireAdmin('../pages/login.php');
$db = getDB();
$user = getCurrentUser();

$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && $user['role']==='admin') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $desc = trim($_POST['description'] ?? '');
        $price = (float)$_POST['price_per_kg'];
        $duration = (int)$_POST['duration_hours'];
        $stmt = $db->prepare("INSERT INTO services (name,description,price_per_kg,duration_hours) VALUES (?,?,?,?)");
        $stmt->bind_param('ssdi',$name,$desc,$price,$duration);
        if ($stmt->execute()) { $msg='Layanan berhasil ditambahkan!'; $msgType='success'; }
        else { $msg='Gagal: '.$db->error; $msgType='danger'; }
    }
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = addslashes(trim($_POST['name']));
        $desc = addslashes(trim($_POST['description'] ?? ''));
        $price = (float)$_POST['price_per_kg'];
        $duration = (int)$_POST['duration_hours'];
        $active = isset($_POST['is_active']) ? 1 : 0;
        $db->query("UPDATE services SET name='$name',description='$desc',price_per_kg=$price,duration_hours=$duration,is_active=$active WHERE id=$id");
        $msg='Layanan berhasil diperbarui!'; $msgType='success';
    }
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM services WHERE id=$id");
        $msg='Layanan dihapus.'; $msgType='success';
    }
    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $db->query("UPDATE services SET is_active = NOT is_active WHERE id=$id");
        $msg='Status layanan diperbarui.'; $msgType='success';
    }
}

$services = $db->query("SELECT s.*, (SELECT COUNT(*) FROM orders o WHERE o.service_id=s.id) as total_orders, (SELECT COALESCE(SUM(t.amount),0) FROM transactions t JOIN orders o ON t.order_id=o.id WHERE o.service_id=s.id AND t.status='success') as total_revenue FROM services s ORDER BY s.created_at ASC");
$totalServices = $db->query("SELECT COUNT(*) as c FROM services")->fetch_assoc()['c'];
$activeServices = $db->query("SELECT COUNT(*) as c FROM services WHERE is_active=1")->fetch_assoc()['c'];

$serviceIcons = [
    'Cuci Reguler' => '🫧',
    'Cuci Express' => '⚡',
    'Cuci + Setrika' => '👕',
    'Dry Cleaning' => '✨',
    'Cuci Sepatu' => '👟',
    'Cuci Karpet' => '🧹',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Layanan - WashWell Admin</title>
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
                <div class="topbar-title">Layanan</div>
            </div>
            <div class="topbar-right">
                <button class="notif-btn" onclick="window.location='notifications.php'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if (getUnreadNotifs($user['id']) > 0): ?>
                    <span class="notif-dot"></span>
                    <?php endif; ?>
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

            <!-- HERO -->
            <div class="page-hero animate-in" style="background:linear-gradient(135deg,#7C3AED 0%,#6D28D9 50%,#2563EB 100%)">
                <img src="../assets/img/washing-machine.svg" class="page-hero-img" alt="">
                <div class="page-hero-title">🧺 Manajemen Layanan</div>
                <div class="page-hero-sub">Kelola daftar layanan, harga per kg, dan durasi pengerjaan</div>
            </div>

            <!-- STATS BAR -->
            <div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap;">
                <div class="card" style="padding:16px 20px;display:flex;align-items:center;gap:12px;flex:1;min-width:160px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:#F3F0FF;color:#7C3AED;display:flex;align-items:center;justify-content:center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M21 12h-2M17.66 17.66l-1.41-1.41M12 19v2M6.34 17.66l1.41-1.41M3 12H1M4.93 4.93l1.41 1.41M12 3V1"/></svg>
                    </div>
                    <div>
                        <div style="font-family:var(--font-display);font-size:20px;font-weight:800;color:#7C3AED"><?= $totalServices ?></div>
                        <div style="font-size:12px;color:var(--gray-500)">Total Layanan</div>
                    </div>
                </div>
                <div class="card" style="padding:16px 20px;display:flex;align-items:center;gap:12px;flex:1;min-width:160px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:var(--green-light);color:var(--green);display:flex;align-items:center;justify-content:center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div>
                        <div style="font-family:var(--font-display);font-size:20px;font-weight:800;color:var(--green)"><?= $activeServices ?></div>
                        <div style="font-size:12px;color:var(--gray-500)">Aktif</div>
                    </div>
                </div>
                <div class="card" style="padding:16px 20px;display:flex;align-items:center;gap:12px;flex:1;min-width:160px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:var(--gray-100);color:var(--gray-500);display:flex;align-items:center;justify-content:center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    </div>
                    <div>
                        <div style="font-family:var(--font-display);font-size:20px;font-weight:800;color:var(--gray-600)"><?= $totalServices - $activeServices ?></div>
                        <div style="font-size:12px;color:var(--gray-500)">Nonaktif</div>
                    </div>
                </div>
                <?php if ($user['role']==='admin'): ?>
                <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')" style="align-self:center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah Layanan
                </button>
                <?php endif; ?>
            </div>

            <!-- SERVICE GRID -->
            <div class="service-grid">
            <?php $services->data_seek(0); $i=0; while ($s = $services->fetch_assoc()): $i++; ?>
                <div class="service-card animate-in">
                    <?php if ($user['role']==='admin'): ?>
                    <div class="service-card-actions">
                        <button onclick="openEditService(<?= htmlspecialchars(json_encode($s)) ?>)" class="btn btn-ghost btn-sm" title="Edit">✏️</button>
                        <form method="POST" onsubmit="return confirm('Hapus layanan ini?')" style="display:inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus">🗑️</button>
                        </form>
                    </div>
                    <?php endif; ?>
                    <div class="service-card-icon" style="<?= !$s['is_active']?'background:var(--gray-100);color:var(--gray-400)':'' ?>">
                        <span style="font-size:22px"><?= $serviceIcons[$s['name']] ?? '🧺' ?></span>
                    </div>
                    <div class="service-card-name"><?= htmlspecialchars($s['name']) ?></div>
                    <div class="service-card-desc"><?= htmlspecialchars($s['description'] ?: 'Layanan laundry profesional WashWell.') ?></div>
                    <div class="service-card-price"><?= formatRupiah($s['price_per_kg']) ?><span>/<?= ($s['unit_type'] ?? 'kg') === 'item' ? 'pcs' : 'kg' ?></span></div>
                    <div class="service-card-meta">
                        <span class="service-tag <?= $s['is_active']?'service-tag-active':'service-tag-inactive' ?>">
                            <?= $s['is_active']?'✅ Aktif':'❌ Nonaktif' ?>
                        </span>
                        <span style="font-size:11.5px;color:var(--gray-500)">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?= $s['duration_hours'] ?>j
                        </span>
                        <span style="font-size:11.5px;color:var(--gray-500);margin-left:auto"><?= $s['total_orders'] ?> pesanan</span>
                    </div>
                    <?php if ($s['total_revenue'] > 0): ?>
                    <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--gray-100);font-size:12px;color:var(--green);font-weight:700">
                        💰 <?= formatRupiah($s['total_revenue']) ?> total pendapatan
                    </div>
                    <?php endif; ?>
                    <?php if ($user['role']==='admin'): ?>
                    <form method="POST" style="margin-top:10px">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-ghost btn-sm btn-full"><?= $s['is_active']?'Nonaktifkan':'Aktifkan' ?></button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
            </div>
        </main>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Tambah Layanan Baru</div>
            <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Nama Layanan</label><input type="text" name="name" class="form-control" required placeholder="cth: Cuci Express"></div>
                <div class="form-group"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2" placeholder="Deskripsi singkat layanan..."></textarea></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group"><label class="form-label">Harga/kg (Rp)</label><input type="number" name="price_per_kg" class="form-control" required min="1000" step="500" placeholder="7000"></div>
                    <div class="form-group"><label class="form-label">Durasi (jam)</label><input type="number" name="duration_hours" class="form-control" required min="1" value="24"></div>
                </div>
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
            <div class="modal-title">Edit Layanan</div>
            <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Nama Layanan</label><input type="text" name="name" id="editName" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Deskripsi</label><textarea name="description" id="editDesc" class="form-control" rows="2"></textarea></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group"><label class="form-label">Harga/kg (Rp)</label><input type="number" name="price_per_kg" id="editPrice" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Durasi (jam)</label><input type="number" name="duration_hours" id="editDuration" class="form-control" required></div>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="is_active" id="editActive" value="1">
                        <span class="form-label" style="margin:0">Layanan Aktif</span>
                    </label>
                </div>
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
function openEditService(s) {
    document.getElementById('editId').value = s.id;
    document.getElementById('editName').value = s.name;
    document.getElementById('editDesc').value = s.description || '';
    document.getElementById('editPrice').value = s.price_per_kg;
    document.getElementById('editDuration').value = s.duration_hours;
    document.getElementById('editActive').checked = s.is_active == 1;
    document.getElementById('editModal').classList.add('open');
}
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function(e){ if(e.target===this) this.classList.remove('open'); });
});
</script>
</body>
</html>
