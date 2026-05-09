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
    
   // Ambil items dari POST
    $itemTypes  = $_POST['item_type'] ?? [];
    $itemWeights = $_POST['item_weight'] ?? [];
    $itemNotes  = $_POST['item_note'] ?? [];

    // Validasi minimal 1 item
    $validItems = [];
    foreach ($itemTypes as $i => $type) {
        $w = max(0, (float)($itemWeights[$i] ?? 0));
        if ($type && $w > 0) {
            $validItems[] = [
                'type'   => $type,
                'weight' => $w,
                'note'   => trim($itemNotes[$i] ?? ''),
            ];
        }
    }

    if (!$sid || !$pickup) {
        $msg = 'Mohon lengkapi semua data yang diperlukan.'; $msgType = 'danger';
    } elseif (empty($validItems)) {
        $msg = 'Tambahkan minimal 1 item pakaian.'; $msgType = 'danger';
    } else {
        $svc = $db->query("SELECT * FROM services WHERE id=$sid AND is_active=1")->fetch_assoc();
        if (!$svc) {
            $msg = 'Layanan tidak ditemukan.'; $msgType = 'danger';
        } else {
            $totalWeight = array_sum(array_column($validItems, 'weight'));
            $amount = $svc['price_per_kg'] * $totalWeight;
            $duration = (int)$svc['duration_hours'];
            $delivery_date = date('Y-m-d', strtotime($pickup . ' + ' . $duration . ' hours'));
            $code = generateOrderCode();

            // Bangun ringkasan item untuk notes
            $itemSummary = implode(', ', array_map(fn($it) => ucfirst($it['type']).' ('.$it['weight'].'kg)'.($it['note']?': '.$it['note']:''), $validItems));
            $fullNotes = "Items: $itemSummary";
            if ($delivery_type === 'delivery') {
                $fullNotes = "[ANTAR KE: $address] " . $fullNotes;
            }
            if ($notes) $fullNotes .= " | Catatan: $notes";

            // Payment method: non-cash options only (cash langsung ke staff)
            $payment = $_POST['payment_method'] ?? 'transfer';

            $stmt = $db->prepare("INSERT INTO orders (order_code,user_id,service_id,weight,amount,pickup_date,delivery_date,notes,payment_method) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('siiddssss', $code,$uid,$sid,$totalWeight,$amount,$pickup,$delivery_date,$fullNotes,$payment);
            if ($stmt->execute()) {
                $orderId = $stmt->insert_id;

                // Notifikasi customer - sukses buat pesanan
                $custName = $db->real_escape_string($user['name']);
                $amtFmt   = formatRupiah($amount);
                $svcName  = $db->real_escape_string($svc['name']);
                $db->query("INSERT INTO notifications (user_id,title,message) VALUES ($uid,'🎉 Pesanan Berhasil Dibuat!','Halo $custName! Pesanan Anda ($code) untuk layanan $svcName sebesar $amtFmt telah kami terima dan sedang menunggu konfirmasi. Terima kasih telah mempercayai WashWell! 🧺')");

                // Notifikasi ke admin
                $db->query("INSERT INTO notifications (user_id,title,message) VALUES (1,'Pesanan Baru','Ada pesanan baru dari $custName - $code | $svcName | " . round($totalWeight,1) . " kg | $amtFmt')");

                $orderSuccess = [
                    'code'   => $code,
                    'svc'    => $svc['name'],
                    'weight' => $totalWeight,
                    'amount' => $amount,
                    'items'  => $validItems,
                    'pickup' => $pickup,
                    'payment'=> $payment,
                ];
                $msgType = 'success';
            } else {
                $msg = 'Gagal membuat pesanan. Silakan coba lagi.'; $msgType = 'danger';
            }
        }
    }
}
