<?php

/**
 * Funções de apoio usadas em todo o sistema, principalmente nas views.
 */

declare(strict_types=1);

/** Lê uma configuração: config('db.nome'), config('app') etc. */
function config(string $chave): mixed
{
    static $config = null;
    $config ??= require RAIZ . '/app/Config/config.php';

    $valor = $config;
    foreach (explode('.', $chave) as $parte) {
        $valor = $valor[$parte];
    }
    return $valor;
}

/**
 * Escapa texto para exibir em HTML. TODO dado vindo do usuário ou do banco passa por aqui
 * antes de ir para a tela: é a proteção contra XSS.
 */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Endereço de uma rota: url('clientes/form', ['id' => 3]) => /…/index.php?r=clientes/form&id=3 */
function url(string $rota = 'dashboard', array $parametros = []): string
{
    $parametros = array_filter($parametros, static fn ($v) => $v !== null && $v !== '');
    // A rota é sempre um texto fixo do código (ex.: 'clientes/form'); os parâmetros são codificados.
    $consulta = http_build_query($parametros);
    return $_SERVER['SCRIPT_NAME'] . '?r=' . $rota . ($consulta !== '' ? '&' . $consulta : '');
}

/** Endereço de um arquivo da pasta public/assets, com a data de alteração para o navegador não usar cópia velha. */
function asset(string $caminho): string
{
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    $arquivo = RAIZ . '/public/assets/' . $caminho;
    $versao = is_file($arquivo) ? filemtime($arquivo) : 0;
    return $base . '/assets/' . $caminho . '?v=' . $versao;
}

/** Campo oculto com o token CSRF; todo formulário POST precisa dele. */
function csrf_campo(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(\App\Core\Csrf::token()) . '">';
}

/** Mensagem de erro de um campo do formulário (vazio se o campo está certo). */
function erro_campo(array $erros, string $campo): string
{
    if (!isset($erros[$campo])) {
        return '';
    }
    return '<p class="campo__erro" id="erro-' . e($campo) . '">' . e($erros[$campo]) . '</p>';
}

/** Atributos de acessibilidade de um campo com erro. */
function attr_erro(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? ' aria-invalid="true" aria-describedby="erro-' . e($campo) . '"' : '';
}

function selecionado(mixed $atual, mixed $opcao): string
{
    return (string) $atual === (string) $opcao ? ' selected' : '';
}

/** Rótulo colorido de situação. */
function selo(string $texto, string $tom): string
{
    return '<span class="selo selo--' . e($tom) . '">' . e($texto) . '</span>';
}

function dinheiro(mixed $valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

/** 2026-11-16 => 16/11/2026 */
function data_br(?string $data): string
{
    if ($data === null || $data === '') {
        return '—';
    }
    $dt = DateTime::createFromFormat('!Y-m-d', substr($data, 0, 10));
    return $dt ? $dt->format('d/m/Y') : '—';
}

function data_hora_br(?string $dataHora): string
{
    if ($dataHora === null || $dataHora === '') {
        return '—';
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dataHora);
    return $dt ? $dt->format('d/m/Y H:i') : '—';
}

/** CPF ou CNPJ com a máscara (o banco guarda só os números). */
function documento_formatado(?string $numeros): string
{
    $numeros = (string) $numeros;
    if (strlen($numeros) === 11) {
        return vsprintf('%s.%s.%s-%s', [substr($numeros, 0, 3), substr($numeros, 3, 3), substr($numeros, 6, 3), substr($numeros, 9)]);
    }
    if (strlen($numeros) === 14) {
        return vsprintf('%s.%s.%s/%s-%s', [substr($numeros, 0, 2), substr($numeros, 2, 3), substr($numeros, 5, 3), substr($numeros, 8, 4), substr($numeros, 12)]);
    }
    return $numeros;
}

function telefone_formatado(?string $numeros): string
{
    $numeros = (string) $numeros;
    return match (strlen($numeros)) {
        11 => sprintf('(%s) %s-%s', substr($numeros, 0, 2), substr($numeros, 2, 5), substr($numeros, 7)),
        10 => sprintf('(%s) %s-%s', substr($numeros, 0, 2), substr($numeros, 2, 4), substr($numeros, 6)),
        default => $numeros,
    };
}

function tamanho_arquivo(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
    }
    return max(1, (int) round($bytes / 1024)) . ' KB';
}

/** Data de hoje (Y-m-d) no fuso do sistema; usada para decidir o que está vencido. */
function hoje(): string
{
    return date('Y-m-d');
}

/**
 * Ícones do sistema (SVG embutido: funciona sem internet). Os desenhos são fixos no código,
 * nunca vêm do usuário, por isso podem ir para a tela sem escape.
 */
function icone(string $nome, string $classe = 'icone'): string
{
    static $desenhos = [
        'painel' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'clientes' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'obrigacoes' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/>',
        'documentos' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',
        'sair' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'conta' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
        'mais' => '<path d="M12 5v14M5 12h14"/>',
        'editar' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'excluir' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/>',
        'ver' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'baixar' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
        'anexo' => '<path d="m21.4 11.1-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/>',
        'busca' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'check-duplo' => '<path d="M18 6 7 17l-5-5"/><path d="m22 10-7.5 7.5L13 16"/>',
        'alerta' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        'relogio' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'calendario' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'lista' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>',
        'filtro' => '<path d="M22 3H2l8 9.5V19l4 2v-8.5z"/>',
        'fechar' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'voltar' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
        'cadeado' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'grafico' => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/>',
        'escudo' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
    ];

    $desenho = $desenhos[$nome] ?? '';
    return '<svg class="' . e($classe) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $desenho . '</svg>';
}

/** Campo oculto com a página atual, para a ação voltar a ela (com filtros e paginação). */
function voltar_campo(): string
{
    return '<input type="hidden" name="voltar" value="' . e($_SERVER['REQUEST_URI'] ?? '') . '">';
}

/** Distância até a data em palavras: "hoje", "amanhã", "em 5 dias", "há 3 dias". */
function prazo_relativo(string $data): string
{
    $alvo = DateTime::createFromFormat('!Y-m-d', substr($data, 0, 10));
    if (!$alvo) {
        return '';
    }
    $dias = (int) (new DateTime('today'))->diff($alvo)->format('%r%a');
    return match (true) {
        $dias === 0 => 'hoje',
        $dias === 1 => 'amanhã',
        $dias === -1 => 'ontem',
        $dias > 1 => "em {$dias} dias",
        default => 'há ' . abs($dias) . ' dias',
    };
}

/** Selo da situação de uma obrigação (pendente, vencida, concluída). */
function selo_obrigacao(string $situacao): string
{
    [$rotulo, $tom] = \App\Models\Obrigacao::SITUACOES[$situacao] ?? [$situacao, 'neutro'];
    return selo($rotulo, $tom);
}

/** Selo da situação de um documento; pedido aguardando fora do prazo ganha o selo "Atrasado". */
function selo_documento(string $situacao, bool $atrasado = false): string
{
    if ($situacao === 'aguardando' && $atrasado) {
        return selo('Atrasado', 'perigo');
    }
    [$rotulo, $tom] = \App\Models\Documento::SITUACOES[$situacao] ?? [$situacao, 'neutro'];
    return selo($rotulo, $tom);
}

/** Nome para exibir: o fantasia, quando existe, é o mais reconhecível. */
function nome_cliente(array $linha, string $prefixo = 'cliente_'): string
{
    $fantasia = $linha[$prefixo . 'fantasia'] ?? $linha['nome_fantasia'] ?? null;
    return $fantasia ?: ($linha[$prefixo . 'nome'] ?? $linha['nome'] ?? '');
}

/** "1 vencida", "3 vencidas". */
function plural(int $quantidade, string $singular, string $plural): string
{
    return $quantidade . ' ' . ($quantidade === 1 ? $singular : $plural);
}
