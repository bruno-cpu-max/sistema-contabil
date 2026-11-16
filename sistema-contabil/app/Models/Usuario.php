<?php

declare(strict_types=1);

namespace App\Models;

final class Usuario extends Model
{
    /** Busca pelo nome de usuário OU pelo e-mail (a tela de login aceita os dois). */
    public function buscarPorLogin(string $login): ?array
    {
        $usuario = $this->executar(
            'SELECT id, nome, usuario, email, senha_hash FROM usuarios WHERE usuario = :usuario OR email = :email LIMIT 1',
            ['usuario' => $login, 'email' => $login]
        )->fetch();
        return $usuario ?: null;
    }

    public function buscar(int $id): ?array
    {
        $usuario = $this->executar('SELECT id, nome, usuario, email, senha_hash FROM usuarios WHERE id = :id', ['id' => $id])->fetch();
        return $usuario ?: null;
    }

    public function atualizarSenha(int $id, string $senha): void
    {
        $this->executar('UPDATE usuarios SET senha_hash = :hash WHERE id = :id', [
            'hash' => password_hash($senha, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
    }
}
