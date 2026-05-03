<?php
require_once '../includes/auth.php';
requireRole('customer', '../pages/login.php');
$db = getDB();
$user = getCurrentUser();
$uid = (int)$user['id'];

// Mark all as read
if (isset($_GET['mark_read'])) {
    $db->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");
    header('Location: notifications.php'); exit;
}
// Delete single
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete') {
    $nid = (int)$_POST['notif_id'];
    $db->query("DELETE FROM notifications WHERE id=$nid AND user_id=$uid");
    header('Location: notifications.php'); exit;
}

$notifs = $db->query("SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC");
$notifCount = getUnreadNotifs($uid);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifikasi - WashWell</title>
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
                <div class="topbar-title">Notifikasi</div>
            </div>
        </header>
        <main class="page-content">
            <div class="page-header">
                <div>
                    <h1>Notifikasi</h1>
                    <p><?= $notifCount ?> notifikasi belum dibaca</p>
                </div>
                <?php if ($notifCount > 0): ?>
                <a href="notifications.php?mark_read=1" class="btn btn-ghost btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Tandai Semua Dibaca
                </a>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-body" style="padding:8px;">
                <?php if ($notifs->num_rows === 0): ?>
                <div style="text-align:center;padding:60px 0;color:var(--gray-400);">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:0.4;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <p style="font-size:14px;">Belum ada notifikasi</p>
                </div>
                <?php else: ?>
                <?php while($n = $notifs->fetch_assoc()): ?>
                <div style="display:flex;align-items:flex-start;gap:14px;padding:16px;border-radius:10px;background:<?= !$n['is_read']?'var(--primary-bg)':'transparent' ?>;margin-bottom:4px;transition:background 0.2s;">
                    <div style="width:42px;height:42px;border-radius:12px;background:<?= !$n['is_read']?'var(--primary)':'var(--gray-100)' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:<?= !$n['is_read']?'white':'var(--gray-500)' ?>;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:13px;font-weight:700;color:var(--gray-800);margin-bottom:3px;">
                            <?= htmlspecialchars($n['title']) ?>
                            <?php if (!$n['is_read']): ?><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--primary);margin-left:6px;vertical-align:middle;"></span><?php endif; ?>
                        </div>
                        <div style="font-size:12px;color:var(--gray-600);margin-bottom:6px;"><?= htmlspecialchars($n['message']) ?></div>
                        <div style="font-size:11px;color:var(--gray-400);"><?= timeAgo($n['created_at']) ?></div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                        <button type="submit" style="background:none;border:none;color:var(--gray-400);cursor:pointer;padding:4px;" title="Hapus">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                        </button>
                    </form>
                </div>
                <?php endwhile; ?>
                <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('sidebarOverlay').classList.add('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('open');}
</script>
</body>
</html>
