<?php
/**
 * Layout mínimo (páginas de erro para quem não está logado).
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
<body class="pagina-simples">
    <main class="conteudo conteudo--centro">
        <?= $conteudo ?>
    </main>
</body>
</html>
