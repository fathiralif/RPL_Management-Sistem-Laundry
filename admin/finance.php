<?php
require_once '../includes/auth.php';
requireAdminOrStaff('../pages/login.php');
$db = getDB();
$user = getCurrentUser();

// Stats
$totalRevenue  = $db->query("SELECT COALESCE(SUM(amount),0) as r FROM transactions WHERE status='success'")->fetch_assoc()['r'];
$todayRevenue  = $db->query("SELECT COALESCE(SUM(amount),0) as r FROM transactions WHERE status='success' AND DATE(created_at)=CURDATE()")->fetch_assoc()['r'];
$monthRevenue  = $db->query("SELECT COALESCE(SUM(amount),0) as r FROM transactions WHERE status='success' AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetch_assoc()['r'];
$pendingPayment = $db->query("SELECT COALESCE(SUM(amount),0) as r FROM orders WHERE payment_status='unpaid' AND status NOT IN ('cancelled')")->fetch_assoc()['r'];
$totalTrx = $db->query("SELECT COUNT(*) as c FROM transactions WHERE status='success'")->fetch_assoc()['c'];

// Monthly chart data (last 6 months)
$chartData = [];
for ($i=5; $i>=0; $i--) {
    $row = $db->query("SELECT COALESCE(SUM(amount),0) as rev, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL $i MONTH),'%b') as lbl FROM transactions WHERE status='success' AND YEAR(created_at)=YEAR(DATE_SUB(CURDATE(),INTERVAL $i MONTH)) AND MONTH(created_at)=MONTH(DATE_SUB(CURDATE(),INTERVAL $i MONTH))")->fetch_assoc();
    $chartData[] = $row;
}
$maxRev = max(array_column($chartData,'rev')) ?: 1;

// Recent transactions
$transactions = $db->query("SELECT t.*,o.order_code,u.name as customer_name,s.name as service_name FROM transactions t JOIN orders o ON t.order_id=o.id JOIN users u ON o.user_id=u.id JOIN services s ON o.service_id=s.id ORDER BY t.created_at DESC LIMIT 15");

// Payment method breakdown
$methods = $db->query("SELECT payment_method, COUNT(*) as cnt, SUM(amount) as total FROM transactions WHERE status='success' GROUP BY payment_method");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Keuangan - WashWell Admin</title>
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
                <div class="topbar-title">Keuangan</div>
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
            <!-- HERO -->
            <div class="page-hero animate-in" style="background:linear-gradient(135deg,#16A34A 0%,#059669 50%,#0D9488 100%)">
                <img src="../assets/img/bubble-deco.svg" class="page-hero-img" alt="">
                <div class="page-hero-title">💰 Laporan Keuangan</div>
                <div class="page-hero-sub">Pantau pendapatan, transaksi, dan analisis finansial laundry</div>
            </div>

            <!-- METRIC ROW -->
            <div class="metric-row">
                <div class="metric-card animate-in">
                    <div class="metric-icon" style="background:var(--green-light);color:var(--green)">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div class="metric-value" style="color:var(--green)"><?= formatRupiah($totalRevenue) ?></div>
                    <div class="metric-label">Total Pendapatan</div>
                    <div class="metric-trend up">↑ Semua waktu</div>
                </div>
                <div class="metric-card animate-in">
                    <div class="metric-icon" style="background:#EFF6FF;color:var(--primary)">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                    </div>
                    <div class="metric-value" style="color:var(--primary)"><?= formatRupiah($monthRevenue) ?></div>
                    <div class="metric-label">Bulan Ini</div>
                    <div class="metric-trend up">↑ Bulan berjalan</div>
                </div>
                <div class="metric-card animate-in">
                    <div class="metric-icon" style="background:#FEF3C7;color:#D97706">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="metric-value" style="color:#D97706"><?= formatRupiah($todayRevenue) ?></div>
                    <div class="metric-label">Pendapatan Hari Ini</div>
                    <div class="metric-trend up">↑ <?= date('d M Y') ?></div>
                </div>
                <div class="metric-card animate-in">
                    <div class="metric-icon" style="background:#FEE2E2;color:#EF4444">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    </div>
                    <div class="metric-value" style="color:#EF4444"><?= formatRupiah($pendingPayment) ?></div>
                    <div class="metric-label">Belum Dibayar</div>
                    <div class="metric-trend down">↓ Perlu ditagih</div>
                </div>
            </div>

            <!-- CHART + PAYMENT METHOD -->
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:24px;">
                <div class="card animate-in">
                    <div class="card-header">
                        <span class="card-title">📈 Grafik Pendapatan 6 Bulan</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" id="revenueChart">
                            <!-- Grid lines -->
                            <div class="chart-grid-line" style="top:20%"><span class="chart-y-label"><?= formatRupiah($maxRev*0.8) ?></span></div>
                            <div class="chart-grid-line" style="top:40%"><span class="chart-y-label"><?= formatRupiah($maxRev*0.6) ?></span></div>
                            <div class="chart-grid-line" style="top:60%"><span class="chart-y-label"><?= formatRupiah($maxRev*0.4) ?></span></div>
                            <div class="chart-grid-line" style="top:80%"><span class="chart-y-label"><?= formatRupiah($maxRev*0.2) ?></span></div>
                            <div class="chart-bars">
                                <?php foreach ($chartData as $d): $pct = $maxRev > 0 ? ($d['rev']/$maxRev)*100 : 5; ?>
                                <div class="chart-bar-wrap">
                                    <div class="chart-bar" data-height="<?= max(5,$pct) ?>" style="height:0%" title="<?= formatRupiah($d['rev']) ?>">
                                        <div style="position:absolute;bottom:calc(100%+4px);left:50%;transform:translateX(-50%);font-size:10px;font-weight:700;color:var(--primary);white-space:nowrap;opacity:0;transition:opacity 0.2s" class="bar-tooltip"><?= formatRupiah($d['rev']) ?></div>
                                    </div>
                                    <div class="chart-bar-label"><?= $d['lbl'] ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card animate-in">
                    <div class="card-header">
                        <span class="card-title">💳 Metode Pembayaran</span>
                    </div>
                    <div class="card-body">
                        <?php $methods->data_seek(0); while ($m = $methods->fetch_assoc()): 
                            $icons = ['cash'=>'💵','transfer'=>'🏦','e-wallet'=>'📱'];
                            $colors = ['cash'=>'var(--green)','transfer'=>'var(--primary)','e-wallet'=>'var(--orange)'];
                            $pct2 = $totalRevenue > 0 ? round(($m['total']/$totalRevenue)*100) : 0;
                        ?>
                        <div style="margin-bottom:16px;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                                <span style="font-size:13px;font-weight:600"><?= $icons[$m['payment_method']] ?? '💳' ?> <?= ucfirst($m['payment_method']) ?></span>
                                <span style="font-size:12.5px;color:var(--gray-500)"><?= $m['cnt'] ?> trx · <?= $pct2 ?>%</span>
                            </div>
                            <div style="height:8px;background:var(--gray-100);border-radius:8px;overflow:hidden;">
                                <div style="height:100%;width:<?= $pct2 ?>%;background:<?= $colors[$m['payment_method']] ?? 'var(--primary)' ?>;border-radius:8px;transition:width 0.8s ease;" class="progress-bar"></div>
                            </div>
                            <div style="font-size:12px;font-weight:700;color:<?= $colors[$m['payment_method']] ?? 'var(--primary)' ?>;margin-top:4px;"><?= formatRupiah($m['total']) ?></div>
                        </div>
                        <?php endwhile; ?>
                        <div style="padding-top:12px;border-top:1px solid var(--gray-100);text-align:center;">
                            <div style="font-size:12px;color:var(--gray-500)">Total <?= $totalTrx ?> transaksi berhasil</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TRANSACTION TABLE -->
            <div class="card animate-in">
                <div class="card-header">
                    <span class="card-title">🧾 Riwayat Transaksi</span>
                    <a href="?export=1" class="btn btn-ghost btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Export
                    </a>
                </div>
                <div class="card-body" style="padding-top:16px;">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kode Transaksi</th>
                                    <th>Order</th>
                                    <th>Pelanggan</th>
                                    <th>Layanan</th>
                                    <th>Metode</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($transactions && $transactions->num_rows > 0): ?>
                                <?php while ($t = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td><span style="font-family:monospace;font-weight:700;color:var(--gray-700)"><?= $t['transaction_code'] ?></span></td>
                                    <td><span style="font-family:monospace;color:var(--primary);font-weight:600"><?= $t['order_code'] ?></span></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <div class="avatar avatar-sm"><?= strtoupper(substr($t['customer_name'],0,1)) ?></div>
                                            <?= htmlspecialchars($t['customer_name']) ?>
                                        </div>
                                    </td>
                                    <td style="font-size:12.5px"><?= htmlspecialchars($t['service_name']) ?></td>
                                    <td>
                                        <?php $methodIcons=['cash'=>'💵','transfer'=>'🏦','e-wallet'=>'📱']; ?>
                                        <span style="font-size:12.5px"><?= $methodIcons[$t['payment_method']] ?? '💳' ?> <?= ucfirst($t['payment_method']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge" style="<?= $t['status']==='success'?'background:var(--green-light);color:#166534':'background:#FEF3C7;color:#92400E' ?>">
                                            <?= $t['status']==='success'?'✅ Berhasil':'⏳ Pending' ?>
                                        </span>
                                    </td>
                                    <td style="color:var(--gray-500);font-size:12px"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                    <td><strong style="color:var(--green)"><?= formatRupiah($t['amount']) ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400)">Belum ada transaksi</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('open');}

// Animate chart bars on load
window.addEventListener('load', () => {
    setTimeout(() => {
        document.querySelectorAll('.chart-bar').forEach((bar, i) => {
            const h = bar.getAttribute('data-height');
            setTimeout(() => {
                bar.style.height = h + '%';
            }, i * 80);
            bar.addEventListener('mouseenter', () => bar.querySelector('.bar-tooltip').style.opacity = '1');
            bar.addEventListener('mouseleave', () => bar.querySelector('.bar-tooltip').style.opacity = '0');
        });
    }, 300);
    // Animate progress bars
    document.querySelectorAll('.progress-bar').forEach(b => {
        const w = b.style.width;
        b.style.width = '0';
        setTimeout(() => b.style.width = w, 400);
    });
});
</script>
</body>
</html>
