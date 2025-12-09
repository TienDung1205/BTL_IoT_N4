<?php
include 'config.php';
header('Content-Type: application/json');
try {
    $stmt = $pdo->query("SELECT * FROM devices");
    $devices = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $devices[$row['device_id']] = $row;
    }
    echo json_encode($devices);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
