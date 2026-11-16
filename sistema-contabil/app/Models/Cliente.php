<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Validador;

final class Cliente extends Model
{
    /** Clientes da página atual, com a contagem de pendências de cada um. */
    public function listar(string $busca, int $limite, int $offset): array
    {
        [$onde, $parametros] = $this->condicaoBusca($busca);
        $hoje = hoje();

        return $this->executar(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM obrigacoes o
                      WHERE o.cliente_id = c.id AND o.status = 'pendente' AND o.vencimento < :hoje1) AS obrigacoes_vencidas,
                    (SELECT COUNT(*) FROM obrigacoes o
                      WHERE o.cliente_id = c.id AND o.status = 'pendente' AND o.vencimento >= :hoje2) AS obrigacoes_pendentes,
                    (SELECT COUNT(*) FROM documentos d
                      WHERE d.cliente_id = c.id AND d.situacao = 'aguardando') AS documentos_aguardando
               FROM clientes c
              WHERE {$onde}
              ORDER BY c.nome
              LIMIT :limite OFFSET :offset",
            $parametros + ['hoje1' => $hoje, 'hoje2' => $hoje, 'limite' => $limite, 'offset' => $offset]
        )->fetchAll();
    }

    public function contar(string $busca = ''): int
    {
        [$onde, $parametros] = $this->condicaoBusca($busca);
        return (int) $this->executar("SELECT COUNT(*) FROM clientes c WHERE {$onde}", $parametros)->fetchColumn();
    }

    public function buscar(int $id): ?array
    {
        $cliente = $this->executar('SELECT * FROM clientes WHERE id = :id', ['id' => $id])->fetch();
        return $cliente ?: null;
    }

    /** Lista simples para os campos de seleção de cliente. */
    public function opcoes(): array
    {
        return $this->executar('SELECT id, nome, nome_fantasia FROM clientes ORDER BY nome')->fetchAll();
    }

    /** O CPF/CNPJ já pertence a outro cliente? */
    public function documentoEmUso(string $documento, ?int $exceto = null): bool
    {
        return (bool) $this->executar(
            'SELECT 1 FROM clientes WHERE documento = :documento AND id <> :exceto',
            ['documento' => $documento, 'exceto' => $exceto ?? 0]
        )->fetchColumn();
    }

    public function criar(array $dados): int
    {
        $this->executar(
            'INSERT INTO clientes (nome, nome_fantasia, documento, email, telefone, endereco, cidade, observacoes)
             VALUES (:nome, :nome_fantasia, :documento, :email, :telefone, :endereco, :cidade, :observacoes)',
            $dados
        );
        return (int) $this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        $this->executar(
            'UPDATE clientes SET nome = :nome, nome_fantasia = :nome_fantasia, documento = :documento, email = :email,
                    telefone = :telefone, endereco = :endereco, cidade = :cidade, observacoes = :observacoes
              WHERE id = :id',
            $dados + ['id' => $id]
        );
    }

    /** Exclui o cliente; as obrigações e documentos dele saem junto (ON DELETE CASCADE no banco). */
    public function excluir(int $id): void
    {
        $this->executar('DELETE FROM clientes WHERE id = :id', ['id' => $id]);
    }

    /**
     * Valida o formulário. Devolve [dados prontos para o banco, erros por campo].
     */
    public static function validar(array $entrada): array
    {
        $v = new Validador();

        $nome = $entrada['nome'];
        $v->obrigatorio('nome', $nome, 'Informe o nome ou a razão social.');
        $v->tamanhoMaximo('nome', $nome, 150);
        $v->tamanhoMaximo('nome_fantasia', $entrada['nome_fantasia'], 150);

        $documento = Validador::somenteNumeros($entrada['documento']);
        if ($entrada['documento'] === '') {
            $v->erro('documento', 'Informe o CPF ou o CNPJ.');
        } elseif (strlen($documento) === 11) {
            if (!Validador::cpfValido($documento)) {
                $v->erro('documento', 'CPF inválido. Confira os números digitados.');
            }
        } elseif (strlen($documento) === 14) {
            if (!Validador::cnpjValido($documento)) {
                $v->erro('documento', 'CNPJ inválido. Confira os números digitados.');
            }
        } else {
            $v->erro('documento', 'Informe um CPF (11 dígitos) ou um CNPJ (14 dígitos).');
        }

        $v->email('email', $entrada['email']);
        $v->tamanhoMaximo('email', $entrada['email'], 150);

        $telefone = Validador::somenteNumeros($entrada['telefone']);
        if ($entrada['telefone'] !== '' && !in_array(strlen($telefone), [10, 11], true)) {
            $v->erro('telefone', 'Informe o telefone com DDD, como (31) 99999-8888.');
        }

        $v->tamanhoMaximo('endereco', $entrada['endereco'], 200);
        $v->tamanhoMaximo('cidade', $entrada['cidade'], 100);
        $v->tamanhoMaximo('observacoes', $entrada['observacoes'], 2000);

        $dados = [
            'nome' => $nome,
            'nome_fantasia' => $entrada['nome_fantasia'] ?: null,
            'documento' => $documento,
            'email' => $entrada['email'] !== '' ? mb_strtolower($entrada['email']) : null,
            'telefone' => $telefone ?: null,
            'endereco' => $entrada['endereco'] ?: null,
            'cidade' => $entrada['cidade'] ?: null,
            'observacoes' => $entrada['observacoes'] ?: null,
        ];

        return [$dados, $v->erros()];
    }

    /** Busca por nome, nome fantasia ou CPF/CNPJ (com ou sem pontuação). */
    private function condicaoBusca(string $busca): array
    {
        if ($busca === '') {
            return ['1 = 1', []];
        }
        // Busca que é só número e pontuação ("11.222.333") compara com o CPF/CNPJ sem a pontuação.
        $numeros = preg_match('/^[\d.\-\/\s]+$/', $busca) ? Validador::somenteNumeros($busca) : '';
        return [
            "(c.nome LIKE :busca1 ESCAPE '!' OR c.nome_fantasia LIKE :busca2 ESCAPE '!' OR c.documento LIKE :busca3 ESCAPE '!')",
            [
                'busca1' => self::termoLike($busca),
                'busca2' => self::termoLike($busca),
                // Sem números na busca, o CPF/CNPJ é comparado com o próprio texto (e não casa com tudo).
                'busca3' => self::termoLike($numeros !== '' ? $numeros : $busca),
            ],
        ];
    }
}
