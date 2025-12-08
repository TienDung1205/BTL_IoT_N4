<?php
include 'config.php'; // file config chứa $pdo

// ESP32 sẽ gửi JSON qua POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
    exit;
}

try {
       foreach ($data as $key => $value) {
    if ($key === 'temp' || $key === 'hum') {
        // DHT22
        $stmt = $pdo->prepare("UPDATE devices SET status=?, last_seen=NOW() WHERE device_id='dht22'");
        $stmt->execute([json_encode(['temperature'=>$data['temp'],'humidity'=>$data['hum']])]);
    } else {
        // Các thiết bị ON/OFF
        $stmt = $pdo->prepare("UPDATE devices SET status=?, last_seen=NOW() WHERE device_id=?");
        $stmt->execute([$value, $key]);
    }
}

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
