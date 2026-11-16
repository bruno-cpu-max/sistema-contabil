<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cliente;
use App\Models\Documento;
use App\Models\Obrigacao;

/**
 * Painel inicial: todos os números vêm do banco a cada acesso, então refletem na hora
 * qualquer cadastro ou alteração.
 */
final class DashboardController extends Controller
{
    public function index(): void
    {
        $obrigacoes = new Obrigacao();
        $documentos = new Documento();

        $this->view('dashboard/index', [
            'titulo' => 'Painel',
            'totalClientes' => (new Cliente())->contar(),
            'resumo' => $obrigacoes->resumo(),
            'resumoDocumentos' => $documentos->resumo(),
            'atencao' => $obrigacoes->precisamAtencao(8),
            'documentosAguardando' => $documentos->aguardandoEnvio(5),
        ]);
    }
}
