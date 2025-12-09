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
            $stmt->execute([json_encode(['temperature' => $data['temp'], 'humidity' => $data['hum']])]);
        } else {
            // Các thiết bị ON/OFF
            $stmt = $pdo->prepare("UPDATE devices SET status=?, last_seen=NOW() WHERE device_id=?");
            $stmt->execute([$value, $key]);
        }
        $last = $stmt->fetchColumn();

        if (!$last || strtotime($now) - strtotime($last) >= 25) {
            $time = date('Y-m-d H:i:s');
            // $stmt = $pdo->prepare("
            //     INSERT INTO sensor_log (temp, hum, light, device_time)
            //     VALUES (?, ?, ?, ?)
            // ");
            $stmt = $pdo->prepare("
                    INSERT INTO sensor_log (temp, hum, light, device_time)
                    SELECT ?, ?, ?, ?
                    FROM DUAL
                    WHERE NOT EXISTS (
                        SELECT 1 FROM sensor_log
                        WHERE device_time >= DATE_SUB(?, INTERVAL 25 SECOND)
                    )
                ");
            $stmt->execute([
                $data['temp'],
                $data['hum'],
                $data['CBAS'],
                $time,
                $time
            ]);
        }


    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
