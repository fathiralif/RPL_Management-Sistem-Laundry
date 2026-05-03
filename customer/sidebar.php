<?php
// Customer sidebar partial
$currentUser = getCurrentUser();
$notifCount = getUnreadNotifs($currentUser['id']);
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$navItems = [
    ['page'=>'dashboard','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>','label'=>'Dashboard','url'=>'dashboard.php'],
    ['page'=>'orders','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>','label'=>'Pesanan Saya','url'=>'orders.php'],
    ['page'=>'new_order','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>','label'=>'Buat Pesanan','url'=>'new_order.php'],
    ['page'=>'payments','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>','label'=>'Pembayaran','url'=>'payments.php'],
    ['page'=>'tracking','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>','label'=>'Lacak Pesanan','url'=>'tracking.php'],
    ['page'=>'notifications','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>','label'=>'Notifikasi','url'=>'notifications.php'],
    ['page'=>'profile','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>','label'=>'Profil Saya','url'=>'profile.php'],
];
?>
<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <span>WashWell</span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Menu Customer</div>
        <?php foreach ($navItems as $item): ?>
        <a href="<?= $item['url'] ?>" class="nav-item <?= $currentPage === $item['page'] ? 'active' : '' ?>">
            <?= $item['icon'] ?>
            <?= $item['label'] ?>
            <?php if ($item['page']==='notifications' && $notifCount > 0): ?>
            <span class="nav-badge"><?= $notifCount ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-item" style="color:#EF4444;" onclick="return confirm('Yakin ingin keluar?')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Keluar
        </a>
    </div>
</aside>
