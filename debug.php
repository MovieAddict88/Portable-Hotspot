<?php
// debug.php - Test script to check what's happening
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Hotspot Monitor Debug</h1>";

// Test database connection
$host = 'sql100.infinityfree.com';
$dbname = 'if0_40117343_hotspot';
$username = 'if0_40117343';  // Replace with your actual username
$password = 'rW0LYMue4MQkP';  // Replace with your actual password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Check if settings table exists and has data
try {
    $stmt = $pdo->prepare("SELECT * FROM settings LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($settings) {
        echo "<p style='color: green;'>✅ Settings table found!</p>";
        echo "<p>Secret key in database: <strong>" . $settings['secret_key'] . "</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ No data in settings table!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Settings table error: " . $e->getMessage() . "</p>";
}

// Test POST request simulation
echo "<h2>Test POST Request</h2>";
echo "<form method='post'>";
echo "<input type='hidden' name='test_data' value='{\"secret_key\":\"8888\",\"timestamp\":\"2024-01-15 10:30:00\",\"devices\":[]}'>";
echo "<button type='submit'>Test Secret Key 8888</button>";
echo "</form>";

if ($_POST) {
    $input = json_decode($_POST['test_data'], true);
    if ($input['secret_key'] === '8888') {
        echo "<p style='color: green;'>✅ Secret key 8888 is working!</p>";
    } else {
        echo "<p style='color: red;'>❌ Secret key mismatch!</p>";
    }
}
?>