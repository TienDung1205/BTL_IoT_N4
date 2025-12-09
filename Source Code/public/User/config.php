<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_home');
define('DB_USER', 'root');
define('DB_PASS', '');
define('API_KEY', '123456'); 

// MQTT configuration
// define('MQTT_HOST', '127.0.0.1');
// define('MQTT_PORT', 1883);
// define('MQTT_USER', ''); 
// define('MQTT_PASS', '');
// define('MQTT_REPORT_TOPIC', 'smarthouse/report');
// define('MQTT_COMMAND_TOPIC_PREFIX', 'smarthouse/command/');
date_default_timezone_set('Asia/Ho_Chi_Minh');
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => $e->getMessage()]));
}
?>
