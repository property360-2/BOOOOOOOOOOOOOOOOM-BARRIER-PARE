<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// Allow requests from devices / local UI (for demo only)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$root = __DIR__;
$dataDir = $root . '/data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);

function load_json($path, $default){
  if (!file_exists($path)) return $default;
  $txt = file_get_contents($path);
  $v = json_decode($txt, true);
  return $v === null ? $default : $v;
}
function save_json($path, $data){
  file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), LOCK_EX);
}

$usersFile = $dataDir . '/users.json';
$tagsFile = $dataDir . '/tags.json';
$regFile = $dataDir . '/registry.json';

// Device API key (change this to a strong secret for your networked device)
define('DEVICE_API_KEY', 'esp32-demo-key');

// ensure admin exists
$users = load_json($usersFile, []);
if (!array_filter($users, function($u){ return isset($u['role']) && $u['role']==='admin'; })){
  // create default admin if none exists
  $users[] = ['username'=>'admin', 'password'=>password_hash('admin', PASSWORD_DEFAULT), 'role'=>'admin'];
  save_json($usersFile, $users);
}

$action = $_REQUEST['action'] ?? 'ping';

function json_resp($data){ echo json_encode($data); exit; }

function time_diff($timestamp) {
    $then = strtotime($timestamp);
    $now = time();
    $diff = $now - $then;
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
}

switch($action){
  case 'ping':
    json_resp(['ok'=>true, 'msg'=>'pong']);
    break;

  case 'register':
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    if (!$u || !$p) json_resp(['ok'=>false,'err'=>'missing']);
    $users = load_json($usersFile, []);
    foreach($users as $ex) if ($ex['username']===$u) json_resp(['ok'=>false,'err'=>'exists']);
    // only allow creating admin if current session user is admin
    $session = $_SESSION['user'] ?? null;
    if ($role === 'admin' && (! $session || ($session['role'] ?? '') !== 'admin')){
      json_resp(['ok'=>false,'err'=>'not_allowed']);
    }
    $users[] = ['username'=>$u, 'password'=>password_hash($p, PASSWORD_DEFAULT), 'role'=>$role];
    save_json($usersFile, $users);
    // auto-login
    $_SESSION['user'] = ['username'=>$u, 'role'=>$role];
    json_resp(['ok'=>true, 'user'=>$_SESSION['user']]);
    break;

  case 'login':
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    if (!$u || !$p) json_resp(['ok'=>false]);
    $users = load_json($usersFile, []);
    foreach($users as $ex){
      if ($ex['username']===$u && password_verify($p, $ex['password'])){
        $_SESSION['user'] = ['username'=>$ex['username'], 'role'=>$ex['role'] ?? 'user'];
        json_resp(['ok'=>true, 'user'=>$_SESSION['user']]);
      }
    }
    json_resp(['ok'=>false]);
    break;

  case 'logout':
    unset($_SESSION['user']);
    json_resp(['ok'=>true]);
    break;

  case 'current_user':
    json_resp(['ok'=>true, 'user'=>($_SESSION['user'] ?? null)]);
    break;

  case 'get_tags':
    $tags = load_json($tagsFile, ['TAG-D9BBB311','TAG-D9BBB311','TAG-1003','EMP-42']);
    json_resp(['ok'=>true, 'tags'=>$tags]);
    break;

  case 'add_tag':
    $session = $_SESSION['user'] ?? null;
    if (!$session || ($session['role'] ?? '') !== 'admin') json_resp(['ok'=>false,'err'=>'auth']);
    $t = $_POST['tag'] ?? '';
    if (!$t) json_resp(['ok'=>false,'err'=>'missing']);
    $tags = load_json($tagsFile, []);
    if (!in_array($t,$tags)) { $tags[] = $t; save_json($tagsFile,$tags); }
    json_resp(['ok'=>true, 'tags'=>$tags]);
    break;

  case 'remove_tag':
    $session = $_SESSION['user'] ?? null;
    if (!$session || ($session['role'] ?? '') !== 'admin') json_resp(['ok'=>false,'err'=>'auth']);
    $t = $_POST['tag'] ?? '';
    $tags = load_json($tagsFile, []);
    $tags = array_values(array_filter($tags, function($x) use($t){ return $x !== $t; }));
    save_json($tagsFile,$tags);
    json_resp(['ok'=>true, 'tags'=>$tags]);
    break;

  case 'get_registry':
    $reg = load_json($regFile, []);
    json_resp(['ok'=>true, 'registry'=>$reg]);
    break;

  case 'toggle_registry':
    // authorized users only OR devices with API key
    $session = $_SESSION['user'] ?? null;
    $deviceKey = $_REQUEST['device_key'] ?? $_REQUEST['api_key'] ?? null;
    $allow = false;
    if ($session) $allow = true;
    if ($deviceKey && $deviceKey === DEVICE_API_KEY) $allow = true;
    if (!$allow) json_resp(['ok'=>false,'err'=>'auth']);
    $id = $_POST['tag'] ?? '';
    if (!$id) json_resp(['ok'=>false,'err'=>'missing']);
    $reg = load_json($regFile, []);
    $now = gmdate('c');
    if (!isset($reg[$id]) || (isset($reg[$id]['in']) && isset($reg[$id]['out']))){
      $reg[$id] = ['in'=>$now, 'out'=>null];
    } else if (isset($reg[$id]['in']) && !isset($reg[$id]['out'])){
      $reg[$id]['out'] = $now;
    }
    save_json($regFile, $reg);
    json_resp(['ok'=>true, 'registry'=>$reg]);
    break;

  case 'delete_registry_entry':
    // admin only
    $session = $_SESSION['user'] ?? null;
    if (!$session || ($session['role'] ?? '') !== 'admin') json_resp(['ok'=>false,'err'=>'auth']);
    $id = $_POST['tag'] ?? '';
    if (!$id) json_resp(['ok'=>false,'err'=>'missing']);
    $reg = load_json($regFile, []);
    if (isset($reg[$id])){ unset($reg[$id]); save_json($regFile, $reg); }
    json_resp(['ok'=>true, 'registry'=>$reg]);
    break;

  case 'clear_registry':
    // admin only
    $session = $_SESSION['user'] ?? null;
    if (!$session || ($session['role'] ?? '') !== 'admin') json_resp(['ok'=>false,'err'=>'auth']);
    // save an empty object for registry
    save_json($regFile, (object)[]);
    json_resp(['ok'=>true, 'registry'=> (object)[]]);
    break;

  case 'register_rfid':
    // Admin only
    $session = $_SESSION['user'] ?? null;
    if (!$session || ($session['role'] ?? '') !== 'admin') {
        json_resp(['ok'=>false,'err'=>'auth']);
    }

    // Get parameters
    $uid = trim($_POST['card_uid'] ?? '');
    $name = trim($_POST['card_holder_name'] ?? '');
    $email = trim($_POST['card_holder_email'] ?? '');
    $phone = trim($_POST['card_holder_phone'] ?? '');
    $dept = trim($_POST['department'] ?? '');
    $plate = trim($_POST['vehicle_plate'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!$uid || !$name) {
        json_resp(['ok'=>false,'err'=>'missing_required']);
    }

    // Load customers
    $customersFile = $dataDir . '/customers.json';
    $customers = load_json($customersFile, []);

    // Check if already exists
    if (isset($customers[$uid])) {
        json_resp(['ok'=>false,'err'=>'uid_already_registered']);
    }

    // Create customer record
    $customers[$uid] = [
        'card_uid' => $uid,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'department' => $dept,
        'vehicle_plate' => $plate,
        'notes' => $notes,
        'registered_at' => gmdate('c'),
        'registered_by' => $session['username']
    ];

    save_json($customersFile, $customers);

    // Also add to tags.json for authorization
    $tags = load_json($tagsFile, []);
    if (!in_array($uid, $tags)) {
        $tags[] = $uid;
        save_json($tagsFile, $tags);
    }

    json_resp(['ok'=>true, 'customer'=>$customers[$uid]]);
    break;

  case 'get_customers':
    // Admin only
    $session = $_SESSION['user'] ?? null;
    if (!$session || ($session['role'] ?? '') !== 'admin') {
        json_resp(['ok'=>false,'err'=>'auth']);
    }

    $customersFile = $dataDir . '/customers.json';
    $customers = load_json($customersFile, []);

    json_resp(['ok'=>true, 'customers'=>$customers]);
    break;

  case 'get_vehicles_inside':
    // Any authenticated user
    $session = $_SESSION['user'] ?? null;
    if (!$session) {
        json_resp(['ok'=>false,'err'=>'auth']);
    }

    // Load registry and customers
    $reg = load_json($regFile, []);
    $customersFile = $dataDir . '/customers.json';
    $customers = load_json($customersFile, []);

    $inside = [];
    foreach ($reg as $uid => $record) {
        // Check if currently inside (has 'in' timestamp but no 'out' timestamp)
        if (isset($record['in']) && !isset($record['out'])) {
            $customer = $customers[$uid] ?? null;
            $inside[] = [
                'uid' => $uid,
                'name' => $customer['name'] ?? 'Unknown',
                'vehicle_plate' => $customer['vehicle_plate'] ?? 'N/A',
                'department' => $customer['department'] ?? '',
                'check_in_time' => $record['in'],
                'duration' => time_diff($record['in'])
            ];
        }
    }

    json_resp(['ok'=>true, 'vehicles'=>$inside]);
    break;

  case 'export_registry_excel':
    // Admin only
    $session = $_SESSION['user'] ?? null;
    if (!$session || ($session['role'] ?? '') !== 'admin') {
        json_resp(['ok'=>false,'err'=>'auth']);
    }

    $reg = load_json($regFile, []);
    $customersFile = $dataDir . '/customers.json';
    $customers = load_json($customersFile, []);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="registry_' . date('Y-m-d_His') . '.csv"');

    $output = fopen('php://output', 'w');

    fputcsv($output, ['Card UID', 'Name', 'Vehicle Plate', 'Department', 'Check In', 'Check Out', 'Duration']);

    foreach ($reg as $uid => $record) {
        $customer = $customers[$uid] ?? null;

        $checkIn = $record['in'] ?? '';
        $checkOut = $record['out'] ?? '';

        $duration = '';
        if ($checkIn && $checkOut) {
            $diff = strtotime($checkOut) - strtotime($checkIn);
            $hours = floor($diff / 3600);
            $minutes = floor(($diff % 3600) / 60);
            $duration = "{$hours}h {$minutes}m";
        } elseif ($checkIn) {
            $duration = 'Still inside';
        }

        fputcsv($output, [
            $uid,
            $customer['name'] ?? 'Unknown',
            $customer['vehicle_plate'] ?? 'N/A',
            $customer['department'] ?? '',
            $checkIn,
            $checkOut,
            $duration
        ]);
    }

    fclose($output);
    exit;
    break;

  case 'export_audit_excel':
    // Admin only
    $session = $_SESSION['user'] ?? null;
    if (!$session || ($session['role'] ?? '') !== 'admin') {
        json_resp(['ok'=>false,'err'=>'auth']);
    }

    $auditFile = __DIR__ . '/esp32_audit.log';

    if (!file_exists($auditFile)) {
        json_resp(['ok'=>false,'err'=>'no_audit_file']);
    }

    // Read NDJSON and convert to CSV
    $lines = file($auditFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Set headers for Excel download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d_His') . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, ['Timestamp', 'IP Address', 'UID', 'Authorized', 'Distance (cm)', 'Status', 'HTTP Code']);

    // Parse each NDJSON line and write to CSV
    foreach (array_reverse($lines) as $line) { // Reverse for newest first
        $obj = json_decode($line, true);
        if (!$obj) continue;

        fputcsv($output, [
            $obj['timestamp'] ?? '',
            $obj['ip'] ?? '',
            $obj['uid'] ?? '',
            isset($obj['authorized']) && $obj['authorized'] ? 'Yes' : 'No',
            $obj['distance'] ?? '',
            $obj['status'] ?? '',
            $obj['http_code'] ?? ''
        ]);
    }

    fclose($output);
    exit;
    break;

  default:
    json_resp(['ok'=>false,'err'=>'unknown_action']);
}
