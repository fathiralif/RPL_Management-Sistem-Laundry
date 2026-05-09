<?php
require_once '../includes/auth.php';
requireRole('customer', '../pages/login.php');
$db = getDB();
$user = getCurrentUser();
$uid = (int)$user['id'];

$msg = ''; $msgType = '';
$orderSuccess = null;

// Jenis pakaian yang tersedia
$clothTypes = [
    ['id'=>'reguler','label'=>'Reguler','icon'=>'👕','desc'=>'Kaos, kemeja biasa'],
    ['id'=>'katun','label'=>'Katun','icon'=>'🧥','desc'=>'Bahan katun halus'],
    ['id'=>'jas','label'=>'Jas/Blazer','icon'=>'🥼','desc'=>'Jas, blazer, coat'],
    ['id'=>'sutra','label'=>'Sutra','icon'=>'✨','desc'=>'Bahan sutra & silk'],
    ['id'=>'jeans','label'=>'Jeans','icon'=>'👖','desc'=>'Celana & jaket jeans'],
    ['id'=>'batik','label'=>'Batik','icon'=>'🎨','desc'=>'Batik & kain motif'],
    ['id'=>'wool','label'=>'Wool','icon'=>'🧶','desc'=>'Sweater & bahan wool'],
    ['id'=>'lainnya','label'=>'Lainnya','icon'=>'🧺','desc'=>'Jenis pakaian lain'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid      = (int)$_POST['service_id'];
    $pickup   = trim($_POST['pickup_date']);
    $notes    = trim($_POST['notes'] ?? '');
    $delivery_type = $_POST['delivery_type'] ?? 'pickup';
    $address  = trim($_POST['delivery_address'] ?? $user['address'] ?? '');
