<?php
require_once '../includes/auth.php';
if (isLoggedIn()) { header('Location: ../index.php'); exit; }
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!$name || !$email || !$password) { $error = 'Mohon isi semua field yang diperlukan.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Format email tidak valid.'; }
    elseif (strlen($password) < 6) { $error = 'Password minimal 6 karakter.'; }
    elseif ($password !== $confirm) { $error = 'Password tidak cocok.'; }
    else {
        $db = getDB();
        $check = $db->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar. Gunakan email lain atau masuk.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name,email,password,phone,role) VALUES (?,?,?,?,'customer')");
            $stmt->bind_param('ssss', $name, $email, $hashed, $phone);
            if ($stmt->execute()) {
                $success = 'Akun berhasil dibuat! Silakan masuk.';
            } else { $error = 'Pendaftaran gagal. Coba lagi.'; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar - WashWell</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<style>
:root{--primary:#2563EB;--primary-dark:#1D4ED8;--primary-light:#EFF6FF;--gray-50:#F8FAFC;--gray-100:#F1F5F9;--gray-200:#E2E8F0;--gray-300:#CBD5E1;--gray-400:#94A3B8;--gray-500:#64748B;--gray-600:#475569;--gray-700:#334155;--gray-800:#1E293B;--gray-900:#0F172A;--font:'Plus Jakarta Sans',sans-serif;--font-display:'Sora',sans-serif;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:var(--font);background:linear-gradient(135deg,#EFF6FF 0%,#F0F9FF 50%,#F0FDF4 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.auth-container{display:grid;grid-template-columns:1fr 1fr;max-width:960px;width:100%;background:white;border-radius:24px;box-shadow:0 24px 64px rgba(0,0,0,0.1);overflow:hidden;}
.auth-visual{background:linear-gradient(135deg,#22C55E,#16A34A,#15803D);padding:48px;display:flex;flex-direction:column;justify-content:center;position:relative;overflow:hidden;}
.auth-visual::before{content:'';position:absolute;top:-50%;right:-30%;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,0.06);}
.visual-content{position:relative;z-index:1;}
.visual-logo{display:flex;align-items:center;gap:10px;margin-bottom:40px;}
.logo-box{width:38px;height:38px;background:rgba(255,255,255,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;}
.visual-logo span{font-family:var(--font-display);font-size:20px;font-weight:700;color:white;}
.visual-title{font-family:var(--font-display);font-size:28px;font-weight:800;color:white;line-height:1.2;margin-bottom:14px;}
.visual-sub{font-size:14px;color:rgba(255,255,255,0.8);line-height:1.7;margin-bottom:30px;}
.benefit-item{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,0.9);font-size:13.5px;margin-bottom:12px;}
.benefit-icon{font-size:18px;}
.auth-form{padding:44px;}
.form-header{margin-bottom:28px;}
.form-header h2{font-family:var(--font-display);font-size:24px;font-weight:800;color:var(--gray-900);margin-bottom:6px;}
.form-header p{font-size:13.5px;color:var(--gray-500);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:12.5px;font-weight:600;color:var(--gray-700);margin-bottom:6px;}
.input-wrap{position:relative;}
.input-wrap svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);pointer-events:none;}
.form-control{width:100%;padding:11px 14px 11px 40px;border:1.5px solid var(--gray-200);border-radius:10px;font-size:13.5px;font-family:var(--font);color:var(--gray-800);outline:none;transition:all 0.2s;background:var(--gray-50);}
.form-control:focus{border-color:var(--primary);background:white;box-shadow:0 0 0 3px rgba(37,99,235,0.08);}
.form-control::placeholder{color:var(--gray-400);}
.terms{display:flex;align-items:flex-start;gap:10px;margin-bottom:18px;font-size:13px;color:var(--gray-500);}
.terms input{margin-top:3px;flex-shrink:0;}
.terms a{color:var(--primary);font-weight:600;}
.btn-submit{width:100%;padding:13px;background:var(--primary);color:white;border:none;border-radius:10px;font-size:15px;font-weight:700;font-family:var(--font);cursor:pointer;transition:all 0.2s;}
.btn-submit:hover{background:var(--primary-dark);transform:translateY(-1px);box-shadow:0 6px 16px rgba(37,99,235,0.3);}
.login-link{text-align:center;margin-top:20px;font-size:13.5px;color:var(--gray-500);}
.login-link a{color:var(--primary);font-weight:700;}
.alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.alert-danger{background:#FEE2E2;color:#991B1B;border-left:3px solid #EF4444;}
.alert-success{background:#DCFCE7;color:#166534;border-left:3px solid #22C55E;}
.strength-bar{height:4px;border-radius:2px;background:var(--gray-200);margin-top:6px;overflow:hidden;}
.strength-fill{height:100%;border-radius:2px;transition:all 0.3s;width:0;}
@media(max-width:768px){
    .auth-container{grid-template-columns:1fr;}
    .auth-visual{display:none;}
    .auth-form{padding:28px 20px;}
    .form-row{grid-template-columns:1fr;}
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
            <h2 class="visual-title">Bergabung Sekarang, Gratis!</h2>
            <p class="visual-sub">Daftar dan mulai kelola laundry Anda dengan lebih efisien hari ini.</p>
            <div class="benefit-item"><span class="benefit-icon">✅</span> Daftar 100% gratis, tanpa kartu kredit</div>
            <div class="benefit-item"><span class="benefit-icon">📦</span> Pantau pesanan secara real-time</div>
            <div class="benefit-item"><span class="benefit-icon">🔒</span> Data aman & terenkripsi</div>
            <div class="benefit-item"><span class="benefit-icon">📱</span> Akses dari HP & komputer</div>
            <div class="benefit-item"><span class="benefit-icon">🎁</span> Nikmati fitur premium gratis 30 hari</div>
        </div>
    </div>
    <div class="auth-form">
        <div class="form-header">
            <a href="../index.php" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--gray-500);margin-bottom:20px;font-weight:500;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                Kembali ke Beranda
            </a>
            <h2>Buat Akun Baru 🎉</h2>
            <p>Isi data di bawah untuk mulai menggunakan WashWell</p>
        </div>
        <?php if ($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?> <a href="login.php" style="font-weight:700;color:#166534;margin-left:8px;">Masuk sekarang →</a></div><?php endif; ?>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap *</label>
                    <div class="input-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" name="name" class="form-control" placeholder="Nama lengkap" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor HP</label>
                    <div class="input-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 15 19.79 19.79 0 0 1 1.61 6.35C1.6 5.26 2.39 4.35 3.47 4h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 11.5A16 16 0 0 0 12.5 16.09l.72-.72a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 20 17.5l-.08-.58z"/></svg>
                        <input type="tel" name="phone" class="form-control" placeholder="08xx-xxxx-xxxx" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Email *</label>
                <div class="input-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <input type="email" name="email" class="form-control" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <div class="input-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" name="password" id="pass" class="form-control" placeholder="Min. 6 karakter" required oninput="checkStrength(this.value)">
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="strengthBar"></div></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password *</label>
                    <div class="input-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
                    </div>
                </div>
            </div>
            <div class="terms">
                <input type="checkbox" required>
                <span>Saya setuju dengan <a href="#">Syarat & Ketentuan</a> dan <a href="#">Kebijakan Privasi</a> WashWell</span>
            </div>
            <button type="submit" class="btn-submit">Buat Akun Sekarang 🚀</button>
        </form>
        <div class="login-link">Sudah punya akun? <a href="login.php">Masuk di sini</a></div>
    </div>
</div>
<script>
function checkStrength(v) {
    const bar = document.getElementById('strengthBar');
    let s = 0;
    if (v.length >= 6) s++;
    if (v.length >= 10) s++;
    if (/[A-Z]/.test(v)) s++;
    if (/[0-9]/.test(v)) s++;
    if (/[^A-Za-z0-9]/.test(v)) s++;
    const w = (s / 5) * 100;
    const colors = ['#EF4444','#F97316','#EAB308','#22C55E','#16A34A'];
    bar.style.width = w + '%';
    bar.style.background = colors[Math.min(s-1, 4)] || '#EF4444';
}
</script>
</body>
</html>
