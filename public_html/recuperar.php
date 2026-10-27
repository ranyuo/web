<?php
declare(strict_types=1);
require dirname(__DIR__).'/portal-private/bootstrap.php';
portal_headers();
$error = ''; $done = false; $users = [];
try {
    $app = portal_app();
    $config = require (getenv('PORTAL_CONFIG_FILE') ?: dirname(__DIR__).'/portal-private/config.php');
    $ownerKey = $config['install_key'] ?? '';
    if (!is_string($ownerKey) || strlen($ownerKey) < 32) throw new PortalError(503, 'Configura install_key con una clave privada de al menos 32 caracteres en portal-private/config.php.');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $app->checkCsrf(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : '');
        $window = intdiv(time(), 300) * 300;
        $rateKey = hash('sha256', 'owner-recovery|'.($_SERVER['REMOTE_ADDR'] ?? 'local').'|'.$window);
        $app->db()->prepare('INSERT INTO pisco_rate_limits (rate_key,hits,expires_at) VALUES (?,1,?) ON DUPLICATE KEY UPDATE hits = hits + 1')->execute([$rateKey, $window + 300]);
        $statement = $app->db()->prepare('SELECT hits FROM pisco_rate_limits WHERE rate_key = ?');
        $statement->execute([$rateKey]);
        if ((int)$statement->fetchColumn() > 5) throw new PortalError(429, 'Demasiados intentos. Espera cinco minutos.');
        if (($_POST['action'] ?? '') === 'authorize') {
            unset($_SESSION['owner_recovery_until']);
            if (!is_string($_POST['owner_key'] ?? null) || !hash_equals($ownerKey, $_POST['owner_key'])) throw new PortalError(403, 'La clave privada no coincide con install_key.');
            session_regenerate_id(true);
            $_SESSION['owner_recovery_until'] = time() + 300;
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            header('Location: /recuperar.php', true, 303); exit;
        }
        if (($_SESSION['owner_recovery_until'] ?? 0) <= time()) throw new PortalError(403, 'Primero verifica tu clave privada de instalación.');
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';
        $id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || !is_string($password) || !is_string($confirmation) || $password !== $confirmation
            || preg_match_all('/./us', $password) < 12 || strlen($password) > 72) {
            throw new PortalError(422, 'Elige una cuenta y una contraseña de al menos 12 caracteres y máximo 72 bytes. Las dos contraseñas deben coincidir.');
        }
        $statement = $app->db()->prepare('SELECT username FROM pisco_users WHERE id = ? AND active = 1');
        $statement->execute([$id]);
        $username = $statement->fetchColumn();
        if ($username === false) throw new PortalError(422, 'La cuenta seleccionada no está activa.');
        $columns=$app->db()->query('SHOW COLUMNS FROM pisco_users')->fetchAll(PDO::FETCH_COLUMN);
        $extra=in_array('auth_version',$columns,true)?', auth_version = auth_version + 1, login_failures = 0, locked_until = NULL':'';
        $app->db()->prepare('UPDATE pisco_users SET password = ?'.$extra.' WHERE id = ? AND active = 1')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
        $loginKey = hash('sha256', 'login:'.$username.'|'.($_SERVER['REMOTE_ADDR'] ?? 'local').'|'.$window);
        $app->db()->prepare('DELETE FROM pisco_rate_limits WHERE rate_key = ?')->execute([$loginKey]);
        unset($_SESSION['owner_recovery_until'], $_SESSION['user_id']);
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        $done = true;
    }
} catch (Throwable $exception) {
    if (!$exception instanceof PortalError) portal_error($exception);
    $error = $exception->getMessage();
    http_response_code($exception->status);
}
$authorized = isset($app) && ($_SESSION['owner_recovery_until'] ?? 0) > time();
if ($authorized) {
    try { $users = $app->db()->query('SELECT id,username,name FROM pisco_users WHERE active = 1 ORDER BY username')->fetchAll(); }
    catch (Throwable $exception) { portal_error($exception); }
}
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Recuperar acceso | Portal ICA</title><link rel="stylesheet" href="/forms.css"></head>
<body><main><h1>Recuperar acceso al portal</h1>
<?php if ($error): ?><p class="notice" role="alert"><?= e($error) ?></p><?php endif ?>
<?php if ($done): ?>
<section><p class="notice success" role="status">Contraseña actualizada. Tu usuario es <strong><?= e((string)$username) ?></strong>.</p><a href="/#/admin">Entrar con mi nueva contraseña</a><p class="muted">Elimina recuperar.php desde el administrador de archivos de Hostinger al terminar.</p></section>
<?php elseif ($authorized): ?>
<section><h2>Elige tu cuenta y una contraseña nueva</h2><form method="post">
<input type="hidden" name="_csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>"><input type="hidden" name="action" value="reset">
<label>Usuario<select name="user_id" required style="display:block;width:100%;font:inherit;padding:12px"><?php foreach ($users as $user): ?><option value="<?= (int)$user['id'] ?>"><?= e($user['username'].' — '.$user['name']) ?></option><?php endforeach ?></select></label>
<label>Contraseña nueva<input type="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>
<label>Repetir contraseña<input type="password" name="password_confirmation" required minlength="12" maxlength="72" autocomplete="new-password"></label><button>Guardar nueva contraseña</button>
</form><p class="muted">La autorización dura cinco minutos. No se borra el contenido del portal.</p></section>
<?php else: ?>
<section><p>Para recuperar una cuenta necesitas acceder a la configuración privada del hosting.</p><p>En hPanel abre <code>portal-private/config.php</code> y consulta el valor de <code>install_key</code>. Si no lo recuerdas, puedes cambiar solo ese valor por una clave aleatoria privada de al menos 32 caracteres y guardar el archivo.</p>
<form method="post"><input type="hidden" name="_csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>"><input type="hidden" name="action" value="authorize"><label>Clave privada de instalación<input type="password" name="owner_key" required minlength="32" autocomplete="off"></label><button>Ver mis usuarios</button></form>
<p class="muted">Esta clave no es la contraseña MySQL ni la contraseña de acceso al portal.</p></section>
<?php endif ?></main></body></html>
