<?php
/**
 * Initialize SQLite3 RFID database
 * Run once to create schema and seed default data
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbPath = __DIR__ . '/rfid.db';
$schemaPath = __DIR__ . '/schema.sql';

// Create or open database
try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection: $dbPath\n";
} catch (Exception $e) {
    die("❌ Failed to open database: " . $e->getMessage() . "\n");
}

// Read and execute schema
if (!file_exists($schemaPath)) {
    die("❌ Schema file not found: $schemaPath\n");
}

$schema = file_get_contents($schemaPath);
try {
    $db->exec($schema);
    echo "✅ Schema created/updated\n";
} catch (Exception $e) {
    die("❌ Failed to execute schema: " . $e->getMessage() . "\n");
}

// Seed default admin user if not exists
$adminCheckStmt = $db->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
$adminCount = $adminCheckStmt->fetchColumn();

if ($adminCount == 0) {
    $adminPassword = password_hash('admin', PASSWORD_DEFAULT);
    $insertAdminStmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $insertAdminStmt->execute(['admin', $adminPassword, 'admin']);
    echo "✅ Default admin user created (username: admin, password: admin)\n";
} else {
    echo "ℹ Admin user already exists\n";
}

// Seed sample RFID card if not exists
$sampleUid = 'TAG-DEMO-001';
$cardCheckStmt = $db->prepare("SELECT COUNT(*) FROM rfid_cards WHERE card_uid = ?");
$cardCheckStmt->execute([$sampleUid]);
$cardCount = $cardCheckStmt->fetchColumn();

if ($cardCount == 0) {
    $insertCardStmt = $db->prepare(
        "INSERT INTO rfid_cards (card_uid, card_holder_name, card_holder_email, department, status) 
         VALUES (?, ?, ?, ?, ?)"
    );
    $insertCardStmt->execute([$sampleUid, 'Demo User', 'demo@example.com', 'IT', 'active']);
    echo "✅ Sample RFID card created: $sampleUid\n";
} else {
    echo "ℹ Sample card already exists\n";
}

echo "\n✅ Database initialization complete!\n";
?>
