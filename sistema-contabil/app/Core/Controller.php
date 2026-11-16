<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base dos controllers: renderizar views, redirecionar e ler dados da requisição com segurança.
 */
abstract class Controller
{
    protected function view(string $view, array $dados = [], string $layout = 'principal'): void
    {
        View::renderizar($view, $dados, $layout);
    }

    protected function redirecionar(string $rota, array $parametros = []): void
    {
        header('Location: ' . url($rota, $parametros));
        exit;
    }

    /** Texto enviado pelo formulário, sem espaços nas pontas ('' se ausente ou se não for texto). */
    protected function entrada(string $campo): string
    {
        $valor = $_POST[$campo] ?? '';
        return is_string($valor) ? trim($valor) : '';
    }

    /** Parâmetro de filtro da URL (?busca=...), sempre como texto. */
    protected function filtro(string $campo): string
    {
        $valor = $_GET[$campo] ?? '';
        return is_string($valor) ? trim($valor) : '';
    }

    /** ID da URL ou do formulário; se for inválido, a página é "não encontrada". */
    protected function idObrigatorio(): int
    {
        $valor = $_POST['id'] ?? $_GET['id'] ?? '';
        if (!is_string($valor) || !ctype_digit($valor) || (int) $valor < 1) {
            $this->naoEncontrado();
        }
        return (int) $valor;
    }

    protected function idOpcional(): ?int
    {
        $valor = $_POST['id'] ?? $_GET['id'] ?? '';
        return is_string($valor) && ctype_digit($valor) && (int) $valor > 0 ? (int) $valor : null;
    }

    protected function paginaAtual(): int
    {
        $pagina = $_GET['pagina'] ?? '1';
        return is_string($pagina) && ctype_digit($pagina) && (int) $pagina > 0 ? (int) $pagina : 1;
    }

    /** Data do filtro da URL (AAAA-MM-DD) ou null se vazia/inválida. */
    protected function dataFiltro(string $campo): ?string
    {
        $valor = $this->filtro($campo);
        $data = \DateTime::createFromFormat('!Y-m-d', $valor);
        return $data && $data->format('Y-m-d') === $valor ? $valor : null;
    }

    /** ID numérico do filtro da URL (ex.: cliente) ou null. */
    protected function idFiltro(string $campo): ?int
    {
        $valor = $this->filtro($campo);
        return ctype_digit($valor) && (int) $valor > 0 ? (int) $valor : null;
    }

    /**
     * Depois de uma ação (concluir, excluir...), volta para a mesma página de onde ela foi feita,
     * mantendo filtros e paginação. Só aceita endereços do próprio sistema.
     */
    protected function voltar(string $rotaPadrao, array $parametros = []): void
    {
        $destino = $_POST['voltar'] ?? '';
        if (is_string($destino) && str_starts_with($destino, $_SERVER['SCRIPT_NAME'] . '?') && !preg_match('/[\r\n]/', $destino)) {
            header('Location: ' . $destino);
            exit;
        }
        $this->redirecionar($rotaPadrao, $parametros);
    }

    /** Endereço da página anterior, se for do próprio sistema (para voltar a ela depois de salvar). */
    protected function origem(): string
    {
        $voltar = $_POST['voltar'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        if (!is_string($voltar)) {
            return '';
        }
        $caminho = (string) parse_url($voltar, PHP_URL_PATH);
        $consulta = (string) parse_url($voltar, PHP_URL_QUERY);
        $endereco = $caminho . ($consulta !== '' ? '?' . $consulta : '');
        return str_starts_with($endereco, $_SERVER['SCRIPT_NAME'] . '?') && !str_contains($endereco, '/form') ? $endereco : '';
    }

    protected function naoEncontrado(): void
    {
        http_response_code(404);
        View::renderizar('erros/404', ['titulo' => 'Página não encontrada']);
        exit;
    }
}
