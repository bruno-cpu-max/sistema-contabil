<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Validador;

/**
 * Solicitações de documentos aos clientes e os arquivos recebidos.
 *
 * Fluxo: o escritório registra o pedido (Aguardando envio) → o arquivo chega e é anexado
 * (Recebido) → o contador confere (Conferido). Pedido aguardando com prazo vencido é "atrasado".
 */
final class Documento extends Model
{
    /** Situação => [rótulo, cor do selo] */
    public const SITUACOES = [
        'aguardando' => ['Aguardando envio', 'aviso'],
        'recebido' => ['Recebido', 'info'],
        'conferido' => ['Conferido', 'sucesso'],
    ];

    public const TIPOS_COMUNS = [
        'Extrato bancário', 'Notas fiscais de entrada', 'Notas fiscais de saída', 'Folha de ponto dos funcionários',
        'Folha de pagamento assinada', 'Relatório de faturamento', 'Comprovantes de pagamento', 'Contrato social',
        'Informe de rendimentos', 'Recibos de atendimentos', 'Comprovante de endereço',
    ];

    public function listar(array $filtros, int $limite, int $offset): array
    {
        [$onde, $parametros] = $this->condicoes($filtros);
        return $this->executar(
            "SELECT d.*, c.nome AS cliente_nome, c.nome_fantasia AS cliente_fantasia,
                    (d.situacao = 'aguardando' AND d.data_limite < :hoje_atraso) AS atrasado
               FROM documentos d
               JOIN clientes c ON c.id = d.cliente_id
              WHERE {$onde}
              ORDER BY FIELD(d.situacao, 'aguardando', 'recebido', 'conferido'), d.data_limite, d.id
              LIMIT :limite OFFSET :offset",
            $parametros + ['hoje_atraso' => hoje(), 'limite' => $limite, 'offset' => $offset]
        )->fetchAll();
    }

    public function contar(array $filtros): int
    {
        [$onde, $parametros] = $this->condicoes($filtros);
        return (int) $this->executar(
            "SELECT COUNT(*) FROM documentos d JOIN clientes c ON c.id = d.cliente_id WHERE {$onde}",
            $parametros
        )->fetchColumn();
    }

    public function buscar(int $id): ?array
    {
        $documento = $this->executar(
            "SELECT d.*, c.nome AS cliente_nome, (d.situacao = 'aguardando' AND d.data_limite < :hoje_atraso) AS atrasado
               FROM documentos d JOIN clientes c ON c.id = d.cliente_id
              WHERE d.id = :id",
            ['id' => $id, 'hoje_atraso' => hoje()]
        )->fetch();
        return $documento ?: null;
    }

    public function doCliente(int $clienteId): array
    {
        return $this->executar(
            "SELECT d.*, (d.situacao = 'aguardando' AND d.data_limite < :hoje_atraso) AS atrasado
               FROM documentos d
              WHERE d.cliente_id = :cliente
              ORDER BY FIELD(d.situacao, 'aguardando', 'recebido', 'conferido'), d.data_limite, d.id",
            ['cliente' => $clienteId, 'hoje_atraso' => hoje()]
        )->fetchAll();
    }

    /** Arquivos de um cliente (para apagar do disco quando o cliente é excluído). */
    public function arquivosDoCliente(int $clienteId): array
    {
        return $this->executar(
            'SELECT arquivo_salvo FROM documentos WHERE cliente_id = :cliente AND arquivo_salvo IS NOT NULL',
            ['cliente' => $clienteId]
        )->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function resumo(): array
    {
        $linha = $this->executar(
            "SELECT COALESCE(SUM(situacao = 'aguardando'), 0) AS aguardando,
                    COALESCE(SUM(situacao = 'aguardando' AND data_limite < :hoje), 0) AS atrasados,
                    COALESCE(SUM(situacao = 'recebido'), 0) AS recebidos,
                    COALESCE(SUM(situacao = 'conferido'), 0) AS conferidos
               FROM documentos",
            ['hoje' => hoje()]
        )->fetch();
        return array_map('intval', $linha);
    }

    /** Quantos documentos cada cliente tem em cada situação (organização por cliente). */
    public function resumoPorCliente(): array
    {
        return $this->executar(
            "SELECT c.id, c.nome, c.nome_fantasia,
                    SUM(d.situacao = 'aguardando') AS aguardando,
                    SUM(d.situacao = 'recebido') AS recebidos,
                    SUM(d.situacao = 'conferido') AS conferidos
               FROM documentos d JOIN clientes c ON c.id = d.cliente_id
              GROUP BY c.id, c.nome, c.nome_fantasia
              ORDER BY aguardando DESC, c.nome"
        )->fetchAll();
    }

    /** Pedidos ainda sem resposta, do prazo mais próximo ao mais distante (painel). */
    public function aguardandoEnvio(int $limite = 5): array
    {
        return $this->executar(
            "SELECT d.*, c.nome AS cliente_nome, c.nome_fantasia AS cliente_fantasia,
                    (d.data_limite < :hoje_atraso) AS atrasado
               FROM documentos d JOIN clientes c ON c.id = d.cliente_id
              WHERE d.situacao = 'aguardando'
              ORDER BY d.data_limite, d.id
              LIMIT :limite",
            ['hoje_atraso' => hoje(), 'limite' => $limite]
        )->fetchAll();
    }

    public function criar(array $dados): int
    {
        $this->executar(
            'INSERT INTO documentos (cliente_id, titulo, descricao, data_limite, situacao)
             VALUES (:cliente_id, :titulo, :descricao, :data_limite, :situacao)',
            $dados
        );
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        $this->executar(
            'UPDATE documentos SET cliente_id = :cliente_id, titulo = :titulo, descricao = :descricao,
                    data_limite = :data_limite, situacao = :situacao
              WHERE id = :id',
            $dados + ['id' => $id]
        );
    }

    /** Guarda os dados do arquivo recebido. */
    public function anexarArquivo(int $id, array $arquivo, string $nomeSalvo): void
    {
        $this->executar(
            'UPDATE documentos
                SET arquivo_nome = :nome, arquivo_salvo = :salvo, arquivo_tipo = :tipo, arquivo_tamanho = :tamanho, enviado_em = NOW()
              WHERE id = :id',
            [
                'nome' => $arquivo['nome_original'],
                'salvo' => $nomeSalvo,
                'tipo' => $arquivo['mime'],
                'tamanho' => $arquivo['tamanho'],
                'id' => $id,
            ]
        );
    }

    public function mudarSituacao(int $id, string $situacao): void
    {
        $this->executar('UPDATE documentos SET situacao = :situacao WHERE id = :id', ['situacao' => $situacao, 'id' => $id]);
    }

    public function excluir(int $id): void
    {
        $this->executar('DELETE FROM documentos WHERE id = :id', ['id' => $id]);
    }

    public static function validar(array $entrada): array
    {
        $v = new Validador();

        $clienteId = ctype_digit($entrada['cliente_id']) ? (int) $entrada['cliente_id'] : 0;
        if ($clienteId < 1) {
            $v->erro('cliente_id', 'Selecione o cliente que deve enviar o documento.');
        }

        $v->obrigatorio('titulo', $entrada['titulo'], 'Informe qual documento está sendo solicitado.');
        $v->tamanhoMaximo('titulo', $entrada['titulo'], 150);
        $v->tamanhoMaximo('descricao', $entrada['descricao'], 2000);
        $dataLimite = $v->data('data_limite', $entrada['data_limite'], 'Informe uma data limite válida para o envio.');
        $v->opcao('situacao', $entrada['situacao'], array_keys(self::SITUACOES), 'Escolha uma situação da lista.');

        return [[
            'cliente_id' => $clienteId,
            'titulo' => $entrada['titulo'],
            'descricao' => $entrada['descricao'] ?: null,
            'data_limite' => $dataLimite,
            'situacao' => $entrada['situacao'],
        ], $v->erros()];
    }

    private function condicoes(array $f): array
    {
        $onde = ['1 = 1'];
        $parametros = [];

        if ($f['busca'] !== '') {
            $onde[] = "(d.titulo LIKE :busca1 ESCAPE '!' OR c.nome LIKE :busca2 ESCAPE '!' OR c.nome_fantasia LIKE :busca3 ESCAPE '!')";
            $parametros += ['busca1' => self::termoLike($f['busca']), 'busca2' => self::termoLike($f['busca']), 'busca3' => self::termoLike($f['busca'])];
        }
        if ($f['cliente_id'] !== null) {
            $onde[] = 'd.cliente_id = :cliente';
            $parametros['cliente'] = $f['cliente_id'];
        }
        if ($f['situacao'] === 'atrasado') {
            $onde[] = "d.situacao = 'aguardando' AND d.data_limite < :hoje_filtro";
            $parametros['hoje_filtro'] = hoje();
        } elseif (isset(self::SITUACOES[$f['situacao']])) {
            $onde[] = 'd.situacao = :situacao';
            $parametros['situacao'] = $f['situacao'];
        }
        if ($f['de'] !== null) {
            $onde[] = 'd.data_limite >= :de';
            $parametros['de'] = $f['de'];
        }
        if ($f['ate'] !== null) {
            $onde[] = 'd.data_limite <= :ate';
            $parametros['ate'] = $f['ate'];
        }

        return [implode(' AND ', $onde), $parametros];
    }
}
