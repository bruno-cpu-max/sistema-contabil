<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Controle de quem está logado (dados guardados na sessão PHP).
 */
final class Auth
{
    public static function entrar(array $usuario): void
    {
        // Novo ID de sessão no login: impede que alguém reaproveite um ID conhecido (fixação de sessão).
        session_regenerate_id(true);
        $_SESSION['usuario'] = ['id' => (int) $usuario['id'], 'nome' => $usuario['nome']];
        $_SESSION['ultima_atividade'] = time();
        unset($_SESSION['_csrf']);
    }

    public static function sair(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Usuário logado e com sessão ainda válida; renova o prazo de inatividade a cada uso. */
    public static function logado(): bool
    {
        if (empty($_SESSION['usuario'])) {
            return false;
        }
        if (time() - ($_SESSION['ultima_atividade'] ?? 0) > config('app.sessao_inatividade')) {
            self::sair();
            return false;
        }
        $_SESSION['ultima_atividade'] = time();
        return true;
    }

    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function id(): int
    {
        return (int) ($_SESSION['usuario']['id'] ?? 0);
    }
}
