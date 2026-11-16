<?php
/**
 * Layout das páginas internas: menu lateral, topo (no celular) e área de conteúdo.
 * @var string $titulo
 * @var string $conteudo
 */
$usuarioLogado = \App\Core\Auth::usuario();
$rotaAtual = is_string($_GET['r'] ?? null) ? explode('/', $_GET['r'])[0] : 'dashboard';
$menu = [
    'dashboard' => ['Painel', 'painel'],
    'clientes' => ['Clientes', 'clientes'],
    'obrigacoes' => ['Obrigações fiscais', 'obrigacoes'],
    'documentos' => ['Documentos', 'documentos'],
];
$iniciais = '';
foreach (array_slice(preg_split('/\s+/', trim($usuarioLogado['nome'] ?? '')) ?: [], 0, 2) as $parte) {
    $iniciais .= mb_strtoupper(mb_substr($parte, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · <?= e(config('app.nome')) ?></title>
    <link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="app">
    <aside class="lateral" id="menu-lateral" aria-label="Menu">
        <a class="marca" href="<?= e(url('dashboard')) ?>">
            <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="38" height="38">
            <span class="marca__texto">
                <strong><?= e(config('app.nome')) ?></strong>
                <small>Gestão contábil</small>
            </span>
        </a>

        <nav class="lateral__nav" aria-label="Menu principal">
            <p class="lateral__secao">Menu</p>
            <?php foreach ($menu as $rota => [$rotulo, $iconeMenu]): ?>
                <a href="<?= e(url($rota)) ?>" class="lateral__link"<?= $rotaAtual === $rota ? ' aria-current="page"' : '' ?>>
                    <?= icone($iconeMenu) ?>
                    <span><?= e($rotulo) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="lateral__rodape">
            <a class="usuario" href="<?= e(url('conta')) ?>"<?= $rotaAtual === 'conta' ? ' aria-current="page"' : '' ?>>
                <span class="usuario__avatar" aria-hidden="true"><?= e($iniciais) ?></span>
                <span class="usuario__dados">
                    <strong><?= e($usuarioLogado['nome'] ?? '') ?></strong>
                    <small>Minha conta</small>
                </span>
            </a>
            <form method="post" action="<?= e(url('logout')) ?>">
                <?= csrf_campo() ?>
                <button type="submit" class="lateral__sair"><?= icone('sair') ?><span>Sair do sistema</span></button>
            </form>
        </div>
    </aside>
    <div class="lateral__fundo" data-fechar-menu hidden></div>

    <div class="principal">
        <header class="topo-movel">
            <button type="button" class="topo-movel__menu" data-abrir-menu aria-controls="menu-lateral" aria-expanded="false">
                <?= icone('menu') ?><span class="oculto">Abrir menu</span>
            </button>
            <a class="marca marca--topo" href="<?= e(url('dashboard')) ?>">
                <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="30" height="30">
                <strong><?= e(config('app.nome')) ?></strong>
            </a>
        </header>

        <main class="conteudo" id="conteudo">
            <?php require RAIZ . '/app/Views/partials/flash.php'; ?>
            <?= $conteudo ?>
        </main>

        <footer class="rodape">
            <?= e(config('app.nome')) ?> · Sistema de gestão para escritórios de contabilidade
        </footer>
    </div>
</body>
</html>
