<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($uri === '/api/health' && $method === 'GET') {
    json_response([
        'success' => true,
        'service' => 'wifi-device-management-api',
        'status' => 'ok',
        'time' => gmdate('c')
    ]);
}

if ($uri === '/api/auth/login' && $method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $email = trim((string)($body['email'] ?? ''));
    $password = (string)($body['password'] ?? '');

    if ($email === '' || $password === '') {
        json_response(['success'=>false,'message'=>'Email and password are required.'], 422);
    }

    json_response([
        'success'=>true,
        'message'=>'Starter endpoint. Connect to admins table using password_verify().',
        'token'=>'replace-with-real-token'
    ]);
}

if ($uri === '/api/devices/heartbeat' && $method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $deviceId = trim((string)($body['device_id'] ?? ''));

    if ($deviceId === '') {
        json_response(['success'=>false,'message'=>'device_id is required.'], 422);
    }

    json_response([
        'success'=>true,
        'message'=>'Heartbeat received.',
        'device_id'=>$deviceId,
        'received_at'=>gmdate('c')
    ]);
}

if ($uri === '/api/devices' && $method === 'GET') {
    json_response(['success'=>true,'devices'=>[]]);
}

json_response(['success'=>false,'message'=>'Route not found.'], 404);
