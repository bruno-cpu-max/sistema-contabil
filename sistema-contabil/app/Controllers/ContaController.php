<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Usuario;

/**
 * Minha conta: troca da senha do usuário logado (ex.: trocar a senha de teste).
 */
final class ContaController extends Controller
{
    public function index(): void
    {
        $this->view('conta/index', ['titulo' => 'Minha conta', 'usuario' => (new Usuario())->buscar(Auth::id())]);
    }

    public function alterarSenha(): void
    {
        $usuarios = new Usuario();
        $usuario = $usuarios->buscar(Auth::id()) ?? $this->naoEncontrado();

        $atual = is_string($_POST['senha_atual'] ?? null) ? $_POST['senha_atual'] : '';
        $nova = is_string($_POST['nova_senha'] ?? null) ? $_POST['nova_senha'] : '';
        $confirmacao = is_string($_POST['confirmacao'] ?? null) ? $_POST['confirmacao'] : '';

        $erros = [];
        if (!password_verify($atual, $usuario['senha_hash'])) {
            $erros['senha_atual'] = 'Senha atual incorreta.';
        }
        if (mb_strlen($nova) < 8) {
            $erros['nova_senha'] = 'A nova senha precisa ter pelo menos 8 caracteres.';
        } elseif (strlen($nova) > 72) {
            $erros['nova_senha'] = 'A nova senha pode ter no máximo 72 caracteres.';
        }
        if ($confirmacao !== $nova) {
            $erros['confirmacao'] = 'A confirmação não é igual à nova senha.';
        }

        if ($erros !== []) {
            http_response_code(422);
            $this->view('conta/index', ['titulo' => 'Minha conta', 'usuario' => $usuario, 'erros' => $erros]);
            return;
        }

        $usuarios->atualizarSenha((int) $usuario['id'], $nova);
        Flash::sucesso('Senha alterada com sucesso.');
        $this->redirecionar('conta');
    }
}
