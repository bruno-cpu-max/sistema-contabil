<?php

declare(strict_types=1);

namespace App\Config;

use PDO;

/**
 * Conexão única com o banco (PDO), criada na primeira vez que alguém precisa dela.
 */
final class Database
{
    private static ?PDO $conexao = null;

    public static function conexao(): PDO
    {
        if (self::$conexao === null) {
            $db = config('db');
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['porta'], $db['nome']);

            self::$conexao = new PDO($dsn, $db['usuario'], $db['senha'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Prepared statements reais (no servidor): os valores nunca são misturados ao SQL.
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$conexao;
    }
}
