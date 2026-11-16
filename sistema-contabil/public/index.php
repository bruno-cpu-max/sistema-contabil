<?php

/**
 * Ponto único de entrada do sistema: toda página passa por aqui (index.php?r=rota).
 */

declare(strict_types=1);

define('RAIZ', dirname(__DIR__));

require RAIZ . '/app/Core/bootstrap.php';

/** @var App\Core\Router $router */
$router = require RAIZ . '/routes/web.php';

$rota = is_string($_GET['r'] ?? null) ? $_GET['r'] : 'dashboard';
$router->despachar($rota, $_SERVER['REQUEST_METHOD'] ?? 'GET');
