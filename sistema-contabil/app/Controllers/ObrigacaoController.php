<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Paginacao;
use App\Models\Cliente;
use App\Models\Obrigacao;

final class ObrigacaoController extends Controller
{
    private const CAMPOS = ['cliente_id', 'nome', 'descricao', 'valor', 'vencimento', 'status'];

    public function index(): void
    {
        $filtros = [
            'busca' => $this->filtro('busca'),
            'cliente_id' => $this->idFiltro('cliente_id'),
            'situacao' => in_array($this->filtro('situacao'), ['pendente', 'vencida', 'concluida'], true) ? $this->filtro('situacao') : '',
            'de' => $this->dataFiltro('de'),
            'ate' => $this->dataFiltro('ate'),
        ];

        $obrigacoes = new Obrigacao();
        $paginacao = new Paginacao($obrigacoes->contar($filtros), $this->paginaAtual(), config('app.itens_por_pagina'));

        $this->view('obrigacoes/index', [
            'titulo' => 'Obrigações fiscais',
            'filtros' => $filtros,
            'obrigacoes' => $obrigacoes->listar($filtros, $paginacao->porPagina, $paginacao->offset()),
            'paginacao' => $paginacao,
            'clientes' => (new Cliente())->opcoes(),
        ]);
    }

    public function formulario(): void
    {
        $id = $this->idOpcional();
        $obrigacao = array_fill_keys(self::CAMPOS, '');
        $obrigacao['status'] = 'pendente';
        // Vindo da ficha do cliente, o cliente já vem escolhido.
        $obrigacao['cliente_id'] = (string) ($this->idFiltro('cliente_id') ?? '');

        if ($id !== null) {
            $obrigacao = (new Obrigacao())->buscar($id) ?? $this->naoEncontrado();
            $obrigacao['valor'] = number_format((float) $obrigacao['valor'], 2, ',', '.');
        }

        $this->exibirFormulario($id, $obrigacao, []);
    }

    public function salvar(): void
    {
        $id = $this->idOpcional();
        $obrigacoes = new Obrigacao();
        if ($id !== null && $obrigacoes->buscar($id) === null) {
            $this->naoEncontrado();
        }

        $entrada = [];
        foreach (self::CAMPOS as $campo) {
            $entrada[$campo] = $this->entrada($campo);
        }

        [$dados, $erros] = Obrigacao::validar($entrada);
        if (!isset($erros['cliente_id']) && (new Cliente())->buscar($dados['cliente_id']) === null) {
            $erros['cliente_id'] = 'Cliente não encontrado. Selecione um cliente da lista.';
        }

        if ($erros !== []) {
            http_response_code(422);
            $this->exibirFormulario($id, $entrada, $erros);
            return;
        }

        if ($id === null) {
            $obrigacoes->criar($dados);
            Flash::sucesso('Obrigação cadastrada com sucesso.');
        } else {
            $obrigacoes->atualizar($id, $dados);
            Flash::sucesso('Obrigação atualizada.');
        }
        $this->voltar('obrigacoes');
    }

    /** Ação rápida das listagens e do painel: marca como concluída. */
    public function concluir(): void
    {
        $id = $this->idObrigatorio();
        $obrigacoes = new Obrigacao();
        $obrigacao = $obrigacoes->buscar($id) ?? $this->naoEncontrado();

        $obrigacoes->concluir($id);
        Flash::sucesso('"' . $obrigacao['nome'] . '" de ' . $obrigacao['cliente_nome'] . ' marcada como concluída.');
        $this->voltar('obrigacoes');
    }

    public function excluir(): void
    {
        $id = $this->idObrigatorio();
        $obrigacoes = new Obrigacao();
        $obrigacoes->buscar($id) ?? $this->naoEncontrado();

        $obrigacoes->excluir($id);
        Flash::sucesso('Obrigação excluída.');
        $this->voltar('obrigacoes');
    }

    private function exibirFormulario(?int $id, array $obrigacao, array $erros): void
    {
        $this->view('obrigacoes/form', [
            'titulo' => $id ? 'Editar obrigação' : 'Nova obrigação',
            'id' => $id,
            'obrigacao' => $obrigacao,
            'erros' => $erros,
            'clientes' => (new Cliente())->opcoes(),
            // Para onde voltar depois de salvar (a página de onde o formulário foi aberto).
            'voltar' => $this->origem(),
        ]);
    }
}
