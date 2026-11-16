<?php
/**
 * Layout da tela de login.
 * @var string $titulo
 * @var string $conteudo
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · <?= e(config('app.nome')) ?></title>
    <link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="pagina-login">
    <section class="login__marca">
        <div class="marca marca--clara">
            <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="44" height="44">
            <span class="marca__texto">
                <strong><?= e(config('app.nome')) ?></strong>
                <small>Gestão contábil</small>
            </span>
        </div>

        <div class="login__chamada">
            <h2>A rotina do escritório organizada em um só lugar.</h2>
            <ul class="login__destaques">
                <li><?= icone('clientes') ?> Cadastro completo de clientes e empresas</li>
                <li><?= icone('calendario') ?> Controle de obrigações e vencimentos</li>
                <li><?= icone('documentos') ?> Documentos dos clientes organizados, sem depender do WhatsApp</li>
            </ul>
        </div>

        <p class="login__rodape"><?= icone('escudo') ?> Acesso restrito à equipe do escritório</p>
    </section>

    <main class="login__area">
        <?php require RAIZ . '/app/Views/partials/flash.php'; ?>
        <?= $conteudo ?>
    </main>
</body>
</html>
