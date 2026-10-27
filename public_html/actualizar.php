<?php
declare(strict_types=1);
require dirname(__DIR__).'/portal-private/bootstrap.php';portal_headers();
$error='';$done=false;
try{
    $app=portal_app();
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
        $app->checkCsrf(is_string($_POST['_csrf']??null)?$_POST['_csrf']:'');
        $config=require(getenv('PORTAL_CONFIG_FILE')?:dirname(__DIR__).'/portal-private/config.php');$key=$config['install_key']??'';
        if(!is_string($key)||strlen($key)<32||!is_string($_POST['owner_key']??null)||!hash_equals($key,$_POST['owner_key']))throw new PortalError(403,'La clave privada no coincide con install_key.');
        $lock=fopen(dirname(__DIR__).'/portal-private/upgrade.lock','c+');if(!$lock||!flock($lock,LOCK_EX))throw new PortalError(503,'No se pudo proteger la actualización.');
        try{
            $db=$app->db();$state=$db->query('SELECT data,revision FROM pisco_state WHERE id=1')->fetch();
            if(!$state)throw new PortalError(422,'Primero debe existir una instalación del portal.');
            $db->exec('CREATE TABLE IF NOT EXISTS pisco_upgrade_backups (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,state_data LONGTEXT NOT NULL,accounts_data LONGTEXT NOT NULL) ENGINE=InnoDB');
            $accounts=$db->query('SELECT * FROM pisco_users')->fetchAll();
            $db->prepare('INSERT INTO pisco_upgrade_backups (state_data,accounts_data) VALUES (?,?)')->execute([$state['data'],json_encode($accounts,JSON_THROW_ON_ERROR)]);
            PortalSchema::apply($db);$done=true;
        }finally{flock($lock,LOCK_UN);fclose($lock);}
    }
}catch(Throwable $exception){if(!$exception instanceof PortalError)portal_error($exception);$error=$exception->getMessage();http_response_code($exception->status);}
function e(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Actualizar portal ICA</title><link rel="stylesheet" href="/forms.css"></head><body><main><h1>Actualizar portal ICA</h1>
<?php if($error):?><p class="notice" role="alert"><?=e($error)?></p><?php endif ?>
<?php if($done):?><section><p class="notice success" role="status">Actualización completada. Se conservaron los usuarios, sus contraseñas y el contenido del portal.</p><p>La base incluye ahora roles, permisos, archivos compartidos y un contador de visitas real. El tráfico comienza a contarse desde esta versión; las cifras de demostración dejan de mostrarse.</p><a href="/#/admin">Abrir la intranet</a><p>Elimina actualizar.php desde hPanel después de verificar el acceso.</p></section>
<?php else:?><section><p>Este paso actualiza la instalación existente. No reinstala ni reemplaza el contenido inicial. Antes de subir la actualización, conserva una copia de archivos y base de datos desde hPanel.</p><form method="post"><input type="hidden" name="_csrf" value="<?=e($_SESSION['csrf']??'')?>"><label>Clave privada de instalación (install_key)<input type="password" name="owner_key" required minlength="32" autocomplete="off"></label><button>Actualizar conservando los datos</button></form></section><?php endif ?>
</main></body></html>
