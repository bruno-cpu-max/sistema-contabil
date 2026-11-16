<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Monta a página: o arquivo da view gera o conteúdo, e o layout coloca menu, topo e rodapé em volta.
 */
final class View
{
    public static function renderizar(string $view, array $dados = [], string $layout = 'principal'): void
    {
        $dados['titulo'] ??= config('app.nome');
        $dados['erros'] ??= [];
        $dados['flash'] = Flash::obter();

        extract($dados, EXTR_SKIP);

        ob_start();
        require RAIZ . '/app/Views/' . $view . '.php';
        $conteudo = ob_get_clean();

        require RAIZ . '/app/Views/layouts/' . $layout . '.php';
    }
}
