<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Usuario;

final class AuthController extends Controller
{
    public function formulario(): void
    {
        if (Auth::logado()) {
            $this->redirecionar('dashboard');
        }
        $this->view('auth/login', ['titulo' => 'Entrar', 'login' => ''], 'login');
    }

    public function entrar(): void
    {
        $login = $this->entrada('login');
        $senha = $_POST['senha'] ?? '';
        $senha = is_string($senha) ? $senha : '';

        $erros = [];
        if ($login === '') {
            $erros['login'] = 'Informe o usuário ou o e-mail.';
        }
        if ($senha === '') {
            $erros['senha'] = 'Informe a senha.';
        }

        if ($erros === []) {
            $usuarios = new Usuario();
            $usuario = $usuarios->buscarPorLogin($login);

            // Quando o usuário não existe, a senha é conferida contra um hash qualquer: a resposta demora
            // o mesmo tempo e não revela quais usuários existem.
            $hash = $usuario['senha_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
            if (password_verify($senha, $hash) && $usuario !== null) {
                if (password_needs_rehash($usuario['senha_hash'], PASSWORD_DEFAULT)) {
                    $usuarios->atualizarSenha((int) $usuario['id'], $senha);
                }
                Auth::entrar($usuario);
                $this->redirecionar('dashboard');
            }

            // Mesma mensagem para usuário inexistente e senha errada.
            $erros['geral'] = 'Usuário ou senha incorretos.';
        }

        http_response_code(422);
        $this->view('auth/login', ['titulo' => 'Entrar', 'login' => $login, 'erros' => $erros], 'login');
    }

    public function sair(): void
    {
        Auth::sair();
        session_start();
        Flash::sucesso('Você saiu do sistema.');
        $this->redirecionar('login');
    }
}
