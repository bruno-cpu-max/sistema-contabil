<?php
/**
 * @var array $usuario
 * @var array $erros
 */
?>
<div class="cabecalho">
    <div>
        <h1>Minha conta</h1>
        <p class="cabecalho__sub">Seus dados de acesso ao sistema.</p>
    </div>
</div>

<div class="grade-ficha">
    <section class="cartao ficha">
        <h2>Dados de acesso</h2>
        <dl class="dados">
            <dt>Nome</dt><dd><?= e($usuario['nome']) ?></dd>
            <dt>Usuário</dt><dd><?= e($usuario['usuario']) ?></dd>
            <dt>E-mail</dt><dd><?= e($usuario['email']) ?></dd>
        </dl>
    </section>

    <form method="post" action="<?= e(url('conta/senha')) ?>" class="cartao formulario" novalidate>
        <?= csrf_campo() ?>
        <h2 class="formulario__titulo">Alterar senha</h2>
        <div class="campo">
            <label for="senha_atual">Senha atual</label>
            <input id="senha_atual" name="senha_atual" type="password" autocomplete="current-password" required<?= attr_erro($erros, 'senha_atual') ?>>
            <?= erro_campo($erros, 'senha_atual') ?>
        </div>
        <div class="campo">
            <label for="nova_senha">Nova senha</label>
            <input id="nova_senha" name="nova_senha" type="password" autocomplete="new-password" minlength="8" required<?= attr_erro($erros, 'nova_senha') ?>>
            <p class="campo__ajuda">Pelo menos 8 caracteres.</p>
            <?= erro_campo($erros, 'nova_senha') ?>
        </div>
        <div class="campo">
            <label for="confirmacao">Confirme a nova senha</label>
            <input id="confirmacao" name="confirmacao" type="password" autocomplete="new-password" required<?= attr_erro($erros, 'confirmacao') ?>>
            <?= erro_campo($erros, 'confirmacao') ?>
        </div>
        <div class="formulario__acoes">
            <button type="submit" class="botao"><?= icone('cadeado') ?> Alterar senha</button>
        </div>
    </form>
</div>
