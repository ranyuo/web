<?php
declare(strict_types=1);
require_once __DIR__.'/Portal.php';

function portal_app(): PortalApp
{
    $path = getenv('PORTAL_CONFIG_FILE') ?: __DIR__.'/config.php';
    if (!is_file($path)) {
        throw new PortalError(503, 'Primero configura portal-private/config.php siguiendo la guía de instalación.');
    }
    $config = require $path;
    if (!is_array($config)) {
        throw new PortalError(503, 'La configuración no es válida.');
    }
    date_default_timezone_set($config['timezone'] ?? 'America/Lima');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', '1800');
        session_name('PISCOSESSID');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $config['secure_cookies'] ?? true,
            'httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
    if (isset($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_seen'] = time();
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return new PortalApp($config);
}

function portal_headers(): void
{
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function portal_json(array $body, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    exit;
}

function portal_error(Throwable $error, bool $json = false): never
{
    if ($error instanceof PortalError) {
        $status = $error->status;
        $message = $error->getMessage();
    } else {
        $status = 503;
        $message = 'No se pudo completar la operación. Revisa la configuración de MySQL o consulta al responsable del sitio.';
        error_log('Portal ICA: '.get_class($error).' ('.$error->getCode().')');
    }
    if ($json) {
        portal_json(['message' => $message], $status);
    }
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Portal ICA</title><main style="font:18px/1.6 system-ui;max-width:700px;margin:60px auto;padding:24px"><h1>Portal ICA</h1><p>'.htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'</p></main></html>';
    exit;
}
