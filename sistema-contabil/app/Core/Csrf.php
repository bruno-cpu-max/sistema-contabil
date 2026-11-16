<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Proteção contra CSRF: cada sessão tem um token aleatório, que vai em todo formulário.
 * Um site malicioso não consegue ler esse token, então não consegue forjar um envio.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function valido(mixed $token): bool
    {
        return is_string($token) && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }
}
