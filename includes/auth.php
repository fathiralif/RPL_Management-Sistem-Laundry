<?php
require_once __DIR__ . '/config.php';
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $id = (int)$_SESSION['user_id'];
    $res = $db->query("SELECT * FROM users WHERE id=$id LIMIT 1");
    return $res ? $res->fetch_assoc() : null;
}

function requireLogin($redirect = '../index.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

function requireRole($role, $redirect = '../index.php') {
    requireLogin($redirect);
    $user = getCurrentUser();
    if (!$user || $user['role'] !== $role) {
        header("Location: $redirect");
        exit;
    }
}

function requireAdmin($redirect = '../index.php') {
    requireLogin($redirect);
    $user = getCurrentUser();
    if (!$user || $user['role'] !== 'admin') {
        header("Location: $redirect");
        exit;
    }
}

function requireAdminOrStaff($redirect = '../index.php') {
    requireLogin($redirect);
    $user = getCurrentUser();
    if (!$user || !in_array($user['role'], ['admin','staff'])) {
        header("Location: $redirect");
        exit;
    }
}

function logout() {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

function getUnreadNotifs($userId) {
    $db = getDB();
    $uid = (int)$userId;
    $res = $db->query("SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$uid AND is_read=0");
    return $res ? (int)$res->fetch_assoc()['cnt'] : 0;
}
?>