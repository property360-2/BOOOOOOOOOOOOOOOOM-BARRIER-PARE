<?php
header('Content-Type: application/json; charset=utf-8');
// Simple SQLite initializer for the RFID dashboard
$root = __DIR__ . DIRECTORY_SEPARATOR;
$dbFile = $root . 'rfid.db';
$schemaFile = $root . 'schema.sql';

try{
    if (!file_exists($schemaFile)) throw new Exception('schema.sql not found in data/ directory');

    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents($schemaFile);
    $pdo->exec($sql);

    // optional: seed a demo authorized user if table empty
    $row = $pdo->query("SELECT COUNT(*) AS c FROM authorized_users")->fetch(PDO::FETCH_ASSOC);
    if ($row && intval($row['c']) === 0){
        $stmt = $pdo->prepare('INSERT INTO authorized_users (uid,name,access_level) VALUES (?,?,?)');
        $stmt->execute(['TAGD9BBB311','Demo User','admin']);
    }

    echo json_encode(['ok'=>true,'msg'=>'Database initialized','db'=>$dbFile]);
}catch(Exception $e){
    echo json_encode(['ok'=>false,'err'=>$e->getMessage()]);
}
