<?php
$expectedToken = '{{TOKEN}}';

// ==================== SEGURIDAD ====================
if (!isset($_GET['token'])) {
    http_response_code(401);
    die('Token required');
}

if ($_GET['token'] !== $expectedToken) {
    http_response_code(403);
    die('Invalid token');
}

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (strpos($userAgent, 'GitHub Actions') === false) {
    http_response_code(403);
    die('Invalid request source');
}

// ==================== UNZIP VENDOR ====================
set_time_limit(300);
ignore_user_abort(true);

$zipFile = realpath(__DIR__ . '/../vendor.zip');
$targetDir = realpath(__DIR__ . '/..');

if (!file_exists($zipFile)) {
    http_response_code(404);
    die('vendor.zip not found');
}

$zip = new ZipArchive;
if ($zip->open($zipFile) !== TRUE) {
    http_response_code(500);
    die('Cannot open zip file');
}

if ($zip->extractTo($targetDir) !== TRUE) {
    http_response_code(500);
    die('Extraction failed');
}

$zip->close();

// Limpieza final
unlink($zipFile);
unlink(__FILE__);

echo 'Vendor unzipped successfully!';
