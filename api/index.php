<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$script_name = dirname($_SERVER['SCRIPT_NAME']);
$uri = '/' . ltrim(str_replace($script_name, '', $uri), '/');

$method = $_SERVER['REQUEST_METHOD'];

require_once __DIR__ . '/core/Env.php';
Env::load(__DIR__ . '/.env');

require_once 'rutas.php';
