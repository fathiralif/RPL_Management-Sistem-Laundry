<?php
// Admin sidebar partial - include at top of admin pages
$currentUser = getCurrentUser();
$notifCount = getUnreadNotifs($currentUser['id']);
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$navItems = [
    ['page'=>'dashboard','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>','label'=>'Dashboard','url'=>'dashboard.php'],
    ['page'=>'orders','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>','label'=>'Pesanan','url'=>'orders.php'],
    ['page'=>'customers','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>','label'=>'Pelanggan','url'=>'customers.php'],
    ['page'=>'finance','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>','label'=>'Keuangan','url'=>'finance.php'],
    ['page'=>'services','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M21 12h-2M17.66 17.66l-1.41-1.41M12 19v2M6.34 17.66l1.41-1.41M3 12H1M4.93 4.93l1.41 1.41M12 3V1"/></svg>','label'=>'Layanan','url'=>'services.php'],
];
if ($currentUser['role'] === 'admin') {
    $navItems[] = ['page'=>'staff','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>','label'=>'Staff','url'=>'staff.php'];
}
$navItems[] = ['page'=>'settings','icon'=>'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M21 12h-2M17.66 17.66l-1.41-1.41M12 19v2M6.34 17.66l1.41-1.41M3 12H1M4.93 4.93l1.41 1.41M12 3V1"/></svg>','label'=>'Pengaturan','url'=>'settings.php'];

$initials = strtoupper(substr($currentUser['name'],0,1));
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
        <div class="nav-label">Menu Utama</div>
        <?php foreach ($navItems as $item): ?>
        <a href="<?= $item['url'] ?>" class="nav-item <?= $currentPage === $item['page'] ? 'active' : '' ?>">
            <?= $item['icon'] ?>
            <?= $item['label'] ?>
            <?php if ($item['page']==='orders' && $notifCount > 0): ?>
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