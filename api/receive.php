<?php
// Include config
require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Get JSON input
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

// Detailed logging
$log_message = date('Y-m-d H:i:s') . " - RAW: " . $raw_input . "\n";
file_put_contents('debug.log', $log_message, FILE_APPEND);

if (!$input) {
    $error_msg = "Invalid JSON. Raw input: " . $raw_input . "\n";
    file_put_contents('debug.log', $error_msg, FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON', 'raw' => $raw_input]);
    exit;
}

// Verify secret key
$received_key = $input['secret_key'] ?? 'none';
file_put_contents('debug.log', "Received key: " . $received_key . " | Expected: " . SECRET_KEY . "\n", FILE_APPEND);

if ($received_key !== SECRET_KEY) {
    $key_error = "Key mismatch! Received: '$received_key' | Expected: '" . SECRET_KEY . "'\n";
    file_put_contents('debug.log', $key_error, FILE_APPEND);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid secret key', 
        'received' => $received_key,
        'expected' => SECRET_KEY
    ]);
    exit;
}

// Process devices
$devices = $input['devices'] ?? [];
$timestamp = $input['timestamp'] ?? date('Y-m-d H:i:s');

file_put_contents('debug.log', "Processing " . count($devices) . " devices\n", FILE_APPEND);

foreach ($devices as $deviceData) {
    processDevice($pdo, $deviceData, $timestamp);
}

echo json_encode([
    'status' => 'success', 
    'message' => 'Data received', 
    'devices_processed' => count($devices)
]);

function processDevice($pdo, $device, $timestamp) {
    file_put_contents('debug.log', "Processing device: " . $device['mac_address'] . "\n", FILE_APPEND);
    
    // Check if device exists
    $stmt = $pdo->prepare("SELECT id FROM devices WHERE mac_address = ?");
    $stmt->execute([$device['mac_address']]);
    $existingDevice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingDevice) {
        // Update existing device
        $deviceId = $existingDevice['id'];
        $stmt = $pdo->prepare("UPDATE devices SET hostname = ?, last_seen = ? WHERE id = ?");
        $stmt->execute([$device['hostname'], $timestamp, $deviceId]);
        file_put_contents('debug.log', "Updated device: " . $deviceId . "\n", FILE_APPEND);
    } else {
        // Insert new device
        $stmt = $pdo->prepare("INSERT INTO devices (mac_address, hostname, first_seen, last_seen) VALUES (?, ?, ?, ?)");
        $stmt->execute([$device['mac_address'], $device['hostname'], $timestamp, $timestamp]);
        $deviceId = $pdo->lastInsertId();
        file_put_contents('debug.log', "Inserted new device: " . $deviceId . "\n", FILE_APPEND);
    }
    
    // Check for active connection
    $stmt = $pdo->prepare("SELECT id FROM connections WHERE device_id = ? AND disconnected_at IS NULL");
    $stmt->execute([$deviceId]);
    $activeConnection = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activeConnection) {
        // Create new connection record
        $stmt = $pdo->prepare("INSERT INTO connections (device_id, ip_address, connected_at) VALUES (?, ?, ?)");
        $stmt->execute([$deviceId, $device['ip_address'], $timestamp]);
        file_put_contents('debug.log', "Created connection for device: " . $deviceId . "\n", FILE_APPEND);
    }
}
?>