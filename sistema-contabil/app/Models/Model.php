<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOStatement;

/**
 * Base dos models: acesso ao banco SEMPRE por prepared statements (os valores vão separados
 * do SQL, então um texto como "' OR 1=1 --" é tratado como texto, nunca como comando).
 */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::conexao();
    }

    /** Prepara e executa uma consulta com parâmetros nomeados. Inteiros vão como inteiros (LIMIT/OFFSET). */
    protected function executar(string $sql, array $parametros = []): PDOStatement
    {
        $consulta = $this->db->prepare($sql);
        foreach ($parametros as $nome => $valor) {
            $tipo = match (true) {
                is_int($valor) => PDO::PARAM_INT,
                $valor === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $consulta->bindValue(':' . ltrim((string) $nome, ':'), $valor, $tipo);
        }
        $consulta->execute();
        return $consulta;
    }

    /**
     * Texto de busca para LIKE, com %, _ e ! escapados: quem digita "50%" procura por "50%",
     * e não por "50 seguido de qualquer coisa". As consultas usam ESCAPE '!'.
     */
    protected static function termoLike(string $texto): string
    {
        return '%' . strtr($texto, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    }
}
