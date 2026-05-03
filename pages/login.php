<?php
require_once '../includes/auth.php';
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user && in_array($user['role'], ['admin','staff'])) {
        header('Location: ../admin/dashboard.php'); exit;
    }
    header('Location: ../customer/dashboard.php'); exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            if (in_array($user['role'], ['admin','staff'])) {
                header('Location: ../admin/dashboard.php'); exit;
            }
            header('Location: ../customer/dashboard.php'); exit;
        } else {
            $error = 'Email atau password salah. Coba lagi.';
        }
    } else {
        $error = 'Mohon isi semua field.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk - WashWell</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --primary:#2563EB; --primary-dark:#1D4ED8; --primary-light:#EFF6FF;
    --orange:#F97316; --green:#22C55E;
    --gray-50:#F8FAFC; --gray-100:#F1F5F9; --gray-200:#E2E8F0;
    --gray-300:#CBD5E1; --gray-400:#94A3B8; --gray-500:#64748B;
    --gray-600:#475569; --gray-700:#334155; --gray-800:#1E293B; --gray-900:#0F172A;
    --font:'Plus Jakarta Sans',sans-serif; --font-display:'Sora',sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:var(--font);background:linear-gradient(135deg,#EFF6FF 0%,#F0F9FF 50%,#F0FDF4 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.auth-container{display:grid;grid-template-columns:1fr 1fr;max-width:960px;width:100%;background:white;border-radius:24px;box-shadow:0 24px 64px rgba(0,0,0,0.1);overflow:hidden;}
.auth-visual{background:linear-gradient(135deg,#2563EB,#1D4ED8,#1E40AF);padding:48px;display:flex;flex-direction:column;justify-content:center;position:relative;overflow:hidden;}
.auth-visual::before{content:'';position:absolute;top:-50%;right:-30%;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,0.06);}
.auth-visual::after{content:'';position:absolute;bottom:-20%;left:-20%;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,0.04);}
.visual-content{position:relative;z-index:1;}
.visual-logo{display:flex;align-items:center;gap:10px;margin-bottom:48px;}
.logo-box{width:38px;height:38px;background:rgba(255,255,255,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;}
.visual-logo span{font-family:var(--font-display);font-size:20px;font-weight:700;color:white;}
.visual-title{font-family:var(--font-display);font-size:32px;font-weight:800;color:white;line-height:1.2;margin-bottom:16px;}
.visual-sub{font-size:15px;color:rgba(255,255,255,0.75);line-height:1.7;margin-bottom:36px;}
.feature-list{display:flex;flex-direction:column;gap:14px;}
.feature-item{display:flex;align-items:center;gap:12px;color:rgba(255,255,255,0.9);font-size:14px;}
.feature-icon{width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.auth-form{padding:48px;}
.form-header{margin-bottom:32px;}
.form-header h2{font-family:var(--font-display);font-size:26px;font-weight:800;color:var(--gray-900);margin-bottom:8px;}
.form-header p{font-size:14px;color:var(--gray-500);}
.form-group{margin-bottom:18px;}
.form-label{display:block;font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:7px;}
.input-wrap{position:relative;}
.input-wrap svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--gray-400);pointer-events:none;}
.form-control{width:100%;padding:12px 14px 12px 42px;border:1.5px solid var(--gray-200);border-radius:10px;font-size:14px;font-family:var(--font);color:var(--gray-800);outline:none;transition:all 0.2s;background:var(--gray-50);}
.form-control:focus{border-color:var(--primary);background:white;box-shadow:0 0 0 3px rgba(37,99,235,0.1);}
.form-control::placeholder{color:var(--gray-400);}
.toggle-pass{position:absolute;right:13px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--gray-400);cursor:pointer;padding:4px;}
.forgot-link{text-align:right;margin-top:6px;}
.forgot-link a{font-size:12.5px;color:var(--primary);font-weight:600;}
.btn-submit{width:100%;padding:13px;background:var(--primary);color:white;border:none;border-radius:10px;font-size:15px;font-weight:700;font-family:var(--font);cursor:pointer;transition:all 0.2s;margin-top:6px;}
.btn-submit:hover{background:var(--primary-dark);transform:translateY(-1px);box-shadow:0 6px 16px rgba(37,99,235,0.3);}
.divider{text-align:center;position:relative;margin:24px 0;}
.divider::before{content:'';position:absolute;top:50%;left:0;right:0;height:1px;background:var(--gray-200);}
.divider span{background:white;padding:0 14px;position:relative;font-size:12.5px;color:var(--gray-400);font-weight:500;}
.demo-accounts{background:var(--gray-50);border-radius:10px;padding:14px;border:1px solid var(--gray-200);}
.demo-title{font-size:11.5px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;}
.demo-item{display:flex;align-items:center;justify-content:space-between;font-size:12.5px;margin-bottom:6px;gap:8px;}
.demo-label{font-weight:600;color:var(--gray-600);}
.demo-cred{color:var(--gray-500);font-family:monospace;}
.demo-fill{background:var(--primary-light);color:var(--primary);border:none;border-radius:6px;padding:3px 10px;font-size:11.5px;font-weight:600;cursor:pointer;font-family:var(--font);}
.register-link{text-align:center;margin-top:24px;font-size:14px;color:var(--gray-500);}
.register-link a{color:var(--primary);font-weight:700;}
.alert{padding:12px 16px;border-radius:8px;font-size:13.5px;margin-bottom:16px;background:#FEE2E2;color:#991B1B;border-left:3px solid #EF4444;display:flex;align-items:center;gap:8px;}
@media(max-width:768px){
    .auth-container{grid-template-columns:1fr;}
    .auth-visual{display:none;}
    .auth-form{padding:32px 24px;}
}
</style>
</head>
<body>
<div class="auth-container">
    <div class="auth-visual">
        <div class="visual-content">
            <div class="visual-logo">
                <div class="logo-box">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </div>
                <span>WashWell</span>
            </div>
            <h2 class="visual-title">Kelola Laundry Lebih Mudah & Efisien</h2>
            <p class="visual-sub">Platform digital terbaik untuk manajemen laundry modern dengan fitur lengkap.</p>
            <div class="feature-list">
                <div class="feature-item"><div class="feature-icon">📊</div>Dashboard real-time yang informatif</div>
                <div class="feature-item"><div class="feature-icon">🔔</div>Notifikasi status pesanan otomatis</div>
                <div class="feature-item"><div class="feature-icon">💰</div>Laporan keuangan lengkap & akurat</div>
                <div class="feature-item"><div class="feature-icon">📱</div>Akses dari perangkat apa pun</div>
            </div>
        </div>
    </div>
    <div class="auth-form">
        <div class="form-header">
            <a href="../index.php" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--gray-500);margin-bottom:24px;font-weight:500;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                Kembali ke Beranda
            </a>
            <h2>Selamat Datang Kembali 👋</h2>
            <p>Masuk ke akun WashWell Anda</p>
        </div>
        <?php if ($error): ?>
        <div class="alert">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email</label>
                <div class="input-wrap">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <input type="email" name="email" id="email" class="form-control" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrap">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                    <button type="button" class="toggle-pass" onclick="togglePass()">👁</button>
                </div>
                <div class="forgot-link"><a href="#">Lupa password?</a></div>
            </div>
            <button type="submit" class="btn-submit">Masuk ke Dashboard →</button>
        </form>
        <div class="divider"><span>Akun Demo</span></div>
        <div class="demo-accounts">
            <div class="demo-title">Coba tanpa daftar:</div>
            <div class="demo-item">
                <span class="demo-label">👑 Admin</span>
                <span class="demo-cred">admin@washwell.com</span>
                <button class="demo-fill" onclick="fillDemo('admin@washwell.com')">Isi</button>
            </div>
            <div class="demo-item">
                <span class="demo-label">🧑‍💼 Staff</span>
                <span class="demo-cred">staff@washwell.com</span>
                <button class="demo-fill" onclick="fillDemo('staff@washwell.com')">Isi</button>
            </div>
            <div class="demo-item">
                <span class="demo-label">👤 Customer</span>
                <span class="demo-cred">john@example.com</span>
                <button class="demo-fill" onclick="fillDemo('john@example.com')">Isi</button>
            </div>
            <div style="font-size:11.5px;color:var(--gray-400);margin-top:8px;">Password semua akun: <strong style="color:var(--gray-600)">password</strong></div>
        </div>
        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar Gratis</a>
        </div>
    </div>
</div>
<script>
function togglePass() {
    const p = document.getElementById('password');
    p.type = p.type === 'password' ? 'text' : 'password';
}
function fillDemo(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'password';
}
</script>
</body>
</html>
