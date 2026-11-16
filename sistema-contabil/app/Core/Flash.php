<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Mensagens de uma tela para a próxima ("Cliente cadastrado com sucesso"), guardadas na sessão
 * e apagadas assim que exibidas.
 */
final class Flash
{
    public static function sucesso(string $mensagem): void
    {
        $_SESSION['_flash'][] = ['tipo' => 'sucesso', 'mensagem' => $mensagem];
    }

    public static function erro(string $mensagem): void
    {
        $_SESSION['_flash'][] = ['tipo' => 'erro', 'mensagem' => $mensagem];
    }

    public static function obter(): array
    {
        $mensagens = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $mensagens;
    }
}
