<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Paginacao;
use App\Core\Upload;
use App\Models\Cliente;
use App\Models\Documento;

/**
 * Documentos entre o escritório e os clientes: pedido, arquivo recebido e conferência.
 */
final class DocumentoController extends Controller
{
    private const CAMPOS = ['cliente_id', 'titulo', 'descricao', 'data_limite', 'situacao'];

    public function index(): void
    {
        $situacoes = array_merge(array_keys(Documento::SITUACOES), ['atrasado']);
        $filtros = [
            'busca' => $this->filtro('busca'),
            'cliente_id' => $this->idFiltro('cliente_id'),
            'situacao' => in_array($this->filtro('situacao'), $situacoes, true) ? $this->filtro('situacao') : '',
            'de' => $this->dataFiltro('de'),
            'ate' => $this->dataFiltro('ate'),
        ];

        $documentos = new Documento();
        $paginacao = new Paginacao($documentos->contar($filtros), $this->paginaAtual(), config('app.itens_por_pagina'));

        $this->view('documentos/index', [
            'titulo' => 'Documentos',
            'filtros' => $filtros,
            'documentos' => $documentos->listar($filtros, $paginacao->porPagina, $paginacao->offset()),
            'paginacao' => $paginacao,
            'resumo' => $documentos->resumo(),
            'porCliente' => $documentos->resumoPorCliente(),
            'clientes' => (new Cliente())->opcoes(),
        ]);
    }

    public function formulario(): void
    {
        $id = $this->idOpcional();
        $documento = array_fill_keys(self::CAMPOS, '');
        $documento['situacao'] = 'aguardando';
        $documento['data_limite'] = date('Y-m-d', strtotime('+5 days'));
        $documento['cliente_id'] = (string) ($this->idFiltro('cliente_id') ?? '');

        if ($id !== null) {
            $documento = (new Documento())->buscar($id) ?? $this->naoEncontrado();
        }

        $this->exibirFormulario($id, $documento, []);
    }

    public function salvar(): void
    {
        $id = $this->idOpcional();
        $documentos = new Documento();
        $existente = null;
        if ($id !== null) {
            $existente = $documentos->buscar($id) ?? $this->naoEncontrado();
        }

        $entrada = [];
        foreach (self::CAMPOS as $campo) {
            $entrada[$campo] = $this->entrada($campo);
        }

        [$dados, $erros] = Documento::validar($entrada);
        if (!isset($erros['cliente_id']) && (new Cliente())->buscar($dados['cliente_id']) === null) {
            $erros['cliente_id'] = 'Cliente não encontrado. Selecione um cliente da lista.';
        }

        [$arquivo, $erroArquivo] = Upload::validar($_FILES['arquivo'] ?? null);
        if ($erroArquivo !== null) {
            $erros['arquivo'] = $erroArquivo;
        }

        if ($erros !== []) {
            http_response_code(422);
            // O navegador não reenvia arquivos: a pessoa escolhe o arquivo de novo (avisado na tela).
            $this->exibirFormulario($id, $entrada + ($existente ?? []), $erros);
            return;
        }

        // Arquivo chegou para um pedido que estava aguardando: passa a "recebido" sozinho.
        if ($arquivo !== null && $dados['situacao'] === 'aguardando') {
            $dados['situacao'] = 'recebido';
        }

        if ($id === null) {
            $id = $documentos->criar($dados);
            $mensagem = 'Solicitação de documento registrada.';
        } else {
            $documentos->atualizar($id, $dados);
            $mensagem = 'Solicitação de documento atualizada.';
        }

        if ($arquivo !== null) {
            $nomeSalvo = Upload::salvar($arquivo);
            $documentos->anexarArquivo($id, $arquivo, $nomeSalvo);
            // O arquivo antigo (se havia) é substituído pelo novo.
            Upload::remover($existente['arquivo_salvo'] ?? null);
            $mensagem .= ' Arquivo "' . $arquivo['nome_original'] . '" anexado.';
        }

        Flash::sucesso($mensagem);
        $this->voltar('documentos');
    }

    /** Ação rápida: marcar como recebido / conferido / aguardando. */
    public function situacao(): void
    {
        $id = $this->idObrigatorio();
        $situacao = $this->entrada('situacao');
        $documentos = new Documento();
        $documento = $documentos->buscar($id) ?? $this->naoEncontrado();

        if (!isset(Documento::SITUACOES[$situacao])) {
            Flash::erro('Situação inválida.');
            $this->voltar('documentos');
        }

        $documentos->mudarSituacao($id, $situacao);
        Flash::sucesso('"' . $documento['titulo'] . '" agora está como: ' . Documento::SITUACOES[$situacao][0] . '.');
        $this->voltar('documentos');
    }

    public function excluir(): void
    {
        $id = $this->idObrigatorio();
        $documentos = new Documento();
        $documento = $documentos->buscar($id) ?? $this->naoEncontrado();

        $documentos->excluir($id);
        Upload::remover($documento['arquivo_salvo']);
        Flash::sucesso('Solicitação de documento excluída.');
        $this->voltar('documentos');
    }

    /**
     * Entrega o arquivo anexado (só para quem está logado: o roteador já exigiu o login).
     * PDF e imagens abrem no navegador; os demais são baixados.
     */
    public function arquivo(): void
    {
        $id = $this->idObrigatorio();
        $documento = (new Documento())->buscar($id) ?? $this->naoEncontrado();
        $caminho = $documento['arquivo_salvo'] ? Upload::caminho($documento['arquivo_salvo']) : null;
        if ($caminho === null) {
            $this->naoEncontrado();
        }

        $tipo = (string) $documento['arquivo_tipo'];
        $abrirNoNavegador = in_array($tipo, ['application/pdf', 'image/jpeg', 'image/png'], true) && !isset($_GET['baixar']);
        $nome = $documento['arquivo_nome'];
        $nomeAscii = preg_replace('/[^A-Za-z0-9._-]/', '_', $nome);

        header('Content-Type: ' . ($tipo !== '' ? $tipo : 'application/octet-stream'));
        header('Content-Length: ' . filesize($caminho));
        header(sprintf(
            'Content-Disposition: %s; filename="%s"; filename*=UTF-8\'\'%s',
            $abrirNoNavegador ? 'inline' : 'attachment',
            $nomeAscii,
            rawurlencode($nome)
        ));
        header('Cache-Control: private, no-store');
        readfile($caminho);
        exit;
    }

    private function exibirFormulario(?int $id, array $documento, array $erros): void
    {
        $this->view('documentos/form', [
            'titulo' => $id ? 'Editar solicitação de documento' : 'Solicitar documento',
            'id' => $id,
            'documento' => $documento,
            'erros' => $erros,
            'clientes' => (new Cliente())->opcoes(),
            'voltar' => $this->origem(),
        ]);
    }
}
