<?php

/**
 * Teste funcional e de segurança do SisContábil.
 *
 * Percorre o sistema pelo HTTP, como um usuário faria (login, formulários, filtros, envio de
 * arquivos), e confere cada resultado. Cria os próprios registros e os apaga no fim: os dados
 * de exemplo não são alterados.
 *
 * Uso (com o sistema no ar e o usuário admin/admin123):
 *     php tests/teste_sistema.php http://localhost/sistema-contabil
 */

declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost/sistema-contabil', '/');
$entrada = $base . '/public/index.php';
$pastaTemporaria = sys_get_temp_dir() . '/teste-siscontabil-' . bin2hex(random_bytes(4));
mkdir($pastaTemporaria);

// ------------------------------------------------------------------ apoio

final class Navegador
{
    private string $cookies;

    public function __construct(private string $entrada, string $pasta)
    {
        $this->cookies = $pasta . '/cookies-' . bin2hex(random_bytes(4)) . '.txt';
    }

    /** @return array{status: int, cabecalhos: string, corpo: string, local: string} */
    public function get(string $rota, array $parametros = []): array
    {
        return $this->requisitar($this->endereco($rota, $parametros));
    }

    public function post(string $rota, array $campos, bool $comToken = true): array
    {
        if ($comToken) {
            $campos['_csrf'] = $this->token();
        }
        return $this->requisitar($this->endereco($rota), $campos);
    }

    /** Endereço completo (para links absolutos, como o de download). */
    public function endereco(string $rota, array $parametros = []): string
    {
        return $this->entrada . '?' . http_build_query(['r' => $rota] + $parametros);
    }

    public function requisitarUrl(string $url): array
    {
        return $this->requisitar($url);
    }

    public function token(): string
    {
        $pagina = $this->get('login')['corpo'];
        if (!preg_match('/name="_csrf" value="([a-f0-9]{64})"/', $pagina, $m)) {
            // Logado, a página de login redireciona: pega o token de uma página interna.
            $pagina = $this->get('conta')['corpo'];
            preg_match('/name="_csrf" value="([a-f0-9]{64})"/', $pagina, $m);
        }
        return $m[1] ?? '';
    }

    public function idSessao(): string
    {
        $conteudo = is_file($this->cookies) ? file_get_contents($this->cookies) : '';
        return preg_match('/SISCONTABIL\s+(\S+)/', (string) $conteudo, $m) ? $m[1] : '';
    }

    private function requisitar(string $url, ?array $campos = null): array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEFILE => $this->cookies,
            CURLOPT_COOKIEJAR => $this->cookies,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($campos !== null) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $campos);
        }
        $resposta = (string) curl_exec($curl);
        $tamanhoCabecalho = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        $cabecalhos = substr($resposta, 0, $tamanhoCabecalho);
        preg_match('/^Location:\s*(\S+)/mi', $cabecalhos, $m);
        return ['status' => $status, 'cabecalhos' => $cabecalhos, 'corpo' => substr($resposta, $tamanhoCabecalho), 'local' => $m[1] ?? ''];
    }
}

$falhas = 0;
$total = 0;
function confere(string $descricao, bool $condicao, string $detalhe = ''): void
{
    global $falhas, $total;
    $total++;
    if ($condicao) {
        echo "  ok    {$descricao}\n";
    } else {
        $falhas++;
        echo "  FALHA {$descricao}" . ($detalhe !== '' ? " ({$detalhe})" : '') . "\n";
    }
}

function secao(string $titulo): void
{
    echo "\n== {$titulo}\n";
}

/** Valor de um indicador do painel. */
function indicador(Navegador $n, string $rotulo): int
{
    $html = $n->get('dashboard')['corpo'];
    $padrao = '/indicador__rotulo">' . preg_quote($rotulo, '/') . '<\/span>\s*<strong class="indicador__valor">([\d.]+)</';
    return preg_match($padrao, $html, $m) ? (int) str_replace('.', '', $m[1]) : -1;
}

function cnpjValido(): string
{
    $b = (string) random_int(10000000, 99999999) . '0001';
    foreach ([12, 13] as $p) {
        $pesos = $p === 12 ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2] : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $s = 0;
        for ($i = 0; $i < $p; $i++) {
            $s += (int) $b[$i] * $pesos[$i];
        }
        $r = $s % 11;
        $b .= (string) ($r < 2 ? 0 : 11 - $r);
    }
    return $b;
}

function idDoRedirecionamento(array $resposta): int
{
    return preg_match('/[?&]id=(\d+)/', $resposta['local'], $m) ? (int) $m[1] : 0;
}

/** Busca na listagem de obrigações a linha de uma obrigação (pelo nome) e devolve o id dela. */
function idObrigacao(Navegador $n, string $nome): int
{
    $html = $n->get('obrigacoes', ['busca' => $nome])['corpo'];
    return preg_match('/obrigacoes\/form&amp;id=(\d+)/', $html, $m) ? (int) $m[1] : 0;
}

$hoje = date('Y-m-d');
$ontem = date('Y-m-d', strtotime('-1 day'));
$emDezDias = date('Y-m-d', strtotime('+10 days'));
$sufixo = bin2hex(random_bytes(3));

// ------------------------------------------------------------------ 1. acesso

secao('Login e sessão');
$visitante = new Navegador($entrada, $pastaTemporaria);

$r = $visitante->get('clientes');
confere('página interna sem login leva ao login', $r['status'] === 302 && str_contains($r['local'], 'r=login'), "status {$r['status']}");
$r = $visitante->get('documentos/arquivo', ['id' => 1]);
confere('arquivo de documento sem login não é entregue', $r['status'] === 302 && !str_contains($r['corpo'], '%PDF'));

$r = $visitante->post('login', ['login' => 'admin', 'senha' => 'admin123'], false);
confere('login sem token CSRF é recusado', $r['status'] === 403, "status {$r['status']}");

$r = $visitante->post('login', ['login' => '', 'senha' => '']);
confere('login vazio mostra erro em cada campo', $r['status'] === 422
    && str_contains($r['corpo'], 'Informe o usuário ou o e-mail.') && str_contains($r['corpo'], 'Informe a senha.'));

$r = $visitante->post('login', ['login' => 'admin', 'senha' => 'senha-errada']);
confere('senha errada não entra e mostra mensagem', $r['status'] === 422 && str_contains($r['corpo'], 'Usuário ou senha incorretos.'));
$r = $visitante->post('login', ['login' => 'ninguem@x.com', 'senha' => 'qualquer']);
confere('usuário inexistente recebe a mesma mensagem (não revela quem existe)', $r['status'] === 422 && str_contains($r['corpo'], 'Usuário ou senha incorretos.'));

$porEmail = new Navegador($entrada, $pastaTemporaria);
$r = $porEmail->post('login', ['login' => 'admin@siscontabil.com.br', 'senha' => 'admin123']);
confere('login pelo e-mail funciona', $r['status'] === 302 && str_contains($r['local'], 'r=dashboard'));

$admin = new Navegador($entrada, $pastaTemporaria);
$admin->get('login');
$sessaoAntes = $admin->idSessao();
$r = $admin->post('login', ['login' => 'admin', 'senha' => 'admin123']);
confere('login pelo nome de usuário funciona', $r['status'] === 302 && str_contains($r['local'], 'r=dashboard'));
confere('o ID da sessão muda no login (contra fixação de sessão)', $sessaoAntes !== '' && $admin->idSessao() !== $sessaoAntes);
preg_match('/^Set-Cookie: SISCONTABIL=.*$/mi', $r['cabecalhos'], $cookie);
confere('cookie de sessão HttpOnly e SameSite', isset($cookie[0]) && stripos($cookie[0], 'HttpOnly') !== false && stripos($cookie[0], 'SameSite=Lax') !== false);
confere('logado, o painel abre', $admin->get('dashboard')['status'] === 200);

$r = $porEmail->post('logout', []);
confere('sair encerra a sessão', $r['status'] === 302 && $porEmail->get('dashboard')['status'] === 302);

// ------------------------------------------------------------------ 2. segurança geral

secao('Proteções gerais');
$r = $admin->get('dashboard');
confere('cabeçalho Content-Security-Policy', (bool) preg_match('/^Content-Security-Policy: .*default-src \'self\'/mi', $r['cabecalhos']));
confere('cabeçalho X-Frame-Options (contra clickjacking)', (bool) preg_match('/^X-Frame-Options: DENY/mi', $r['cabecalhos']));
foreach (['database.sql', 'app/Config/config.php', 'storage/uploads/exemplo-extrato-bancario.pdf', 'storage/logs/erros.log'] as $caminho) {
    $status = $admin->requisitarUrl($base . '/' . $caminho)['status'];
    confere("arquivo interno bloqueado pelo navegador: {$caminho}", in_array($status, [403, 404], true), "status {$status}");
}
$r = $admin->post('clientes/excluir', ['id' => '1'], false);
confere('excluir sem token CSRF é recusado', $r['status'] === 403);
$r = $admin->get('clientes/ver', ['id' => '999999']);
confere('registro inexistente dá 404', $r['status'] === 404);
$r = $admin->get('rota-que-nao-existe');
confere('rota inexistente dá 404', $r['status'] === 404);

// ------------------------------------------------------------------ 3. clientes

secao('Clientes');
$totalClientesAntes = indicador($admin, 'Clientes cadastrados');
$cnpj = cnpjValido();
$cnpjFormatado = vsprintf('%s.%s.%s/%s-%s', [substr($cnpj, 0, 2), substr($cnpj, 2, 3), substr($cnpj, 5, 3), substr($cnpj, 8, 4), substr($cnpj, 12)]);
$nomeCliente = "Empresa Teste {$sufixo} Ltda";
$dadosCliente = [
    'nome' => $nomeCliente, 'nome_fantasia' => "Teste {$sufixo}", 'documento' => $cnpjFormatado,
    'email' => 'teste@empresa.com.br', 'telefone' => '(31) 98888-7777', 'endereco' => 'Rua A, 1',
    'cidade' => 'Belo Horizonte/MG', 'observacoes' => 'Criado pelo teste automatizado.',
];

$r = $admin->post('clientes/salvar', ['nome' => '', 'documento' => '123', 'email' => 'invalido', 'telefone' => '99'] + $dadosCliente);
confere('cliente inválido não é salvo e mostra erro por campo', $r['status'] === 422
    && str_contains($r['corpo'], 'Informe o nome ou a razão social.')
    && str_contains($r['corpo'], 'Informe um CPF (11 dígitos) ou um CNPJ (14 dígitos).')
    && str_contains($r['corpo'], 'Informe um e-mail válido')
    && str_contains($r['corpo'], 'Informe o telefone com DDD'));
$r = $admin->post('clientes/salvar', ['documento' => '11.222.333/0001-82'] + $dadosCliente);
confere('CNPJ com dígito verificador errado é recusado', $r['status'] === 422 && str_contains($r['corpo'], 'CNPJ inválido'));
$r = $admin->post('clientes/salvar', ['documento' => '529.982.247-25'] + $dadosCliente);
confere('CPF/CNPJ já cadastrado é recusado', $r['status'] === 422 && str_contains($r['corpo'], 'Já existe um cliente cadastrado com este CPF/CNPJ.'));

$r = $admin->post('clientes/salvar', $dadosCliente);
$idCliente = idDoRedirecionamento($r);
confere('cadastra cliente válido', $r['status'] === 302 && $idCliente > 0);
$ficha = $admin->get('clientes/ver', ['id' => $idCliente])['corpo'];
confere('ficha mostra os dados salvos, com CNPJ formatado', str_contains($ficha, $nomeCliente) && str_contains($ficha, $cnpjFormatado) && str_contains($ficha, '(31) 98888-7777'));
confere('indicador "Clientes cadastrados" aumentou', indicador($admin, 'Clientes cadastrados') === $totalClientesAntes + 1);

confere('pesquisa pelo nome encontra', str_contains($admin->get('clientes', ['busca' => "Teste {$sufixo}"])['corpo'], $nomeCliente));
confere('pesquisa pelo CNPJ com pontuação encontra', str_contains($admin->get('clientes', ['busca' => $cnpjFormatado])['corpo'], $nomeCliente));
confere('pesquisa pelo CNPJ só com números encontra', str_contains($admin->get('clientes', ['busca' => $cnpj])['corpo'], $nomeCliente));
$r = $admin->get('clientes', ['busca' => "' OR 1=1 -- "]);
confere('tentativa de SQL injection na pesquisa não quebra nem lista tudo', $r['status'] === 200 && str_contains($r['corpo'], 'Nenhum cliente encontrado'));
$r = $admin->get('clientes', ['busca' => '%']);
confere('"%" na pesquisa é tratado como texto (não lista tudo)', str_contains($r['corpo'], 'Nenhum cliente encontrado'));

$r = $admin->post('clientes/salvar', ['id' => (string) $idCliente, 'cidade' => 'Contagem/MG'] + $dadosCliente);
confere('edita cliente', $r['status'] === 302 && str_contains($admin->get('clientes/ver', ['id' => $idCliente])['corpo'], 'Contagem/MG'));

$xss = '<script>alert("xss")</script>';
$cnpjXss = cnpjValido();
$r = $admin->post('clientes/salvar', ['nome' => $xss, 'documento' => $cnpjXss, 'nome_fantasia' => '', 'observacoes' => '<img src=x onerror=alert(1)>'] + $dadosCliente);
$idXss = idDoRedirecionamento($r);
$paginaXss = $admin->get('clientes/ver', ['id' => $idXss])['corpo'];
$listaXss = $admin->get('clientes', ['busca' => 'script'])['corpo'];
confere('XSS: nome com <script> aparece como texto, sem executar',
    !str_contains($paginaXss, $xss) && str_contains($paginaXss, '&lt;script&gt;') && !str_contains($listaXss, $xss));
confere('XSS: <img onerror> nas observações aparece como texto', !str_contains($paginaXss, '<img src=x') && str_contains($paginaXss, '&lt;img src=x'));

// ------------------------------------------------------------------ 4. obrigações

secao('Obrigações fiscais');
$antes = ['pendentes' => indicador($admin, 'Pendentes'), 'vencidas' => indicador($admin, 'Vencidas'), 'concluidas' => indicador($admin, 'Concluídas'), 'total' => indicador($admin, 'Obrigações fiscais')];

$r = $admin->post('obrigacoes/salvar', ['cliente_id' => '', 'nome' => '', 'valor' => 'abc', 'vencimento' => '31/02/2026', 'status' => 'vencida', 'descricao' => '']);
confere('obrigação inválida mostra erro por campo', $r['status'] === 422
    && str_contains($r['corpo'], 'Selecione o cliente.') && str_contains($r['corpo'], 'Informe a obrigação')
    && str_contains($r['corpo'], 'Informe o valor em reais') && str_contains($r['corpo'], 'Informe uma data de vencimento válida.')
    && str_contains($r['corpo'], 'Escolha Pendente ou Concluída.'));
$r = $admin->post('obrigacoes/salvar', ['cliente_id' => '999999', 'nome' => 'X', 'valor' => '', 'vencimento' => $hoje, 'status' => 'pendente', 'descricao' => '']);
confere('obrigação para cliente inexistente é recusada', $r['status'] === 422 && str_contains($r['corpo'], 'Cliente não encontrado'));

$nomeVencida = "DAS teste {$sufixo}";
$r = $admin->post('obrigacoes/salvar', ['cliente_id' => (string) $idCliente, 'nome' => $nomeVencida, 'valor' => '1.234,56', 'vencimento' => $ontem, 'status' => 'pendente', 'descricao' => 'Teste']);
confere('cadastra obrigação (vencida ontem)', $r['status'] === 302);
$idVencida = idObrigacao($admin, $nomeVencida);
$lista = $admin->get('obrigacoes', ['busca' => $nomeVencida])['corpo'];
confere('pendente com vencimento passado aparece como Vencida, com valor formatado', str_contains($lista, '>Vencida<') && str_contains($lista, 'R$ 1.234,56'));
$nomePendente = "FGTS teste {$sufixo}";
$admin->post('obrigacoes/salvar', ['cliente_id' => (string) $idCliente, 'nome' => $nomePendente, 'valor' => '', 'vencimento' => $emDezDias, 'status' => 'pendente', 'descricao' => '']);
$idPendente = idObrigacao($admin, $nomePendente);
confere('obrigação futura aparece como Pendente; valor vazio vira R$ 0,00', $idPendente > 0
    && str_contains($admin->get('obrigacoes', ['busca' => $nomePendente])['corpo'], '>Pendente<')
    && str_contains($admin->get('obrigacoes', ['busca' => $nomePendente])['corpo'], 'R$ 0,00'));

confere('painel: total de obrigações aumentou 2', indicador($admin, 'Obrigações fiscais') === $antes['total'] + 2);
confere('painel: vencidas aumentou 1', indicador($admin, 'Vencidas') === $antes['vencidas'] + 1);
confere('painel: pendentes aumentou 1', indicador($admin, 'Pendentes') === $antes['pendentes'] + 1);

$soVencidas = $admin->get('obrigacoes', ['situacao' => 'vencida', 'cliente_id' => $idCliente])['corpo'];
confere('filtro por situação + cliente', str_contains($soVencidas, $nomeVencida) && !str_contains($soVencidas, $nomePendente));
$porPeriodo = $admin->get('obrigacoes', ['cliente_id' => $idCliente, 'de' => $hoje, 'ate' => $emDezDias])['corpo'];
confere('filtro por período de vencimento', str_contains($porPeriodo, $nomePendente) && !str_contains($porPeriodo, $nomeVencida));
confere('pesquisa pelo nome do cliente', str_contains($admin->get('obrigacoes', ['busca' => "Teste {$sufixo}"])['corpo'], $nomeVencida));

$pagina1 = $admin->get('obrigacoes')['corpo'];
$pagina2 = $admin->get('obrigacoes', ['pagina' => 2])['corpo'];
preg_match('/Mostrando (\d+)–(\d+) de (\d+)/', $pagina1, $m1);
preg_match('/Mostrando (\d+)–(\d+) de (\d+)/', $pagina2, $m2);
confere('paginação: 10 por página e a página 2 continua de onde a 1 parou',
    isset($m1[2], $m2[1]) && $m1[1] === '1' && $m1[2] === '10' && $m2[1] === '11', ($m1[0] ?? '?') . ' / ' . ($m2[0] ?? '?'));
confere('paginação mantém os filtros nos links', str_contains($admin->get('obrigacoes', ['situacao' => 'pendente'])['corpo'], 'situacao=pendente&amp;pagina=2')
    || indicador($admin, 'Pendentes') <= 10);
preg_match('/Mostrando \d+–\d+ de \d+/', $admin->get('obrigacoes', ['pagina' => 999])['corpo'], $m3);
confere('página além da última mostra a última', isset($m3[0]));

$r = $admin->post('obrigacoes/concluir', ['id' => (string) $idVencida]);
confere('marcar como concluída', $r['status'] === 302 && str_contains($admin->get('obrigacoes', ['busca' => $nomeVencida])['corpo'], '>Concluída<'));
confere('painel atualiza na hora: vencidas voltou e concluídas aumentou',
    indicador($admin, 'Vencidas') === $antes['vencidas'] && indicador($admin, 'Concluídas') === $antes['concluidas'] + 1);

$r = $admin->post('obrigacoes/salvar', ['id' => (string) $idPendente, 'cliente_id' => (string) $idCliente, 'nome' => $nomePendente . ' editada', 'valor' => '99,90', 'vencimento' => $emDezDias, 'status' => 'pendente', 'descricao' => 'Editada']);
confere('edita obrigação', $r['status'] === 302 && str_contains($admin->get('obrigacoes', ['busca' => $nomePendente . ' editada'])['corpo'], 'R$ 99,90'));
$r = $admin->post('obrigacoes/excluir', ['id' => (string) $idPendente]);
confere('exclui obrigação', $r['status'] === 302 && idObrigacao($admin, $nomePendente) === 0);

// ------------------------------------------------------------------ 5. documentos

secao('Documentos e envio de arquivos');
$pdf = $pastaTemporaria . '/extrato.pdf';
file_put_contents($pdf, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");
$exeDisfarcado = $pastaTemporaria . '/nota.pdf';
file_put_contents($exeDisfarcado, "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xFF\xFF\x00\x00" . str_repeat("\x00", 200) . 'This program cannot be run in DOS mode.');
$php = $pastaTemporaria . '/ataque.php';
file_put_contents($php, '<?php echo "invadido"; ?>');
$grande = $pastaTemporaria . '/grande.pdf';
file_put_contents($grande, '%PDF-1.4' . str_repeat('0', 6 * 1024 * 1024));

$tituloDoc = "Extrato teste {$sufixo}";
$camposDoc = ['cliente_id' => (string) $idCliente, 'titulo' => $tituloDoc, 'descricao' => 'Extrato do mês', 'data_limite' => $emDezDias, 'situacao' => 'aguardando'];

$r = $admin->post('documentos/salvar', ['cliente_id' => '', 'titulo' => '', 'data_limite' => '', 'situacao' => 'x', 'descricao' => '']);
confere('solicitação inválida mostra erro por campo', $r['status'] === 422
    && str_contains($r['corpo'], 'Selecione o cliente que deve enviar o documento.') && str_contains($r['corpo'], 'Informe qual documento')
    && str_contains($r['corpo'], 'Informe uma data limite válida') && str_contains($r['corpo'], 'Escolha uma situação da lista.'));

$aguardandoAntes = substr_count($admin->get('documentos', ['situacao' => 'aguardando', 'cliente_id' => $idCliente])['corpo'], 'Aguardando envio</span>');
$r = $admin->post('documentos/salvar', $camposDoc);
confere('registra solicitação de documento', $r['status'] === 302);
$listaDocs = $admin->get('documentos', ['cliente_id' => $idCliente])['corpo'];
preg_match('/documentos\/form&amp;id=(\d+)/', $listaDocs, $m);
$idDoc = (int) ($m[1] ?? 0);
confere('aparece como "Aguardando envio" e "Não enviado"', $idDoc > 0 && str_contains($listaDocs, 'Aguardando envio') && str_contains($listaDocs, 'Não enviado'));

$r = $admin->post('documentos/salvar', ['id' => (string) $idDoc, 'arquivo' => new CURLFile($exeDisfarcado, 'application/pdf', 'nota.pdf')] + $camposDoc);
confere('recusa executável renomeado para .pdf (confere o conteúdo)', $r['status'] === 422 && str_contains($r['corpo'], 'não corresponde à extensão'));
$r = $admin->post('documentos/salvar', ['id' => (string) $idDoc, 'arquivo' => new CURLFile($php, 'application/pdf', 'ataque.php')] + $camposDoc);
confere('recusa arquivo .php', $r['status'] === 422 && str_contains($r['corpo'], 'Tipo de arquivo não permitido'));
$r = $admin->post('documentos/salvar', ['id' => (string) $idDoc, 'arquivo' => new CURLFile($grande, 'application/pdf', 'grande.pdf')] + $camposDoc);
confere('recusa arquivo maior que 5 MB', $r['status'] === 422 && str_contains($r['corpo'], 'grande demais'));

$r = $admin->post('documentos/salvar', ['id' => (string) $idDoc, 'arquivo' => new CURLFile($pdf, 'application/pdf', 'extrato março.pdf')] + $camposDoc);
confere('aceita PDF válido', $r['status'] === 302);
$editar = $admin->get('documentos/form', ['id' => $idDoc])['corpo'];
confere('com arquivo, o pedido passa a "Recebido" sozinho', str_contains($editar, '<option value="recebido" selected>'));
confere('nome original do arquivo é exibido', str_contains($editar, 'extrato março.pdf'));
$download = $admin->get('documentos/arquivo', ['id' => $idDoc]);
confere('o arquivo é entregue íntegro a quem está logado', $download['status'] === 200 && $download['corpo'] === file_get_contents($pdf));
confere('PDF abre no navegador com o tipo correto', (bool) preg_match('/^Content-Type: application\/pdf/mi', $download['cabecalhos'])
    && (bool) preg_match('/^Content-Disposition: inline/mi', $download['cabecalhos']));
confere('opção "baixar" força o download', (bool) preg_match('/^Content-Disposition: attachment/mi', $admin->get('documentos/arquivo', ['id' => $idDoc, 'baixar' => 1])['cabecalhos']));
confere('quem não está logado não baixa o arquivo', $visitante->get('documentos/arquivo', ['id' => $idDoc])['status'] === 302);

$r = $admin->post('documentos/situacao', ['id' => (string) $idDoc, 'situacao' => 'conferido']);
confere('contador altera a situação para "Conferido"', $r['status'] === 302
    && str_contains($admin->get('documentos', ['cliente_id' => $idCliente, 'situacao' => 'conferido'])['corpo'], $tituloDoc));
$r = $admin->post('documentos/situacao', ['id' => (string) $idDoc, 'situacao' => 'invalida']);
confere('situação inválida é recusada', str_contains($admin->get('documentos', ['cliente_id' => $idCliente, 'situacao' => 'conferido'])['corpo'], $tituloDoc));

$tituloAtrasado = "Notas teste {$sufixo}";
$admin->post('documentos/salvar', ['titulo' => $tituloAtrasado, 'data_limite' => $ontem] + $camposDoc);
confere('pedido com prazo vencido aparece como "Atrasado" e no filtro de atrasados',
    str_contains($admin->get('documentos', ['situacao' => 'atrasado', 'cliente_id' => $idCliente])['corpo'], $tituloAtrasado));
confere('documentos organizados por cliente (filtro e ficha)',
    str_contains($admin->get('clientes/ver', ['id' => $idCliente])['corpo'], $tituloAtrasado)
    && str_contains($admin->get('documentos')['corpo'], 'documentos&amp;cliente_id=' . $idCliente));

$r = $admin->post('documentos/excluir', ['id' => (string) $idDoc]);
confere('exclui solicitação e o arquivo deixa de existir', $r['status'] === 302 && $admin->get('documentos/arquivo', ['id' => $idDoc])['status'] === 404);

// ------------------------------------------------------------------ 6. minha conta

secao('Minha conta');
$r = $admin->post('conta/senha', ['senha_atual' => 'errada', 'nova_senha' => '123', 'confirmacao' => '456']);
confere('troca de senha inválida mostra erro por campo', $r['status'] === 422
    && str_contains($r['corpo'], 'Senha atual incorreta.') && str_contains($r['corpo'], 'pelo menos 8 caracteres') && str_contains($r['corpo'], 'A confirmação não é igual'));
$r = $admin->post('conta/senha', ['senha_atual' => 'admin123', 'nova_senha' => 'nova-senha-123', 'confirmacao' => 'nova-senha-123']);
$outro = new Navegador($entrada, $pastaTemporaria);
confere('troca a senha e a nova passa a valer', $r['status'] === 302
    && $outro->post('login', ['login' => 'admin', 'senha' => 'nova-senha-123'])['status'] === 302);
$admin->post('conta/senha', ['senha_atual' => 'nova-senha-123', 'nova_senha' => 'admin123', 'confirmacao' => 'admin123']);
confere('senha de teste restaurada (admin123)', (new Navegador($entrada, $pastaTemporaria))->post('login', ['login' => 'admin', 'senha' => 'admin123'])['status'] === 302);

// ------------------------------------------------------------------ 7. exclusão em cascata

secao('Exclusão de cliente (com obrigações e documentos)');
$r = $admin->post('clientes/excluir', ['id' => (string) $idCliente]);
confere('exclui o cliente', $r['status'] === 302 && $admin->get('clientes/ver', ['id' => $idCliente])['status'] === 404);
confere('as obrigações dele saem junto', idObrigacao($admin, $nomeVencida) === 0);
confere('os documentos dele saem junto', str_contains($admin->get('documentos', ['busca' => $tituloAtrasado])['corpo'], 'Nenhum documento encontrado'));
$admin->post('clientes/excluir', ['id' => (string) $idXss]);
confere('painel volta aos números de antes do teste',
    indicador($admin, 'Clientes cadastrados') === $totalClientesAntes && indicador($admin, 'Obrigações fiscais') === $antes['total']
    && indicador($admin, 'Concluídas') === $antes['concluidas']);

// ------------------------------------------------------------------ resultado

array_map('unlink', glob($pastaTemporaria . '/*') ?: []);
rmdir($pastaTemporaria);

echo "\n" . ($falhas === 0 ? "Tudo certo: {$total} verificações passaram." : "{$falhas} de {$total} verificações FALHARAM.") . "\n";
exit($falhas === 0 ? 0 : 1);
