<?php
require_once '../includes/auth.php';
requireAdmin('../pages/login.php'); // Hanya admin
$db = getDB();
$user = getCurrentUser();

$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone'] ?? '');
        $role = 'admin';
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name,email,password,phone,role) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssss',$name,$email,$password,$phone,$role);
        if ($stmt->execute()) { $msg='Admin berhasil ditambahkan!'; $msgType='success'; }
        else { $msg='Gagal: Email mungkin sudah terdaftar.'; $msgType='danger'; }
    }
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = addslashes(trim($_POST['name']));
        $phone = addslashes(trim($_POST['phone'] ?? ''));
        $db->query("UPDATE users SET name='$name',phone='$phone' WHERE id=$id AND role='admin'");
        $msg='Data admin diperbarui!'; $msgType='success';
    }
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id !== $user['id']) {
            $db->query("DELETE FROM users WHERE id=$id AND role='admin'");
            $msg='Admin dihapus.'; $msgType='success';
        } else {
            $msg='Tidak bisa menghapus akun sendiri.'; $msgType='danger';
        }
    }
}

$staffList = $db->query("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) as handled_orders FROM users u WHERE u.role='admin' ORDER BY u.created_at ASC");
$totalStaff = 0;
$totalAdmin = $db->query("SELECT COUNT(*) as c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$avatarColors = ['#2563EB','#16A34A','#D97706','#DC2626','#7C3AED','#0891B2'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Admin - WashWell</title>
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
                <div class="topbar-title">Manajemen Admin</div>
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
            <div class="page-hero animate-in" style="background:linear-gradient(135deg,#D97706 0%,#B45309 50%,#92400E 100%)">
                <img src="../assets/img/bubble-deco.svg" class="page-hero-img" alt="">
                <div class="page-hero-title">👑 Manajemen Admin</div>
                <div class="page-hero-sub">Kelola tim admin dan staff operasional WashWell</div>
            </div>

            <!-- STATS + ADD -->
            <div style="display:flex;gap:16px;margin-bottom:24px;align-items:center;flex-wrap:wrap;">
                <div class="card" style="padding:16px 20px;display:flex;align-items:center;gap:12px;flex:1;min-width:140px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:#FEF3C7;color:#D97706;display:flex;align-items:center;justify-content:center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <div style="font-family:var(--font-display);font-size:20px;font-weight:800;color:#D97706"><?= $totalStaff ?></div>
                        <div style="font-size:12px;color:var(--gray-500)">Total Admin</div>
                    </div>
                </div>
                <div class="card" style="padding:16px 20px;display:flex;align-items:center;gap:12px;flex:1;min-width:140px;">
                    <div style="width:40px;height:40px;border-radius:12px;background:#FEE2E2;color:#DC2626;display:flex;align-items:center;justify-content:center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div>
                        <div style="font-family:var(--font-display);font-size:20px;font-weight:800;color:#DC2626"><?= $totalAdmin ?></div>
                        <div style="font-size:12px;color:var(--gray-500)">Admin</div>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')" style="align-self:center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah Admin
                </button>
            </div>

            <!-- STAFF GRID -->
            <div class="staff-grid">
            <?php $i=0; while ($s = $staffList->fetch_assoc()): $color = $avatarColors[$i % count($avatarColors)]; $i++; ?>
                <div class="staff-card animate-in">
                    <div class="staff-avatar-lg" style="background:<?= $color ?>">
                        <?= strtoupper(substr($s['name'],0,1)) ?>
                    </div>
                    <div class="staff-name"><?= htmlspecialchars($s['name']) ?></div>
                    <div class="staff-role-badge staff-role-admin">
                        👑 Admin
                    </div>
                    <div class="staff-info-row">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <?= htmlspecialchars($s['email']) ?>
                    </div>
                    <?php if ($s['phone']): ?>
                    <div class="staff-info-row">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <?= htmlspecialchars($s['phone']) ?>
                    </div>
                    <?php endif; ?>
                    <div class="staff-divider"></div>
                    <div class="staff-stat">
                        <span class="staff-stat-label">Pesanan Ditangani</span>
                        <span class="staff-stat-val"><?= $s['handled_orders'] ?></span>
                    </div>
                    <div class="staff-stat" style="margin-top:6px;">
                        <span class="staff-stat-label">Bergabung</span>
                        <span class="staff-stat-val"><?= date('d M Y', strtotime($s['created_at'])) ?></span>
                    </div>
                    <?php if ($s['id'] !== $user['id']): ?>
                    <div style="display:flex;gap:8px;margin-top:16px;">
                        <button onclick="openEdit(<?= htmlspecialchars(json_encode($s)) ?>)" class="btn btn-ghost btn-sm" style="flex:1">Edit</button>
                        <form method="POST" onsubmit="return confirm('Hapus staff ini?')" style="flex:1">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm btn-full">Hapus</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div style="margin-top:16px;padding:8px;background:var(--gray-50);border-radius:8px;font-size:12px;color:var(--gray-500);text-align:center;">Akun Anda sendiri</div>
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
            <div class="modal-title">Tambah Admin Baru</div>
            <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Nama Lengkap</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
                <div class="form-group"><label class="form-label">No. HP</label><input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx"></div>
                <input type="hidden" name="role" value="admin">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addModal').classList.remove('open')">Batal</button>
                <button type="submit" class="btn btn-primary">Tambahkan</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Edit Staff</div>
            <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Nama Lengkap</label><input type="text" name="name" id="editName" class="form-control" required></div>
                <div class="form-group"><label class="form-label">No. HP</label><input type="text" name="phone" id="editPhone" class="form-control"></div>
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
function openEdit(s) {
    document.getElementById('editId').value = s.id;
    document.getElementById('editName').value = s.name;
    document.getElementById('editPhone').value = s.phone || '';
    document.getElementById('editModal').classList.add('open');
}
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function(e){ if(e.target===this) this.classList.remove('open'); });
});
</script>
</body>
</html>
