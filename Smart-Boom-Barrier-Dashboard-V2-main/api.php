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
		// Register a new RFID card (admin only)
		$session = $_SESSION['user'] ?? null;
		if (!$session || ($session['role'] ?? '') !== 'admin') json_resp(['ok'=>false,'err'=>'auth']);
		
		$uid = $_POST['card_uid'] ?? '';
		$name = $_POST['card_holder_name'] ?? '';
		$email = $_POST['card_holder_email'] ?? '';
		$phone = $_POST['card_holder_phone'] ?? '';
		$dept = $_POST['department'] ?? '';
		$notes = $_POST['notes'] ?? '';
		
		if (!$uid || !$name) json_resp(['ok'=>false,'err'=>'missing']);
		
		// Store in JSON registry for now (can be extended to SQLite)
		$rfidRegistry = $dataDir . '/rfid_registry.json';
		$registry = load_json($rfidRegistry, []);
		
		// Check if UID already exists
		foreach($registry as $entry) {
			if ($entry['card_uid'] === $uid) json_resp(['ok'=>false,'err'=>'uid_exists']);
		}
		
		// Add new card registration
		$registry[] = [
			'card_uid' => $uid,
			'card_holder_name' => $name,
			'card_holder_email' => $email,
			'card_holder_phone' => $phone,
			'department' => $dept,
			'notes' => $notes,
			'status' => 'active',
			'registered_at' => gmdate('c'),
			'registered_by' => $session['username'] ?? 'admin'
		];
		
		save_json($rfidRegistry, $registry);
		
		// Also add to tags list for compatibility
		$tags = load_json($tagsFile, []);
		if (!in_array($uid, $tags)) {
			$tags[] = $uid;
			save_json($tagsFile, $tags);
		}
		
		json_resp(['ok'=>true, 'msg'=>'RFID card registered', 'card_uid'=>$uid]);
		break;

	case 'get_rfid_registry':
		// Get all registered RFID cards (admin only)
		$session = $_SESSION['user'] ?? null;
		if (!$session || ($session['role'] ?? '') !== 'admin') json_resp(['ok'=>false,'err'=>'auth']);
		
		$rfidRegistry = $dataDir . '/rfid_registry.json';
		$registry = load_json($rfidRegistry, []);
		json_resp(['ok'=>true, 'cards'=>$registry]);
		break;

	case 'delete_rfid_card':
		// Delete an RFID card registration (admin only)
		$session = $_SESSION['user'] ?? null;
		if (!$session || ($session['role'] ?? '') !== 'admin') json_resp(['ok'=>false,'err'=>'auth']);
		
		$uid = $_POST['card_uid'] ?? '';
		if (!$uid) json_resp(['ok'=>false,'err'=>'missing']);
		
		$rfidRegistry = $dataDir . '/rfid_registry.json';
		$registry = load_json($rfidRegistry, []);
		
		$updated = array_values(array_filter($registry, function($e) use($uid) {
			return $e['card_uid'] !== $uid;
		}));
		
		save_json($rfidRegistry, $updated);
		
		// Also remove from tags list
		$tags = load_json($tagsFile, []);
		$tags = array_values(array_filter($tags, function($t) use($uid) {
			return $t !== $uid;
		}));
		save_json($tagsFile, $tags);
		
		json_resp(['ok'=>true, 'msg'=>'RFID card deleted']);
		break;

	default:
		json_resp(['ok'=>false,'err'=>'unknown_action']);
}


