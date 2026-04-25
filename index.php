<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WashWell - Laundry Modern & Terpercaya</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #2563EB; --primary-dark: #1D4ED8; --primary-light: #EFF6FF;
    --orange: #F97316; --green: #22C55E; --teal: #06B6D4;
    --gray-50: #F8FAFC; --gray-100: #F1F5F9; --gray-200: #E2E8F0;
    --gray-300: #CBD5E1; --gray-400: #94A3B8; --gray-500: #64748B;
    --gray-600: #475569; --gray-700: #334155; --gray-800: #1E293B;
    --gray-900: #0F172A; --white: #fff;
    --font: 'Plus Jakarta Sans', sans-serif;
    --font-display: 'Sora', sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body { font-family: var(--font); color: var(--gray-800); background: var(--white); overflow-x: hidden; }
a { text-decoration: none; color: inherit; }

/* NAVBAR */
nav {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    padding: 0 5%;
    height: 72px;
    display: flex; align-items: center; justify-content: space-between;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(226,232,240,0.8);
    transition: all 0.3s;
}
.nav-logo { display: flex; align-items: center; gap: 10px; }
.nav-logo .logo-box { width: 38px; height: 38px; background: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.nav-logo span { font-family: var(--font-display); font-size: 20px; font-weight: 700; color: var(--gray-900); }
.nav-links { display: flex; align-items: center; gap: 32px; }
.nav-links a { font-size: 14px; font-weight: 500; color: var(--gray-600); transition: color 0.2s; }
.nav-links a:hover { color: var(--primary); }
.nav-actions { display: flex; align-items: center; gap: 10px; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; font-family: var(--font); }
.btn-outline { background: transparent; border: 1.5px solid var(--gray-300); color: var(--gray-700); }
.btn-outline:hover { border-color: var(--primary); color: var(--primary); }
.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
.btn-lg { padding: 14px 32px; font-size: 16px; border-radius: 12px; }
.btn-orange { background: var(--orange); color: white; }
.btn-orange:hover { background: #EA6C0A; transform: translateY(-1px); }

/* HAMBURGER */
.hamburger { display: none; flex-direction: column; gap: 5px; background: none; border: none; cursor: pointer; padding: 6px; }
.hamburger span { display: block; width: 22px; height: 2px; background: var(--gray-700); border-radius: 2px; transition: all 0.3s; }
.mobile-menu { display: none; position: fixed; top: 72px; left: 0; right: 0; background: white; border-bottom: 1px solid var(--gray-200); padding: 20px 5%; z-index: 99; flex-direction: column; gap: 14px; }
.mobile-menu.open { display: flex; }
.mobile-menu a { font-size: 15px; font-weight: 500; color: var(--gray-700); padding: 8px 0; border-bottom: 1px solid var(--gray-100); }

/* HERO */
.hero {
    min-height: 100vh;
    display: flex; align-items: center;
    padding: 72px 5% 0;
    background: linear-gradient(135deg, #F0F7FF 0%, #EFF6FF 40%, #F0FDF4 100%);
    position: relative; overflow: hidden;
}
.hero-bg {
    position: absolute; inset: 0;
    background-image:
        radial-gradient(circle at 20% 80%, rgba(37,99,235,0.08) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(34,197,94,0.06) 0%, transparent 50%),
        radial-gradient(circle at 50% 50%, rgba(6,182,212,0.05) 0%, transparent 60%);
}
.hero-shapes { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.shape {
    position: absolute; border-radius: 50%;
    animation: float 6s ease-in-out infinite;
}
.shape-1 { width: 300px; height: 300px; background: rgba(37,99,235,0.06); top: 10%; right: 5%; animation-delay: 0s; }
.shape-2 { width: 200px; height: 200px; background: rgba(34,197,94,0.07); bottom: 20%; right: 20%; animation-delay: 2s; }
.shape-3 { width: 150px; height: 150px; background: rgba(249,115,22,0.07); top: 30%; left: 2%; animation-delay: 4s; }
@keyframes float { 0%,100% { transform: translateY(0) rotate(0); } 50% { transform: translateY(-20px) rotate(5deg); } }

.hero-inner { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; max-width: 1200px; margin: 0 auto; width: 100%; padding: 80px 0; position: relative; z-index: 1; }

.hero-badge { display: inline-flex; align-items: center; gap: 8px; background: var(--primary-light); color: var(--primary); padding: 7px 16px; border-radius: 30px; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
.hero-badge span.dot { width: 8px; height: 8px; border-radius: 50%; background: var(--primary); animation: pulse 2s infinite; }
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

.hero-title { font-family: var(--font-display); font-size: clamp(36px, 5vw, 56px); font-weight: 800; line-height: 1.1; color: var(--gray-900); margin-bottom: 20px; }
.hero-title .highlight { color: var(--primary); position: relative; }
.hero-title .highlight::after { content: ''; position: absolute; bottom: 2px; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--primary), var(--teal)); border-radius: 2px; opacity: 0.3; }
.hero-subtitle { font-size: 16px; color: var(--gray-500); line-height: 1.7; margin-bottom: 36px; max-width: 460px; }
.hero-actions { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.hero-stats { display: flex; align-items: center; gap: 24px; margin-top: 40px; padding-top: 32px; border-top: 1px solid var(--gray-200); flex-wrap: wrap; }
.hero-stat-item { }
.hero-stat-num { font-family: var(--font-display); font-size: 24px; font-weight: 800; color: var(--gray-900); }
.hero-stat-label { font-size: 12px; color: var(--gray-500); }

.hero-visual { position: relative; }
.dashboard-preview {
    background: white;
    border-radius: 20px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.12), 0 0 0 1px rgba(0,0,0,0.04);
    overflow: hidden;
    animation: slideUp 0.8s ease 0.2s both;
}
@keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
.preview-topbar { background: var(--gray-50); padding: 12px 16px; display: flex; align-items: center; gap: 6px; border-bottom: 1px solid var(--gray-200); }
.preview-dot { width: 10px; height: 10px; border-radius: 50%; }
.preview-content { padding: 20px; }
.preview-title { font-family: var(--font-display); font-size: 14px; font-weight: 700; margin-bottom: 14px; color: var(--gray-800); }
.mini-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 14px; }
.mini-stat { background: var(--gray-50); border-radius: 10px; padding: 10px; text-align: center; border: 1px solid var(--gray-100); }
.mini-stat-num { font-weight: 800; font-size: 18px; margin-bottom: 2px; }
.mini-stat-label { font-size: 10px; color: var(--gray-500); }
.mini-table { font-size: 11px; }
.mini-table-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 8px; border-radius: 6px; gap: 8px; }
.mini-table-row:nth-child(odd) { background: var(--gray-50); }
.mini-badge { padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; }
.mini-badge-orange { background: #FEF3C7; color: #92400E; }
.mini-badge-green { background: #DCFCE7; color: #166534; }
.mini-badge-blue { background: #EFF6FF; color: #1D4ED8; }

.floating-card {
    position: absolute;
    background: white;
    border-radius: 14px;
    padding: 12px 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    font-size: 12px;
    border: 1px solid var(--gray-100);
    animation: float 5s ease-in-out infinite;
}
.float-card-1 { top: -20px; left: -40px; animation-delay: 1s; }
.float-card-2 { bottom: 40px; right: -50px; animation-delay: 3s; }
.float-card-label { font-weight: 600; color: var(--gray-600); margin-bottom: 2px; }
.float-card-value { font-family: var(--font-display); font-weight: 800; font-size: 18px; }

/* SERVICES */
.section { padding: 100px 5%; }
.section-center { text-align: center; max-width: 600px; margin: 0 auto 60px; }
.section-badge { display: inline-block; background: var(--primary-light); color: var(--primary); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 14px; }
.section-title { font-family: var(--font-display); font-size: clamp(28px, 4vw, 40px); font-weight: 800; color: var(--gray-900); margin-bottom: 14px; line-height: 1.2; }
.section-subtitle { font-size: 15px; color: var(--gray-500); line-height: 1.7; }
.section-bg { background: var(--gray-50); }

.services-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; max-width: 1200px; margin: 0 auto; }
.service-card {
    background: white; border-radius: 18px; padding: 28px;
    border: 1px solid var(--gray-200);
    transition: all 0.3s;
    position: relative; overflow: hidden;
}
.service-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); border-color: var(--primary); }
.service-card::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--primary), var(--teal)); opacity: 0; transition: opacity 0.3s; }
.service-card:hover::after { opacity: 1; }
.service-icon { width: 52px; height: 52px; border-radius: 14px; background: var(--primary-light); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; font-size: 24px; }
.service-name { font-family: var(--font-display); font-size: 17px; font-weight: 700; color: var(--gray-900); margin-bottom: 8px; }
.service-desc { font-size: 13.5px; color: var(--gray-500); line-height: 1.6; margin-bottom: 16px; }
.service-price { font-family: var(--font-display); font-size: 20px; font-weight: 800; color: var(--primary); }
.service-price span { font-size: 13px; font-weight: 400; color: var(--gray-400); }

/* HOW IT WORKS */
.steps-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 30px; max-width: 1000px; margin: 0 auto; }
.step { text-align: center; position: relative; }
.step-num { width: 56px; height: 56px; border-radius: 18px; background: var(--primary); color: white; font-family: var(--font-display); font-size: 22px; font-weight: 800; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; }
.step-title { font-family: var(--font-display); font-size: 16px; font-weight: 700; margin-bottom: 8px; color: var(--gray-800); }
.step-desc { font-size: 13.5px; color: var(--gray-500); line-height: 1.6; }
.step-arrow { position: absolute; top: 28px; right: -20px; color: var(--gray-300); font-size: 20px; }

/* TESTIMONIALS */
.testimonials-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; max-width: 1100px; margin: 0 auto; }
.testi-card { background: white; border-radius: 18px; padding: 28px; border: 1px solid var(--gray-200); }
.testi-stars { color: #F59E0B; font-size: 16px; margin-bottom: 14px; }
.testi-text { font-size: 14px; color: var(--gray-600); line-height: 1.7; margin-bottom: 20px; font-style: italic; }
.testi-author { display: flex; align-items: center; gap: 12px; }
.testi-avatar { width: 42px; height: 42px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 15px; }
.testi-name { font-weight: 700; font-size: 14px; color: var(--gray-800); }
.testi-loc { font-size: 12px; color: var(--gray-400); }

/* CTA */
.cta-section {
    padding: 100px 5%;
    background: linear-gradient(135deg, var(--primary) 0%, #1D4ED8 50%, #1E40AF 100%);
    text-align: center; position: relative; overflow: hidden;
}
.cta-section::before { content:''; position:absolute; inset:0; background-image: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.08) 0%, transparent 50%), radial-gradient(circle at 80% 50%, rgba(255,255,255,0.05) 0%, transparent 50%); }
.cta-inner { position: relative; z-index: 1; max-width: 600px; margin: 0 auto; }
.cta-title { font-family: var(--font-display); font-size: clamp(30px, 4vw, 44px); font-weight: 800; color: white; margin-bottom: 16px; }
.cta-subtitle { font-size: 16px; color: rgba(255,255,255,0.8); margin-bottom: 36px; }
.cta-actions { display: flex; align-items: center; justify-content: center; gap: 14px; flex-wrap: wrap; }
.btn-white { background: white; color: var(--primary); }
.btn-white:hover { background: var(--gray-100); transform: translateY(-1px); }
.btn-outline-white { background: transparent; border: 2px solid rgba(255,255,255,0.5); color: white; }
.btn-outline-white:hover { border-color: white; background: rgba(255,255,255,0.1); }

/* FOOTER */
footer {
    background: var(--gray-900);
    color: var(--gray-400);
    padding: 60px 5% 30px;
}
.footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 40px; }
.footer-brand .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
.footer-brand .logo span { font-family: var(--font-display); font-size: 18px; font-weight: 700; color: white; }
.footer-brand p { font-size: 13.5px; line-height: 1.7; max-width: 260px; }
.footer-col h4 { color: white; font-weight: 700; font-size: 14px; margin-bottom: 16px; }
.footer-col ul { list-style: none; }
.footer-col ul li { margin-bottom: 10px; }
.footer-col ul li a { font-size: 13.5px; color: var(--gray-400); transition: color 0.2s; }
.footer-col ul li a:hover { color: var(--primary-light, #93C5FD); }
.footer-bottom { border-top: 1px solid var(--gray-700); padding-top: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: gap; gap: 12px; }
.footer-bottom p { font-size: 13px; }
.footer-socials { display: flex; gap: 10px; }
.social-btn { width: 34px; height: 34px; border-radius: 8px; background: var(--gray-800); display: flex; align-items: center; justify-content: center; color: var(--gray-400); transition: all 0.2s; font-size: 14px; }
.social-btn:hover { background: var(--primary); color: white; }

/* RESPONSIVE */
@media (max-width: 768px) {
    .nav-links, .nav-actions { display: none; }
    .hamburger { display: flex; }
    .hero-inner { grid-template-columns: 1fr; gap: 40px; text-align: center; }
    .hero-visual { display: none; }
    .hero-subtitle { margin: 0 auto 36px; }
    .hero-actions { justify-content: center; }
    .hero-stats { justify-content: center; }
    .footer-grid { grid-template-columns: 1fr 1fr; gap: 30px; }
}
@media (max-width: 480px) {
    .footer-grid { grid-template-columns: 1fr; }
    .step-arrow { display: none; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav>
    <div class="nav-logo">
        <div class="logo-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <span>WashWell</span>
    </div>
    <div class="nav-links">
        <a href="#layanan">Layanan</a>
        <a href="#cara-kerja">Cara Kerja</a>
        <a href="#testimoni">Testimoni</a>
        <a href="#kontak">Kontak</a>
    </div>
    <div class="nav-actions">
        <a href="pages/login.php" class="btn btn-outline">Masuk</a>
        <a href="pages/register.php" class="btn btn-primary">Daftar Gratis</a>
    </div>
    <button class="hamburger" onclick="toggleMenu()">
        <span></span><span></span><span></span>
    </button>
</nav>

<!-- MOBILE MENU -->
<div class="mobile-menu" id="mobileMenu">
    <a href="#layanan">Layanan</a>
    <a href="#cara-kerja">Cara Kerja</a>
    <a href="#testimoni">Testimoni</a>
    <a href="pages/login.php">Masuk</a>
    <a href="pages/register.php" style="color:var(--primary);font-weight:700;">Daftar Gratis →</a>
</div>

<!-- HERO -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="hero-inner">
        <div class="hero-text" style="animation: slideUp 0.6s ease both">
            <div class="hero-badge">
                <span class="dot"></span>
                #1 Platform Laundry Digital Indonesia
            </div>
            <h1 class="hero-title">
                Laundry Bersih & <span class="highlight">Praktis</span> di Ujung Jari
            </h1>
            <p class="hero-subtitle">
                WashWell hadir dengan sistem manajemen laundry modern. Pantau pesanan, kelola pelanggan, dan tingkatkan bisnis laundry Anda dengan teknologi terkini.
            </p>
            <div class="hero-actions">
                <a href="pages/register.php" class="btn btn-primary btn-lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    Mulai Sekarang
                </a>
                <a href="#cara-kerja" class="btn btn-outline btn-lg">Pelajari Lebih Lanjut</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat-item">
                    <div class="hero-stat-num">5.000+</div>
                    <div class="hero-stat-label">Pelanggan Aktif</div>
                </div>
                <div style="width:1px;height:40px;background:var(--gray-200)"></div>
                <div class="hero-stat-item">
                    <div class="hero-stat-num">50.000+</div>
                    <div class="hero-stat-label">Pesanan Selesai</div>
                </div>
                <div style="width:1px;height:40px;background:var(--gray-200)"></div>
                <div class="hero-stat-item">
                    <div class="hero-stat-num">4.9 ⭐</div>
                    <div class="hero-stat-label">Rating Kepuasan</div>
                </div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="floating-card float-card-1">
                <div class="float-card-label">Pesanan Hari Ini</div>
                <div class="float-card-value" style="color:var(--primary)">42</div>
            </div>
            <div class="dashboard-preview">
                <div class="preview-topbar">
                    <div class="preview-dot" style="background:#FF5F57"></div>
                    <div class="preview-dot" style="background:#FFBD2E"></div>
                    <div class="preview-dot" style="background:#28CA41"></div>
                    <span style="font-size:11px;color:var(--gray-400);margin-left:8px;font-family:monospace">washwell.com/dashboard</span>
                </div>
                <div class="preview-content">
                    <div class="preview-title">📊 Dashboard WashWell</div>
                    <div class="mini-stats">
                        <div class="mini-stat">
                            <div class="mini-stat-num" style="color:var(--orange)">250</div>
                            <div class="mini-stat-label">Total Orders</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-num" style="color:var(--primary)">68</div>
                            <div class="mini-stat-label">In Progress</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-num" style="color:var(--green)">85</div>
                            <div class="mini-stat-label">Ready</div>
                        </div>
                    </div>
                    <div class="mini-table">
                        <div class="mini-table-row">
                            <span style="font-weight:600;">ORD-02001</span>
                            <span>John M.</span>
                            <span class="mini-badge mini-badge-orange">Pending</span>
                            <span style="font-weight:700">Rp50.000</span>
                        </div>
                        <div class="mini-table-row">
                            <span style="font-weight:600;">ORD-02002</span>
                            <span>Customer</span>
                            <span class="mini-badge mini-badge-green">Ready</span>
                            <span style="font-weight:700">Rp30.000</span>
                        </div>
                        <div class="mini-table-row">
                            <span style="font-weight:600;">ORD-02003</span>
                            <span>John M.</span>
                            <span class="mini-badge mini-badge-blue">Progress</span>
                            <span style="font-weight:700">Rp10.000</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="floating-card float-card-2">
                <div class="float-card-label">Pendapatan Bulan Ini</div>
                <div class="float-card-value" style="color:var(--green)">Rp12,4 Jt</div>
            </div>
        </div>
    </div>
</section>

<!-- SERVICES -->
<section class="section section-bg" id="layanan">
    <div class="section-center">
        <div class="section-badge">Layanan Kami</div>
        <h2 class="section-title">Solusi Cuci Lengkap untuk Semua Kebutuhan</h2>
        <p class="section-subtitle">Kami menyediakan berbagai layanan laundry profesional dengan harga terjangkau dan hasil terjamin.</p>
    </div>
    <div class="services-grid">
        <?php
        $services = [
            ['icon'=>'🫧','name'=>'Cuci Reguler','desc'=>'Cuci bersih dengan deterjen premium berkualitas tinggi untuk pakaian sehari-hari.','price'=>'Rp 7.000','unit'=>'/kg'],
            ['icon'=>'⚡','name'=>'Cuci Express','desc'=>'Layanan kilat 6 jam untuk kebutuhan mendesak Anda. Bersih, cepat, sempurna.','price'=>'Rp 12.000','unit'=>'/kg'],
            ['icon'=>'👔','name'=>'Cuci + Setrika','desc'=>'Lengkap cuci bersih dan setrika rapi, pakaian siap pakai langsung dari laundry.','price'=>'Rp 10.000','unit'=>'/kg'],
            ['icon'=>'✨','name'=>'Dry Cleaning','desc'=>'Dry cleaning profesional untuk pakaian delicate, jas, gaun, dan busana khusus.','price'=>'Rp 25.000','unit'=>'/kg'],
            ['icon'=>'👟','name'=>'Cuci Sepatu','desc'=>'Bersihkan sepatu kesayangan Anda hingga seperti baru. Nyaman dan wangi.','price'=>'Rp 35.000','unit'=>'/pasang'],
            ['icon'=>'🏠','name'=>'Cuci Karpet','desc'=>'Cuci karpet dan permadani berbagai ukuran dengan hasil bersih dan segar.','price'=>'Rp 15.000','unit'=>'/kg'],
        ];
        foreach ($services as $s): ?>
        <div class="service-card">
            <div class="service-icon"><?= $s['icon'] ?></div>
            <div class="service-name"><?= $s['name'] ?></div>
            <div class="service-desc"><?= $s['desc'] ?></div>
            <div class="service-price"><?= $s['price'] ?> <span><?= $s['unit'] ?></span></div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="section" id="cara-kerja">
    <div class="section-center">
        <div class="section-badge">Cara Kerja</div>
        <h2 class="section-title">Mudah & Cepat dalam 4 Langkah</h2>
        <p class="section-subtitle">Proses pemesanan laundry yang simpel dan transparan dari awal hingga akhir.</p>
    </div>
    <div class="steps-grid">
        <div class="step">
            <div class="step-num">1</div>
            <div class="step-title">Daftar Akun</div>
            <div class="step-desc">Buat akun WashWell gratis dalam hitungan menit. Tanpa biaya pendaftaran.</div>
            <div class="step-arrow">→</div>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <div class="step-title">Buat Pesanan</div>
            <div class="step-desc">Pilih layanan, masukkan berat cucian, dan tentukan tanggal pickup yang diinginkan.</div>
            <div class="step-arrow">→</div>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <div class="step-title">Kami Proses</div>
            <div class="step-desc">Tim laundry profesional kami menangani cucian dengan standar kebersihan terbaik.</div>
            <div class="step-arrow">→</div>
        </div>
        <div class="step">
            <div class="step-num">4</div>
            <div class="step-title">Ambil / Antar</div>
            <div class="step-desc">Ambil sendiri atau kami antarkan ke rumah Anda. Bersih, rapi, dan harum.</div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="section section-bg" id="testimoni">
    <div class="section-center">
        <div class="section-badge">Testimoni</div>
        <h2 class="section-title">Apa Kata Pelanggan Kami?</h2>
    </div>
    <div class="testimonials-grid">
        <?php
        $testimonials = [
            ['name'=>'Sari Dewi','loc'=>'Palembang','stars'=>5,'text'=>'"Pelayanannya sangat memuaskan! Pakaian saya bersih dan wangi. Sistem trackingnya memudahkan saya memantau status cucian secara real-time."'],
            ['name'=>'Budi Hartono','loc'=>'Jakarta Selatan','stars'=>5,'text'=>'"WashWell benar-benar mengubah cara saya mengelola laundry. Laporan keuangan otomatis dan manajemen karyawan yang mudah sekali!"'],
            ['name'=>'Rina Sanjaya','loc'=>'Bandung','stars'=>5,'text'=>'"Express 6 jam beneran 6 jam! Baju kemeja kantor saya selalu rapi berkat WashWell. Harga terjangkau, kualitas premium."'],
            ['name'=>'Ahmad Fauzi','loc'=>'Surabaya','stars'=>5,'text'=>'"Sebagai pemilik laundry, fitur dashboard admin WashWell sangat membantu operasional sehari-hari. Sangat direkomendasikan!"'],
            ['name'=>'Maya Putri','loc'=>'Yogyakarta','stars'=>5,'text'=>'"Cuci karpet kesayangan saya hasilnya luar biasa! Bersih sempurna dan tidak ada bau apek sama sekali. Pasti balik lagi."'],
            ['name'=>'Dani Pratama','loc'=>'Medan','stars'=>5,'text'=>'"Notifikasi real-time sangat berguna! Saya tahu persis kapan cucian siap. Aplikasi yang benar-benar memudahkan hidup."'],
        ];
        foreach ($testimonials as $t):
            $initial = strtoupper(substr($t['name'], 0, 1));
        ?>
        <div class="testi-card">
            <div class="testi-stars"><?= str_repeat('⭐', $t['stars']) ?></div>
            <div class="testi-text"><?= $t['text'] ?></div>
            <div class="testi-author">
                <div class="testi-avatar" style="background:var(--primary)"><?= $initial ?></div>
                <div>
                    <div class="testi-name"><?= $t['name'] ?></div>
                    <div class="testi-loc">📍 <?= $t['loc'] ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="cta-inner">
        <h2 class="cta-title">Siap Mulai Gunakan WashWell?</h2>
        <p class="cta-subtitle">Bergabung dengan ribuan pelanggan yang sudah mempercayai WashWell untuk kebutuhan laundry mereka.</p>
        <div class="cta-actions">
            <a href="pages/register.php" class="btn btn-white btn-lg">
                🚀 Daftar Gratis Sekarang
            </a>
            <a href="pages/login.php" class="btn btn-outline-white btn-lg">
                Sudah Punya Akun? Masuk
            </a>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer id="kontak">
    <div class="footer-grid">
        <div class="footer-brand">
            <div class="logo">
                <div style="width:34px;height:34px;background:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                </div>
                <span>WashWell</span>
            </div>
            <p>Platform manajemen laundry modern yang memudahkan operasional bisnis laundry Anda dari mana saja.</p>
        </div>
        <div class="footer-col">
            <h4>Layanan</h4>
            <ul>
                <li><a href="#">Cuci Reguler</a></li>
                <li><a href="#">Cuci Express</a></li>
                <li><a href="#">Cuci + Setrika</a></li>
                <li><a href="#">Dry Cleaning</a></li>
                <li><a href="#">Cuci Sepatu</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Perusahaan</h4>
            <ul>
                <li><a href="#">Tentang Kami</a></li>
                <li><a href="#">Karir</a></li>
                <li><a href="#">Blog</a></li>
                <li><a href="#">Syarat & Ketentuan</a></li>
                <li><a href="#">Kebijakan Privasi</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Kontak</h4>
            <ul>
                <li><a href="#">📧 hello@washwell.com</a></li>
                <li><a href="#">📞 0800-WASHWELL</a></li>
                <li><a href="#">📍 Palembang, Indonesia</a></li>
                <li><a href="#">💬 Live Chat</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2024 WashWell. All rights reserved. Made with ❤️ in Indonesia.</p>
        <div class="footer-socials">
            <a href="#" class="social-btn">ig</a>
            <a href="#" class="social-btn">tw</a>
            <a href="#" class="social-btn">fb</a>
            <a href="#" class="social-btn">wa</a>
        </div>
    </div>
</footer>

<script>
function toggleMenu() {
    document.getElementById('mobileMenu').classList.toggle('open');
}
// Close menu on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('nav')) {
        document.getElementById('mobileMenu').classList.remove('open');
    }
});
// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        const target = document.querySelector(a.getAttribute('href'));
        if (target) target.scrollIntoView({ behavior: 'smooth' });
        document.getElementById('mobileMenu').classList.remove('open');
    });
});
// Animate on scroll
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = 'slideUp 0.5s ease both';
        }
    });
}, { threshold: 0.1 });
document.querySelectorAll('.service-card, .step, .testi-card').forEach(el => observer.observe(el));
</script>
</body>
</html>
