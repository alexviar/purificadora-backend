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

// ==================== INICIALIZAR LARAVEL ====================
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// ==================== EJECUTAR COMANDOS ====================
$commands = [
    ['migrate', '--force' => true],
    file_exists(__DIR__ . './storage') ? [] : ['storage:link'],
    ['optimize:clear'],
    ['optimize']
];

header('Content-Type: text/plain');
$finalOutput = '';

try {
    foreach ($commands as $commandParts) {
        if (empty($commandParts)) continue;
        $input = new ArrayInput(['command' => $commandParts[0], ...array_slice($commandParts, 1)]);
        $output = new BufferedOutput();

        $status = $kernel->handle($input, $output);
        $commandOutput = $output->fetch();

        $finalOutput .= "▶️ Ejecutando: php artisan " . implode(' ', $commandParts) . "\n";
        $finalOutput .= $commandOutput . "\n";
        $finalOutput .= $status === 0 ? "✅ Éxito\n\n" : "❌ Error (Código: $status)\n\n";

        if ($status !== 0) {
            throw new Exception("Command failed: php artisan " . implode(' ', $commandParts));
        }
    }

    $finalOutput .= "🎉 Todos los comandos ejecutados correctamente";
} catch (Exception $e) {
    http_response_code(500);
    $finalOutput .= "💥 Error crítico: " . $e->getMessage();
    logger($finalOutput);
} finally {
    // Auto-eliminación del script y output final
    unlink(__FILE__);
    die($finalOutput);
}
