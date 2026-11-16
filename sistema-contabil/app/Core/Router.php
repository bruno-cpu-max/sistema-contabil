<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Roteador: liga cada endereço (index.php?r=clientes/form) ao método do controller que o atende.
 *
 * Antes de chamar o controller ele aplica as regras que valem para o sistema todo:
 * página interna só com login, e envio de formulário (POST) só com token CSRF válido.
 */
final class Router
{
    /** @var array<string, array<string, array{0: class-string, 1: string, 2: bool}>> */
    private array $rotas = ['GET' => [], 'POST' => []];

    public function get(string $rota, string $controller, string $acao, bool $publica = false): void
    {
        $this->rotas['GET'][$rota] = [$controller, $acao, $publica];
    }

    public function post(string $rota, string $controller, string $acao, bool $publica = false): void
    {
        $this->rotas['POST'][$rota] = [$controller, $acao, $publica];
    }

    public function despachar(string $rota, string $metodo): void
    {
        $rota = trim($rota, '/') ?: 'dashboard';
        $metodo = strtoupper($metodo) === 'POST' ? 'POST' : 'GET';
        $definicao = $this->rotas[$metodo][$rota] ?? null;

        if ($definicao === null) {
            $outroMetodo = $metodo === 'POST' ? 'GET' : 'POST';
            http_response_code(isset($this->rotas[$outroMetodo][$rota]) ? 405 : 404);
            View::renderizar('erros/404', ['titulo' => 'Página não encontrada'], Auth::logado() ? 'principal' : 'simples');
            return;
        }

        [$controller, $acao, $publica] = $definicao;

        if (!$publica && !Auth::logado()) {
            // Na entrada normal (painel) basta a tela de login; o aviso é para quem abriu outra página.
            if ($rota !== 'dashboard') {
                Flash::erro('Faça login para continuar.');
            }
            header('Location: ' . url('login'));
            exit;
        }

        if ($metodo === 'POST') {
            // Envio maior que o limite do PHP (post_max_size): o PHP descarta o formulário inteiro.
            if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                Flash::erro('O envio é maior do que o servidor permite. Escolha um arquivo menor.');
                header('Location: ' . url(explode('/', $rota)[0]));
                exit;
            }
            if (!Csrf::valido($_POST['_csrf'] ?? null)) {
                http_response_code(403);
                View::renderizar('erros/csrf', ['titulo' => 'Sessão expirada'], Auth::logado() ? 'principal' : 'simples');
                return;
            }
        }

        (new $controller())->$acao();
    }
}
