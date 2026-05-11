<?php
require_once '../includes/auth.php';
requireAdmin('../pages/login.php');
$db = getDB();
$user = getCurrentUser();

$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $id = $user['id'];
        $name = addslashes(trim($_POST['name']));
        $phone = addslashes(trim($_POST['phone'] ?? ''));
        $db->query("UPDATE users SET name='$name',phone='$phone' WHERE id=$id");
        $msg='Profil berhasil diperbarui!'; $msgType='success';
        $user['name'] = $_POST['name'];
    }
    if ($action === 'change_password') {
        $id = $user['id'];
        $oldPass = $_POST['old_password'];
        $newPass = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        // Get current password
        $row = $db->query("SELECT password FROM users WHERE id=$id")->fetch_assoc();
        if (!password_verify($oldPass, $row['password'])) {
            $msg='Password lama tidak sesuai.'; $msgType='danger';
        } elseif ($newPass !== $confirm) {
            $msg='Konfirmasi password tidak cocok.'; $msgType='danger';
        } elseif (strlen($newPass) < 6) {
            $msg='Password baru minimal 6 karakter.'; $msgType='danger';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password='$hash' WHERE id=$id");
            $msg='Password berhasil diubah!'; $msgType='success';
        }
    }
}
$activeTab = $_GET['tab'] ?? 'profile';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengaturan - WashWell Admin</title>
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
                <div class="topbar-title">Pengaturan</div>
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
                    <h1>⚙️ Pengaturan</h1>
                    <p>Kelola profil akun dan preferensi sistem</p>
                </div>
            </div>

            <div class="settings-layout">
                <!-- NAV SIDEBAR -->
                <div class="settings-nav">
                    <div class="settings-nav-item <?= $activeTab==='profile'?'active':'' ?>" onclick="switchTab('profile')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Profil Saya
                    </div>
                    <div class="settings-nav-item <?= $activeTab==='password'?'active':'' ?>" onclick="switchTab('password')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Ubah Password
                    </div>
                    <div class="settings-nav-item <?= $activeTab==='notif'?'active':'' ?>" onclick="switchTab('notif')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        Notifikasi
                    </div>
                    <?php if ($user['role']==='admin'): ?>
                    <div class="settings-nav-item <?= $activeTab==='system'?'active':'' ?>" onclick="switchTab('system')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        Sistem
                    </div>
                    <?php endif; ?>
                    <div class="settings-nav-item <?= $activeTab==='about'?'active':'' ?>" onclick="switchTab('about')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Tentang
                    </div>
                </div>

                <!-- PANELS -->
                <div>
                    <!-- PROFILE -->
                    <div class="settings-panel <?= $activeTab==='profile'?'active':'' ?>" id="panel-profile">
                        <div class="card">
                            <div class="card-body">
                                <div class="settings-section-title">Profil Saya</div>
                                <div class="settings-section-sub">Informasi akun dan data diri</div>

                                <!-- Avatar display -->
                                <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;padding:16px;background:var(--gray-50);border-radius:12px;">
                                    <div class="avatar avatar-lg" style="width:56px;height:56px;font-size:20px;"><?= strtoupper(substr($user['name'],0,1)) ?></div>
                                    <div>
                                        <div style="font-family:var(--font-display);font-weight:700;font-size:15px"><?= htmlspecialchars($user['name']) ?></div>
                                        <div style="font-size:12.5px;color:var(--gray-500);margin-top:2px"><?= htmlspecialchars($user['email']) ?></div>
                                        <span class="staff-role-badge staff-role-admin" style="margin-top:6px;display:inline-block">
                                            👑 Admin
                                        </span>
                                    </div>
                                </div>

                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="form-group">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:var(--gray-50);color:var(--gray-400)">
                                        <small style="color:var(--gray-400);font-size:12px">Email tidak dapat diubah</small>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">No. HP</label>
                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- PASSWORD -->
                    <div class="settings-panel <?= $activeTab==='password'?'active':'' ?>" id="panel-password">
                        <div class="card">
                            <div class="card-body">
                                <div class="settings-section-title">Ubah Password</div>
                                <div class="settings-section-sub">Pastikan menggunakan password yang kuat dan unik</div>
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="form-group">
                                        <label class="form-label">Password Lama</label>
                                        <input type="password" name="old_password" class="form-control" required placeholder="Masukkan password saat ini">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Password Baru</label>
                                        <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="Min. 6 karakter">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Konfirmasi Password Baru</label>
                                        <input type="password" name="confirm_password" class="form-control" required placeholder="Ulangi password baru">
                                    </div>
                                    <div class="alert alert-info" style="margin-bottom:16px">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        Gunakan kombinasi huruf, angka, dan simbol untuk keamanan optimal.
                                    </div>
                                    <button type="submit" class="btn btn-primary">Ubah Password</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- NOTIFIKASI -->
                    <div class="settings-panel <?= $activeTab==='notif'?'active':'' ?>" id="panel-notif">
                        <div class="card">
                            <div class="card-body">
                                <div class="settings-section-title">Pengaturan Notifikasi</div>
                                <div class="settings-section-sub">Atur jenis notifikasi yang ingin kamu terima</div>
                                <?php
                                $toggles = [
                                    ['Pesanan Masuk', 'Notifikasi ketika ada pesanan baru masuk', true],
                                    ['Update Status', 'Notifikasi perubahan status pesanan', true],
                                    ['Pembayaran', 'Notifikasi transaksi dan pembayaran berhasil', true],
                                    ['Laporan Harian', 'Ringkasan laporan operasional harian', false],
                                    ['Pengingat Deadline', 'Notifikasi pesanan mendekati batas waktu', true],
                                ];
                                foreach ($toggles as [$title, $sub, $checked]):
                                ?>
                                <div class="toggle-row">
                                    <div class="toggle-info">
                                        <div class="toggle-info-title"><?= $title ?></div>
                                        <div class="toggle-info-sub"><?= $sub ?></div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" <?= $checked?'checked':'' ?> onchange="saveToggle(this)">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- SISTEM -->
                    <?php if ($user['role']==='admin'): ?>
                    <div class="settings-panel <?= $activeTab==='system'?'active':'' ?>" id="panel-system">
                        <div class="card">
                            <div class="card-body">
                                <div class="settings-section-title">Pengaturan Sistem</div>
                                <div class="settings-section-sub">Konfigurasi operasional laundry</div>

                                <div style="margin-bottom:20px;">
                                    <label class="form-label">Nama Toko</label>
                                    <input type="text" class="form-control" value="WashWell Laundry">
                                </div>
                                <div style="margin-bottom:20px;">
                                    <label class="form-label">Jam Operasional</label>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                        <input type="time" class="form-control" value="07:00">
                                        <input type="time" class="form-control" value="21:00">
                                    </div>
                                </div>
                                <div style="margin-bottom:20px;">
                                    <label class="form-label">Berat Minimum Order (kg)</label>
                                    <input type="number" class="form-control" value="1" min="0.5" step="0.5">
                                </div>
                                <?php
                                $sysToggles = [
                                    ['Pendaftaran Pelanggan Baru', 'Izinkan pelanggan baru mendaftar sendiri', true],
                                    ['Mode Maintenance', 'Nonaktifkan akses publik sementara', false],
                                ];
                                foreach ($sysToggles as [$title, $sub, $checked]):
                                ?>
                                <div class="toggle-row">
                                    <div class="toggle-info">
                                        <div class="toggle-info-title"><?= $title ?></div>
                                        <div class="toggle-info-sub"><?= $sub ?></div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" <?= $checked?'checked':'' ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                                <div style="margin-top:20px;">
                                    <button class="btn btn-primary">Simpan Konfigurasi</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- TENTANG -->
                    <div class="settings-panel <?= $activeTab==='about'?'active':'' ?>" id="panel-about">
                        <div class="card">
                            <div class="card-body" style="text-align:center;padding:40px;">
                                <img src="../assets/img/washing-machine.svg" alt="WashWell" width="80" style="margin-bottom:16px;">
                                <div style="font-family:var(--font-display);font-size:22px;font-weight:800;color:var(--primary);margin-bottom:4px;">WashWell</div>
                                <div style="font-size:13px;color:var(--gray-500);margin-bottom:24px;">Sistem Manajemen Laundry v2.0</div>
                                <div style="display:inline-flex;flex-direction:column;gap:10px;text-align:left;background:var(--gray-50);border-radius:12px;padding:16px 24px;">
                                    <div style="font-size:13px;color:var(--gray-700)">📋 Versi: <strong>2.0.0</strong></div>
                                    <div style="font-size:13px;color:var(--gray-700)">🔧 PHP: <strong><?= phpversion() ?></strong></div>
                                    <div style="font-size:13px;color:var(--gray-700)">🗄️ Database: <strong>MySQL / MariaDB</strong></div>
                                    <div style="font-size:13px;color:var(--gray-700)">📅 Build: <strong><?= date('Y') ?></strong></div>
                                </div>
                                <div style="margin-top:24px;font-size:12px;color:var(--gray-400)">Dibuat dengan ❤️ untuk manajemen laundry yang lebih mudah</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('open');}

function switchTab(tab) {
    document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.settings-nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
    history.replaceState(null,'','?tab=' + tab);
}

function saveToggle(el) {
    // Visual feedback only - bisa diintegrasikan ke AJAX
    const row = el.closest('.toggle-row');
    row.style.opacity = '0.6';
    setTimeout(() => row.style.opacity = '1', 300);
}
</script>
</body>
</html>
