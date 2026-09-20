<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

function json_response(array $data, int $status=200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    exit;
}
function body(): array { return json_decode(file_get_contents('php://input'), true) ?: []; }
function db(): PDO {
    static $pdo;
    if ($pdo) return $pdo;
    $host=getenv('DB_HOST') ?: '127.0.0.1';
    $port=getenv('DB_PORT') ?: '3306';
    $name=getenv('DB_DATABASE') ?: 'wifi_manager';
    $user=getenv('DB_USERNAME') ?: 'root';
    $pass=getenv('DB_PASSWORD') ?: '';
    $pdo=new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",$user,$pass,[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]);
    return $pdo;
}
function bearer(): string {
    $h=$_SERVER['HTTP_AUTHORIZATION'] ?? '';
    return preg_match('/Bearer\\s+(.+)/i',$h,$m) ? trim($m[1]) : '';
}
function admin_id(): int {
    $token=bearer(); if ($token==='') json_response(['success'=>false,'message'=>'Admin authentication required.'],401);
    $s=db()->prepare('SELECT admin_id FROM admin_sessions WHERE token_hash=? AND expires_at>NOW()');
    $s->execute([hash('sha256',$token)]); $id=(int)$s->fetchColumn();
    if (!$id) json_response(['success'=>false,'message'=>'Invalid or expired admin token.'],401);
    return $id;
}
function device_auth(): array {
    $token=bearer(); if ($token==='') json_response(['success'=>false,'message'=>'Device token required.'],401);
    $s=db()->prepare('SELECT * FROM devices WHERE device_token_hash=? LIMIT 1');
    $s->execute([hash('sha256',$token)]); $d=$s->fetch();
    if (!$d) json_response(['success'=>false,'message'=>'Invalid device token.'],401);
    return $d;
}
$uri=parse_url($_SERVER['REQUEST_URI'] ?? '/',PHP_URL_PATH);
$method=$_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($uri==='/api/health' && $method==='GET') json_response(['success'=>true,'service'=>'wifi-device-management-api','status'=>'ok','time'=>gmdate('c')]);

if ($uri==='/api/auth/login' && $method==='POST') {
    $b=body(); $email=trim((string)($b['email']??'')); $password=(string)($b['password']??'');
    if ($email===''||$password==='') json_response(['success'=>false,'message'=>'Email and password are required.'],422);
    $s=db()->prepare('SELECT id,name,email,password_hash FROM admins WHERE email=? LIMIT 1'); $s->execute([$email]); $a=$s->fetch();
    if (!$a || !password_verify($password,$a['password_hash'])) json_response(['success'=>false,'message'=>'Invalid credentials.'],401);
    $token=bin2hex(random_bytes(32));
    db()->prepare('INSERT INTO admin_sessions(admin_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 12 HOUR))')->execute([$a['id'],hash('sha256',$token)]);
    json_response(['success'=>true,'token'=>$token,'admin'=>['id'=>$a['id'],'name'=>$a['name'],'email'=>$a['email']]]);
}

if ($uri==='/api/devices/enrollment-code' && $method==='POST') {
    $aid=admin_id(); $code=strtoupper(bin2hex(random_bytes(4)));
    db()->prepare('INSERT INTO enrollment_codes(code,created_by,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 24 HOUR))')->execute([$code,$aid]);
    json_response(['success'=>true,'code'=>$code,'expires_in'=>'24 hours']);
}

if ($uri==='/api/devices/register' && $method==='POST') {
    $b=body(); $code=strtoupper(trim((string)($b['enrollment_code']??''))); $deviceId=trim((string)($b['device_id']??''));
    if ($code===''||$deviceId==='') json_response(['success'=>false,'message'=>'enrollment_code and device_id are required.'],422);
    $s=db()->prepare('SELECT id FROM enrollment_codes WHERE code=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1'); $s->execute([$code]); $ec=$s->fetch();
    if (!$ec) json_response(['success'=>false,'message'=>'Invalid or expired enrollment code.'],400);
    $raw=bin2hex(random_bytes(32));
    $sql='INSERT INTO devices(device_id,hostname,os_name,app_version,device_token_hash,connection_status,last_heartbeat_at)
          VALUES(?,?,?,?,?,?,NOW())
          ON DUPLICATE KEY UPDATE hostname=VALUES(hostname),os_name=VALUES(os_name),app_version=VALUES(app_version),device_token_hash=VALUES(device_token_hash),connection_status="online",last_heartbeat_at=NOW()';
    db()->prepare($sql)->execute([$deviceId,$b['hostname']??null,$b['os_name']??null,$b['app_version']??null,hash('sha256',$raw),'online']);
    db()->prepare('UPDATE enrollment_codes SET used_at=NOW(),used_device_id=? WHERE id=?')->execute([$deviceId,$ec['id']]);
    db()->prepare('INSERT INTO device_events(device_id,event_type,payload) VALUES(?,?,?)')->execute([$deviceId,'registered',json_encode(['hostname'=>$b['hostname']??null])]);
    json_response(['success'=>true,'device_id'=>$deviceId,'device_token'=>$raw]);
}

if ($uri==='/api/devices/heartbeat' && $method==='POST') {
    $d=device_auth(); $b=body();
    db()->prepare('UPDATE devices SET hostname=?,os_name=?,app_version=?,last_ip=?,connection_status="online",last_heartbeat_at=NOW() WHERE id=?')
      ->execute([$b['hostname']??$d['hostname'],$b['os_name']??$d['os_name'],$b['app_version']??$d['app_version'],$b['last_ip']??null,$d['id']]);
    db()->prepare('INSERT INTO device_events(device_id,event_type,payload) VALUES(?,?,?)')->execute([$d['device_id'],'heartbeat',json_encode(['ip'=>$b['last_ip']??null])]);
    $s=db()->prepare('SELECT id,scan_requested_at FROM scan_requests WHERE device_id=? AND completed_at IS NULL ORDER BY id DESC LIMIT 1'); $s->execute([$d['device_id']]); $scan=$s->fetch();
    json_response(['success'=>true,'received_at'=>gmdate('c'),'scan_request'=>$scan?['id'=>(int)$scan['id']]:null]);
}

if ($uri==='/api/devices/scan-result' && $method==='POST') {
    $d=device_auth(); $b=body();
    $scanId=(int)($b['scan_id']??0);
    db()->prepare('UPDATE devices SET wifi_ssid=?,wifi_bssid=?,wifi_signal=?,wifi_radio=?,wifi_channel=?,wifi_connected_at=NOW(),last_scan_at=NOW() WHERE id=?')
      ->execute([$b['ssid']??null,$b['bssid']??null,$b['signal']??null,$b['radio']??null,$b['channel']??null,$d['id']]);
    if($scanId) db()->prepare('UPDATE scan_requests SET completed_at=NOW(),result_json=? WHERE id=? AND device_id=?')->execute([json_encode($b),$scanId,$d['device_id']]);
    db()->prepare('INSERT INTO device_events(device_id,event_type,payload) VALUES(?,?,?)')->execute([$d['device_id'],'wifi_scan',json_encode($b)]);
    json_response(['success'=>true]);
}

if ($uri==='/api/devices' && $method==='GET') {
    admin_id();
    $rows=db()->query("SELECT id,device_id,hostname,os_name,app_version,last_ip,connection_status,last_heartbeat_at,wifi_ssid,wifi_bssid,wifi_signal,wifi_radio,wifi_channel,last_scan_at FROM devices ORDER BY updated_at DESC")->fetchAll();
    json_response(['success'=>true,'devices'=>$rows]);
}

if (preg_match('#^/api/devices/([^/]+)/scan$#',$uri,$m) && $method==='POST') {
    $aid=admin_id(); $deviceId=urldecode($m[1]);
    $s=db()->prepare('SELECT id FROM devices WHERE device_id=? LIMIT 1'); $s->execute([$deviceId]); if(!$s->fetch()) json_response(['success'=>false,'message'=>'Device not found.'],404);
    db()->prepare('INSERT INTO scan_requests(device_id,requested_by) VALUES(?,?)')->execute([$deviceId,$aid]);
    json_response(['success'=>true,'message'=>'Scan queued for authorized device.']);
}

if (preg_match('#^/api/devices/([^/]+)$#',$uri,$m) && $method==='GET') {
    admin_id(); $s=db()->prepare('SELECT * FROM devices WHERE device_id=? LIMIT 1'); $s->execute([urldecode($m[1])]); $d=$s->fetch();
    if(!$d) json_response(['success'=>false,'message'=>'Device not found.'],404);
    unset($d['device_token_hash']); json_response(['success'=>true,'device'=>$d]);
}

if ($uri==='/api/events' && $method==='GET') {
    admin_id(); $rows=db()->query('SELECT id,device_id,event_type,payload,created_at FROM device_events ORDER BY id DESC LIMIT 100')->fetchAll();
    json_response(['success'=>true,'events'=>$rows]);
}

json_response(['success'=>false,'message'=>'Route not found.'],404);
