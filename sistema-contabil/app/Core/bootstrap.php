<?php

/**
 * Inicialização comum a todas as requisições: carregamento das classes, configuração,
 * sessão segura, cabeçalhos de segurança e tratamento de erros.
 */

declare(strict_types=1);

// Carrega as classes App\... a partir da pasta app/ (App\Models\Cliente => app/Models/Cliente.php).
spl_autoload_register(static function (string $classe): void {
    if (str_starts_with($classe, 'App\\')) {
        $arquivo = RAIZ . '/app/' . str_replace('\\', '/', substr($classe, 4)) . '.php';
        if (is_file($arquivo)) {
            require $arquivo;
        }
    }
});

require RAIZ . '/app/Core/helpers.php';

date_default_timezone_set(config('app.fuso_horario'));
mb_internal_encoding('UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', RAIZ . '/storage/logs/erros.log');

// Qualquer erro inesperado: registra no log e mostra uma página amigável, sem detalhes técnicos.
set_exception_handler(static function (Throwable $erro): void {
    error_log(sprintf('[%s] %s em %s:%d', get_class($erro), $erro->getMessage(), $erro->getFile(), $erro->getLine()));
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (config('app.debug')) {
        echo '<pre>' . e((string) $erro) . '</pre>';
        return;
    }
    \App\Core\View::renderizar('erros/500', ['titulo' => 'Erro'], 'simples');
});

// Avisos do PHP (variável indefinida etc.) viram exceções: falham de forma visível, em vez de gravar dado errado.
set_error_handler(static function (int $nivel, string $mensagem, string $arquivo, int $linha): bool {
    if (!(error_reporting() & $nivel)) {
        return false;
    }
    throw new ErrorException($mensagem, 0, $nivel, $arquivo, $linha);
});

// Sessão: cookie inacessível ao JavaScript, não enviado por outros sites e só aceito se criado pelo servidor.
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('SISCONTABIL');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Cabeçalhos de segurança: sem scripts ou estilos de fora, sem a página dentro de outro site.
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self'; "
    . "frame-ancestors 'none'; form-action 'self'; base-uri 'self'; object-src 'none'");
