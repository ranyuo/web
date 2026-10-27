<?php
declare(strict_types=1);
require dirname(__DIR__).'/portal-private/bootstrap.php';
portal_headers();
$route = is_string($_GET['route'] ?? null) ? trim($_GET['route'], '/') : '';
try {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!in_array($path, ['/', '/index.php'], true)) throw new PortalError(404, 'Página no encontrada.');
    $app = portal_app();
    if ($route !== '') {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method !== 'GET') {
            $app->checkCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        }
        if ($route === 'api/archivo' && $method === 'GET') PortalFiles::serve($app, is_string($_GET['id']??null)?$_GET['id']:'');
        if ($route === 'api/archivos' && $method === 'POST') portal_json($app->upload(),201);
        // Cerrar sesión no necesita un cuerpo JSON; la protección CSRF ya se comprobó.
        if ($route === 'api/session' && $method === 'DELETE') portal_json($app->logout());
        $input = $method === 'GET' ? [] : $app->input();
        if ($route === 'api/desafio' && $method === 'POST') portal_json($app->challenge($input));
        if ($route === 'api/eventos' && $method === 'POST') portal_json($app->interfaceEvent($input));
        if ($route === 'api/cuentas' && $method === 'POST') portal_json($app->accountOperation($input));
        if ($route === 'api/password' && $method === 'POST') { $app->changePassword($input); portal_json(['ok'=>true]); }
        if ($route === 'api/empresas' && $method === 'POST') portal_json($app->registerCompany($input),201);
        if ($route === 'api/visitas' && $method === 'POST') portal_json($app->track($input));
        if ($route === 'api/portal' && $method === 'GET') portal_json($app->bootstrap());
        if ($route === 'api/portal' && $method === 'PUT') portal_json($app->save($input));
        if ($route === 'api/session' && $method === 'POST') portal_json($app->login($input));
        if (preg_match('#^api/solicitudes/([a-z]+)$#', $route, $match) && $method === 'POST') {
            $result = $app->submit($match[1], $input);
            $status = $result['status']; unset($result['status']);
            portal_json($result, $status);
        }
        if (preg_match('#^api/consultas/([a-z]+)$#', $route, $match) && $method === 'POST') portal_json($app->lookup($match[1], $input));
        throw new PortalError(404, 'Ruta no encontrada.');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') throw new PortalError(405, 'Método no permitido.');
    $bootstrap = $app->bootstrap();
    header('Content-Type: text/html; charset=utf-8');
    require dirname(__DIR__).'/portal-private/views/portal.php';
} catch (Throwable $error) {
    portal_error($error, $route !== '');
}
