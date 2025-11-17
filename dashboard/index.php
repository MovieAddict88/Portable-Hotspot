<?php
require_once '../config.php';

$stmt = $pdo->prepare("
    SELECT d.*, c.ip_address, c.connected_at 
    FROM connections c 
    JOIN devices d ON c.device_id = d.id 
    WHERE c.disconnected_at IS NULL 
    OR c.connected_at > NOW() - INTERVAL 2 MINUTE 
    ORDER BY c.connected_at DESC
");
$stmt->execute();
$currentDevices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT d.hostname, c.connected_at, c.disconnected_at, 
           TIMEDIFF(c.disconnected_at, c.connected_at) as duration
    FROM connections c 
    JOIN devices d ON c.device_id = d.id 
    ORDER BY c.connected_at DESC 
    LIMIT 20
");
$stmt->execute();
$connectionHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) as total_devices FROM devices");
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotspot Monitor Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .device { background: #ecf0f1; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #3498db; }
        .device.connected { border-left-color: #2ecc71; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #3498db; color: white; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-card h3 { font-size: 24px; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Hotspot Monitor Dashboard</h1>
            <p>Real-time monitoring of connected devices</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>📱 <?php echo $stats['total_devices']; ?></h3>
                <p>Total Devices</p>
            </div>
            <div class="stat-card">
                <h3>🟢 <?php echo count($currentDevices); ?></h3>
                <p>Currently Connected</p>
            </div>
        </div>

        <div class="card">
            <h2>🟢 Currently Connected Devices</h2>
            <?php if (empty($currentDevices)): ?>
                <p>No devices currently connected</p>
            <?php else: ?>
                <?php foreach ($currentDevices as $device): ?>
                    <div class="device connected">
                        <strong>📱 <?php echo htmlspecialchars($device['hostname']); ?></strong><br>
                        <small>IP: <?php echo htmlspecialchars($device['ip_address']); ?></small><br>
                        <small>MAC: <?php echo htmlspecialchars($device['mac_address']); ?></small><br>
                        <small>Connected: <?php echo $device['connected_at']; ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>📊 Connection History</h2>
            <?php if (empty($connectionHistory)): ?>
                <p>No connection history yet</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>Connected</th>
                            <th>Disconnected</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connectionHistory as $connection): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($connection['hostname']); ?></td>
                                <td><?php echo $connection['connected_at']; ?></td>
                                <td><?php echo $connection['disconnected_at'] ?: 'Still connected'; ?></td>
                                <td><?php echo $connection['duration'] ?: 'Active'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>