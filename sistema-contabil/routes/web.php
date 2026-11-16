<?php

/**
 * Rotas do sistema: endereço (index.php?r=...) => controller e método.
 *
 * Todas exigem login, menos as marcadas como públicas (true no fim).
 * Toda rota POST exige o token CSRF (verificado pelo Router).
 */

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ClienteController;
use App\Controllers\ContaController;
use App\Controllers\DashboardController;
use App\Controllers\DocumentoController;
use App\Controllers\ObrigacaoController;
use App\Core\Router;

$router = new Router();

// Acesso
$router->get('login', AuthController::class, 'formulario', true);
$router->post('login', AuthController::class, 'entrar', true);
$router->post('logout', AuthController::class, 'sair');

// Painel
$router->get('dashboard', DashboardController::class, 'index');

// Clientes
$router->get('clientes', ClienteController::class, 'index');
$router->get('clientes/form', ClienteController::class, 'formulario');
$router->post('clientes/salvar', ClienteController::class, 'salvar');
$router->get('clientes/ver', ClienteController::class, 'ver');
$router->post('clientes/excluir', ClienteController::class, 'excluir');

// Obrigações fiscais
$router->get('obrigacoes', ObrigacaoController::class, 'index');
$router->get('obrigacoes/form', ObrigacaoController::class, 'formulario');
$router->post('obrigacoes/salvar', ObrigacaoController::class, 'salvar');
$router->post('obrigacoes/concluir', ObrigacaoController::class, 'concluir');
$router->post('obrigacoes/excluir', ObrigacaoController::class, 'excluir');

// Documentos
$router->get('documentos', DocumentoController::class, 'index');
$router->get('documentos/form', DocumentoController::class, 'formulario');
$router->post('documentos/salvar', DocumentoController::class, 'salvar');
$router->post('documentos/situacao', DocumentoController::class, 'situacao');
$router->post('documentos/excluir', DocumentoController::class, 'excluir');
$router->get('documentos/arquivo', DocumentoController::class, 'arquivo');

// Minha conta
$router->get('conta', ContaController::class, 'index');
$router->post('conta/senha', ContaController::class, 'alterarSenha');

return $router;
