<?php
/** @var string $login @var array $erros */
?>
<form method="post" action="<?= e(url('login')) ?>" class="login__formulario" novalidate>
    <?= csrf_campo() ?>
    <h1>Bem-vindo de volta</h1>
    <p class="texto-suave">Entre com seu usuário ou e-mail para acessar o painel do escritório.</p>

    <?php if (isset($erros['geral'])): ?>
        <div class="alerta alerta--erro" role="alert"><?= icone('alerta') ?><span><?= e($erros['geral']) ?></span></div>
    <?php endif; ?>

    <div class="campo">
        <label for="login">Usuário ou e-mail</label>
        <div class="campo__com-icone">
            <?= icone('conta') ?>
            <input id="login" name="login" type="text" value="<?= e($login) ?>" autocomplete="username" required autofocus
                   maxlength="150"<?= attr_erro($erros, 'login') ?>>
        </div>
        <?= erro_campo($erros, 'login') ?>
    </div>

    <div class="campo">
        <label for="senha">Senha</label>
        <div class="campo__com-icone">
            <?= icone('cadeado') ?>
            <input id="senha" name="senha" type="password" autocomplete="current-password" required<?= attr_erro($erros, 'senha') ?>>
        </div>
        <?= erro_campo($erros, 'senha') ?>
    </div>

    <button type="submit" class="botao botao--largo botao--grande">Entrar</button>
</form>
