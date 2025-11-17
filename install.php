<?php
// Hotspot Monitor - Auto Installer
header('Content-Type: text/html; charset=utf-8');

if ($_POST['install']) {
    $host = $_POST['host'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $database = $_POST['database'];
    $secret_key = $_POST['secret_key'] ?: 'hotspot_monitor_2024';
    
    try {
        // Connect to MySQL
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database`");
        $pdo->exec("USE `$database`");
        
        // Create tables
        $tables_sql = [
            "CREATE TABLE IF NOT EXISTS devices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                mac_address VARCHAR(17) UNIQUE NOT NULL,
                hostname VARCHAR(255),
                first_seen DATETIME,
                last_seen DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS connections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                device_id INT,
                ip_address VARCHAR(15),
                connected_at DATETIME,
                disconnected_at DATETIME,
                data_downloaded BIGINT DEFAULT 0,
                data_uploaded BIGINT DEFAULT 0,
                FOREIGN KEY (device_id) REFERENCES devices(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                secret_key VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "INSERT IGNORE INTO settings (secret_key) VALUES ('$secret_key')"
        ];
        
        foreach ($tables_sql as $sql) {
            $pdo->exec($sql);
        }
        
        // Create config file
        $config_content = "<?php
// Auto-generated config file
define('DB_HOST', '$host');
define('DB_NAME', '$database');
define('DB_USER', '$username');
define('DB_PASS', '$password');
define('SECRET_KEY', '$secret_key');

try {
    \$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException \$e) {
    die('Database connection failed: ' . \$e->getMessage());
}
?>";
        
        file_put_contents('config.php', $config_content);
        
        $success = true;
        
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotspot Monitor - Auto Install</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #3498db; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #2980b9; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .step { background: #e8f4fc; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Hotspot Monitor - Auto Install</h1>
        
        <?php if (isset($success)): ?>
            <div class="success">
                <h2>🎉 Installation Complete!</h2>
                <p><strong>Secret Key:</strong> <?php echo $secret_key; ?></p>
                <p><strong>Database:</strong> <?php echo $database; ?></p>
                <p><a href="dashboard/index.php" style="background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;">🚀 Open Dashboard</a></p>
                <p style="margin-top: 15px; font-size: 14px; color: #666;">⚠️ Remember to delete this install.php file for security</p>
            </div>
        <?php elseif (isset($error)): ?>
            <div class="error">❌ Installation failed: <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!isset($success)): ?>
        <div class="step">
            <h3>📋 Installation Steps:</h3>
            <ol>
                <li>Fill in your MySQL database credentials</li>
                <li>Choose a secret key (or use default)</li>
                <li>Click "Install Now"</li>
                <li>The system will automatically create everything</li>
            </ol>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>MySQL Host:</label>
                <input type="text" name="host" value="localhost" required>
            </div>
            
            <div class="form-group">
                <label>MySQL Username:</label>
                <input type="text" name="username" value="root" required>
            </div>
            
            <div class="form-group">
                <label>MySQL Password:</label>
                <input type="password" name="password" placeholder="Your MySQL password">
            </div>
            
            <div class="form-group">
                <label>Database Name:</label>
                <input type="text" name="database" value="hotspot_monitor" required>
            </div>
            
            <div class="form-group">
                <label>Secret Key (for Android app):</label>
                <input type="text" name="secret_key" value="hotspot_monitor_2024" required>
            </div>
            
            <button type="submit" name="install" value="1">🔧 Install Now</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>