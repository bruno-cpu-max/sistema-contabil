<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Validador;

/**
 * Obrigações fiscais e guias.
 *
 * No banco a obrigação é "pendente" ou "concluida". A situação "vencida" é calculada na hora
 * (pendente com vencimento antes de hoje): assim ela nunca fica desatualizada e não depende
 * de nenhuma rotina agendada.
 */
final class Obrigacao extends Model
{
    /** Situação => [rótulo, cor do selo] */
    public const SITUACOES = [
        'pendente' => ['Pendente', 'aviso'],
        'vencida' => ['Vencida', 'perigo'],
        'concluida' => ['Concluída', 'sucesso'],
    ];

    /** Sugestões para o campo "obrigação" (o usuário pode digitar outra). */
    public const TIPOS_COMUNS = [
        'DAS - Simples Nacional', 'DCTFWeb', 'FGTS Digital', 'INSS - DCTFWeb', 'eSocial - Folha de Pagamento',
        'IRPJ - Lucro Presumido', 'CSLL - Lucro Presumido', 'PIS', 'COFINS', 'ISS', 'ICMS', 'DEFIS',
        'Carnê-Leão', 'IRPF - Declaração Anual', 'EFD-Reinf', 'SPED Fiscal',
    ];

    /** Expressão SQL da situação calculada (precisa do parâmetro :hoje_situacao). */
    private const SITUACAO_SQL = "CASE WHEN o.status = 'concluida' THEN 'concluida'
                                       WHEN o.vencimento < :hoje_situacao THEN 'vencida'
                                       ELSE 'pendente' END";

    public function listar(array $filtros, int $limite, int $offset): array
    {
        [$onde, $parametros] = $this->condicoes($filtros);
        $situacao = self::SITUACAO_SQL;

        return $this->executar(
            "SELECT o.*, c.nome AS cliente_nome, c.nome_fantasia AS cliente_fantasia, {$situacao} AS situacao
               FROM obrigacoes o
               JOIN clientes c ON c.id = o.cliente_id
              WHERE {$onde}
              ORDER BY (o.status = 'concluida'), o.vencimento, o.id
              LIMIT :limite OFFSET :offset",
            $parametros + ['hoje_situacao' => hoje(), 'limite' => $limite, 'offset' => $offset]
        )->fetchAll();
    }

    public function contar(array $filtros): int
    {
        [$onde, $parametros] = $this->condicoes($filtros);
        return (int) $this->executar(
            "SELECT COUNT(*) FROM obrigacoes o JOIN clientes c ON c.id = o.cliente_id WHERE {$onde}",
            $parametros
        )->fetchColumn();
    }

    public function buscar(int $id): ?array
    {
        $situacao = self::SITUACAO_SQL;
        $obrigacao = $this->executar(
            "SELECT o.*, c.nome AS cliente_nome, {$situacao} AS situacao
               FROM obrigacoes o JOIN clientes c ON c.id = o.cliente_id
              WHERE o.id = :id",
            ['id' => $id, 'hoje_situacao' => hoje()]
        )->fetch();
        return $obrigacao ?: null;
    }

    /** Todas as obrigações de um cliente (página do cliente). */
    public function doCliente(int $clienteId): array
    {
        $situacao = self::SITUACAO_SQL;
        return $this->executar(
            "SELECT o.*, {$situacao} AS situacao FROM obrigacoes o
              WHERE o.cliente_id = :cliente
              ORDER BY (o.status = 'concluida'), o.vencimento, o.id",
            ['cliente' => $clienteId, 'hoje_situacao' => hoje()]
        )->fetchAll();
    }

    /** Números do painel, calculados na hora a partir do banco. */
    public function resumo(): array
    {
        $hoje = hoje();
        $linha = $this->executar(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'pendente' AND vencimento >= :hoje1), 0) AS pendentes,
                    COALESCE(SUM(status = 'pendente' AND vencimento < :hoje2), 0) AS vencidas,
                    COALESCE(SUM(status = 'concluida'), 0) AS concluidas,
                    COALESCE(SUM(status = 'pendente' AND vencimento BETWEEN :hoje3 AND :em7dias), 0) AS proximos_7_dias,
                    COALESCE(SUM(CASE WHEN status = 'pendente' AND vencimento < :hoje4 THEN valor END), 0) AS valor_vencido
               FROM obrigacoes",
            [
                'hoje1' => $hoje, 'hoje2' => $hoje, 'hoje3' => $hoje, 'hoje4' => $hoje,
                'em7dias' => date('Y-m-d', strtotime('+7 days')),
            ]
        )->fetch();

        return array_map('floatval', $linha);
    }

    /** Pendentes vencidas ou que vencem nos próximos 7 dias: o que o escritório precisa olhar primeiro. */
    public function precisamAtencao(int $limite = 8): array
    {
        $situacao = self::SITUACAO_SQL;
        return $this->executar(
            "SELECT o.*, c.nome AS cliente_nome, c.nome_fantasia AS cliente_fantasia, {$situacao} AS situacao
               FROM obrigacoes o JOIN clientes c ON c.id = o.cliente_id
              WHERE o.status = 'pendente' AND o.vencimento <= :em7dias
              ORDER BY o.vencimento, o.id
              LIMIT :limite",
            ['hoje_situacao' => hoje(), 'em7dias' => date('Y-m-d', strtotime('+7 days')), 'limite' => $limite]
        )->fetchAll();
    }

    public function criar(array $dados): int
    {
        $this->executar(
            "INSERT INTO obrigacoes (cliente_id, nome, descricao, valor, vencimento, status, concluida_em)
             VALUES (:cliente_id, :nome, :descricao, :valor, :vencimento, :status,
                     CASE WHEN :status2 = 'concluida' THEN NOW() ELSE NULL END)",
            $dados + ['status2' => $dados['status']]
        );
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        // A data de conclusão é preenchida ao concluir e apagada se a obrigação voltar a pendente.
        $this->executar(
            "UPDATE obrigacoes
                SET cliente_id = :cliente_id, nome = :nome, descricao = :descricao, valor = :valor,
                    vencimento = :vencimento, status = :status,
                    concluida_em = CASE WHEN :status2 = 'concluida' THEN COALESCE(concluida_em, NOW()) ELSE NULL END
              WHERE id = :id",
            $dados + ['status2' => $dados['status'], 'id' => $id]
        );
    }

    public function concluir(int $id): void
    {
        $this->executar(
            "UPDATE obrigacoes SET status = 'concluida', concluida_em = COALESCE(concluida_em, NOW()) WHERE id = :id",
            ['id' => $id]
        );
    }

    public function excluir(int $id): void
    {
        $this->executar('DELETE FROM obrigacoes WHERE id = :id', ['id' => $id]);
    }

    public static function validar(array $entrada): array
    {
        $v = new Validador();

        $clienteId = ctype_digit($entrada['cliente_id']) ? (int) $entrada['cliente_id'] : 0;
        if ($clienteId < 1) {
            $v->erro('cliente_id', 'Selecione o cliente.');
        }

        $v->obrigatorio('nome', $entrada['nome'], 'Informe a obrigação (ex.: DAS - Simples Nacional).');
        $v->tamanhoMaximo('nome', $entrada['nome'], 100);
        $v->tamanhoMaximo('descricao', $entrada['descricao'], 2000);

        $valor = $entrada['valor'] === '' ? '0.00' : Validador::valorMonetario($entrada['valor']);
        if ($valor === null) {
            $v->erro('valor', 'Informe o valor em reais, como 1.250,90 (ou deixe em branco se não houver).');
        } elseif ((float) $valor > 9999999999.99) {
            $v->erro('valor', 'Valor alto demais.');
        }

        $vencimento = $v->data('vencimento', $entrada['vencimento'], 'Informe uma data de vencimento válida.');
        $v->opcao('status', $entrada['status'], ['pendente', 'concluida'], 'Escolha Pendente ou Concluída.');

        return [[
            'cliente_id' => $clienteId,
            'nome' => $entrada['nome'],
            'descricao' => $entrada['descricao'] ?: null,
            'valor' => $valor ?? '0.00',
            'vencimento' => $vencimento,
            'status' => $entrada['status'],
        ], $v->erros()];
    }

    private function condicoes(array $f): array
    {
        $onde = ['1 = 1'];
        $parametros = [];

        if ($f['busca'] !== '') {
            $onde[] = "(o.nome LIKE :busca1 ESCAPE '!' OR c.nome LIKE :busca2 ESCAPE '!' OR c.nome_fantasia LIKE :busca3 ESCAPE '!')";
            $parametros += ['busca1' => self::termoLike($f['busca']), 'busca2' => self::termoLike($f['busca']), 'busca3' => self::termoLike($f['busca'])];
        }
        if ($f['cliente_id'] !== null) {
            $onde[] = 'o.cliente_id = :cliente';
            $parametros['cliente'] = $f['cliente_id'];
        }
        switch ($f['situacao']) {
            case 'pendente':
                $onde[] = "o.status = 'pendente' AND o.vencimento >= :hoje_filtro";
                $parametros['hoje_filtro'] = hoje();
                break;
            case 'vencida':
                $onde[] = "o.status = 'pendente' AND o.vencimento < :hoje_filtro";
                $parametros['hoje_filtro'] = hoje();
                break;
            case 'concluida':
                $onde[] = "o.status = 'concluida'";
                break;
        }
        if ($f['de'] !== null) {
            $onde[] = 'o.vencimento >= :de';
            $parametros['de'] = $f['de'];
        }
        if ($f['ate'] !== null) {
            $onde[] = 'o.vencimento <= :ate';
            $parametros['ate'] = $f['ate'];
        }

        return [implode(' AND ', $onde), $parametros];
    }
}
