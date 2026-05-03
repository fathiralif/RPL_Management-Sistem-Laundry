<?php
require_once '../includes/auth.php';
requireRole('customer', '../pages/login.php');
$db = getDB();
$user = getCurrentUser();
$uid = (int)$user['id'];

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name    = trim($_POST['name']);
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if (!$name) {
            $msg = 'Nama tidak boleh kosong.'; $msgType = 'danger';
        } else {
            $stmt = $db->prepare("UPDATE users SET name=?,phone=?,address=? WHERE id=?");
            $stmt->bind_param('sssi', $name, $phone, $address, $uid);
            if ($stmt->execute()) { $msg = 'Profil berhasil diperbarui!'; $msgType = 'success'; $user = getCurrentUser(); }
            else { $msg = 'Gagal memperbarui profil.'; $msgType = 'danger'; }
        }
    }

    if ($action === 'change_password') {
        $oldPass = $_POST['old_password'];
        $newPass = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        if (!password_verify($oldPass, $user['password'])) {
            $msg = 'Password lama tidak sesuai.'; $msgType = 'danger';
        } elseif (strlen($newPass) < 6) {
            $msg = 'Password baru minimal 6 karakter.'; $msgType = 'danger';
        } elseif ($newPass !== $confirm) {
            $msg = 'Konfirmasi password tidak cocok.'; $msgType = 'danger';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $uid);
            if ($stmt->execute()) { $msg = 'Password berhasil diubah!'; $msgType = 'success'; }
            else { $msg = 'Gagal mengubah password.'; $msgType = 'danger'; }
        }
    }
}

// Stats
$totalOrders  = $db->query("SELECT COUNT(*) as c FROM orders WHERE user_id=$uid")->fetch_assoc()['c'];
$totalSpent   = $db->query("SELECT COALESCE(SUM(t.amount),0) as s FROM transactions t JOIN orders o ON t.order_id=o.id WHERE o.user_id=$uid AND t.status='success'")->fetch_assoc()['s'];
$memberSince  = date('d M Y', strtotime($user['created_at']));
$notifCount   = getUnreadNotifs($uid);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil Saya - WashWell</title>
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
                <div class="topbar-title">Profil Saya</div>
            </div>
        </header>
        <main class="page-content">
            <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?>"><?= $msgType==='success'?'✅':'⚠️' ?> <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start;">
                <!-- Profile Card -->
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div class="card">
                        <div class="card-body" style="text-align:center;padding:32px 24px;">
                            <div style="width:72px;height:72px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:white;margin:0 auto 16px;font-family:'Sora',sans-serif;">
                                <?= strtoupper(substr($user['name'],0,1)) ?>
                            </div>
                            <div style="font-family:'Sora',sans-serif;font-size:18px;font-weight:700;"><?= htmlspecialchars($user['name']) ?></div>
                            <div style="font-size:13px;color:var(--gray-500);margin-bottom:16px;"><?= htmlspecialchars($user['email']) ?></div>
                            <span style="display:inline-block;background:var(--primary-bg);color:var(--primary);padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;">Customer</span>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--gray-400);margin-bottom:12px;">Statistik</div>
                            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:13px;">
                                <span style="color:var(--gray-500);">Member Sejak</span>
                                <span style="font-weight:600;"><?= $memberSince ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:13px;">
                                <span style="color:var(--gray-500);">Total Pesanan</span>
                                <span style="font-weight:600;"><?= $totalOrders ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;">
                                <span style="color:var(--gray-500);">Total Bayar</span>
                                <span style="font-weight:600;color:var(--primary);"><?= formatRupiah($totalSpent) ?></span>
                            </div>
                        </div>
                    </div>
                    <a href="logout.php" class="btn btn-danger btn-full" onclick="return confirm('Yakin ingin keluar?')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Keluar
                    </a>
                </div>

                <!-- Edit Forms -->
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <!-- Edit Profil -->
                    <div class="card">
                        <div class="card-header"><span class="card-title">Edit Informasi Profil</span></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                    <div class="form-group">
                                        <label class="form-label">Nama Lengkap *</label>
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:var(--gray-50);color:var(--gray-500);">
                                        <small style="font-size:11px;color:var(--gray-400);">Email tidak dapat diubah</small>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">No. Telepon</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="081234567890">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Alamat Lengkap</label>
                                    <textarea name="address" class="form-control" rows="3" placeholder="Jl. Contoh No. 1, Kelurahan, Kecamatan, Kota"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    Simpan Perubahan
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Ganti Password -->
                    <div class="card">
                        <div class="card-header"><span class="card-title">Ganti Password</span></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                <div class="form-group">
                                    <label class="form-label">Password Lama *</label>
                                    <input type="password" name="old_password" class="form-control" placeholder="Masukkan password saat ini" required>
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                    <div class="form-group">
                                        <label class="form-label">Password Baru *</label>
                                        <input type="password" name="new_password" class="form-control" placeholder="Min. 6 karakter" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Konfirmasi Password *</label>
                                        <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password baru" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-outline">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    Ganti Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<style>
@media(max-width:900px){.main-content div[style*="grid-template-columns:300px 1fr"]{grid-template-columns:1fr!important;}}
</style>
<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('open');}
</script>
</body>
</html>
