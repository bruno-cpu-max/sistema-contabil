<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Paginacao;
use App\Core\Upload;
use App\Models\Cliente;
use App\Models\Documento;
use App\Models\Obrigacao;

final class ClienteController extends Controller
{
    private const CAMPOS = ['nome', 'nome_fantasia', 'documento', 'email', 'telefone', 'endereco', 'cidade', 'observacoes'];

    public function index(): void
    {
        $clientes = new Cliente();
        $busca = $this->filtro('busca');
        $paginacao = new Paginacao($clientes->contar($busca), $this->paginaAtual(), config('app.itens_por_pagina'));

        $this->view('clientes/index', [
            'titulo' => 'Clientes',
            'busca' => $busca,
            'clientes' => $clientes->listar($busca, $paginacao->porPagina, $paginacao->offset()),
            'paginacao' => $paginacao,
        ]);
    }

    /** Formulário de cadastro (sem id) ou de edição (com id). */
    public function formulario(): void
    {
        $id = $this->idOpcional();
        $cliente = array_fill_keys(self::CAMPOS, '');

        if ($id !== null) {
            $cliente = (new Cliente())->buscar($id) ?? $this->naoEncontrado();
            $cliente['documento'] = documento_formatado($cliente['documento']);
            $cliente['telefone'] = telefone_formatado($cliente['telefone']);
        }

        $this->view('clientes/form', [
            'titulo' => $id ? 'Editar cliente' : 'Novo cliente',
            'id' => $id,
            'cliente' => $cliente,
        ]);
    }

    public function salvar(): void
    {
        $id = $this->idOpcional();
        $clientes = new Cliente();
        if ($id !== null && $clientes->buscar($id) === null) {
            $this->naoEncontrado();
        }

        $entrada = [];
        foreach (self::CAMPOS as $campo) {
            $entrada[$campo] = $this->entrada($campo);
        }

        [$dados, $erros] = Cliente::validar($entrada);
        if (!isset($erros['documento']) && $clientes->documentoEmUso($dados['documento'], $id)) {
            $erros['documento'] = 'Já existe um cliente cadastrado com este CPF/CNPJ.';
        }

        if ($erros !== []) {
            http_response_code(422);
            $this->view('clientes/form', [
                'titulo' => $id ? 'Editar cliente' : 'Novo cliente',
                'id' => $id,
                'cliente' => $entrada,
                'erros' => $erros,
            ]);
            return;
        }

        if ($id === null) {
            $id = $clientes->criar($dados);
            Flash::sucesso('Cliente cadastrado com sucesso.');
        } else {
            $clientes->atualizar($id, $dados);
            Flash::sucesso('Cadastro do cliente atualizado.');
        }
        $this->redirecionar('clientes/ver', ['id' => $id]);
    }

    /** Ficha do cliente com as obrigações e os documentos dele. */
    public function ver(): void
    {
        $id = $this->idObrigatorio();
        $cliente = (new Cliente())->buscar($id) ?? $this->naoEncontrado();

        $this->view('clientes/ver', [
            'titulo' => $cliente['nome_fantasia'] ?: $cliente['nome'],
            'cliente' => $cliente,
            'obrigacoes' => (new Obrigacao())->doCliente($id),
            'documentos' => (new Documento())->doCliente($id),
        ]);
    }

    public function excluir(): void
    {
        $id = $this->idObrigatorio();
        $clientes = new Cliente();
        $cliente = $clientes->buscar($id) ?? $this->naoEncontrado();

        // Os registros saem do banco em cascata; os arquivos recebidos são apagados do disco.
        $arquivos = (new Documento())->arquivosDoCliente($id);
        $clientes->excluir($id);
        foreach ($arquivos as $arquivo) {
            Upload::remover($arquivo);
        }

        Flash::sucesso('Cliente "' . $cliente['nome'] . '" excluído, com suas obrigações e documentos.');
        $this->redirecionar('clientes');
    }
}
