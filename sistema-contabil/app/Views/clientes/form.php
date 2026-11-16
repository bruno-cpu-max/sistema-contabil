<?php
/**
 * Cadastro e edição de cliente.
 * @var ?int $id
 * @var array $cliente
 * @var array $erros
 */
$voltarPara = $id ? url('clientes/ver', ['id' => $id]) : url('clientes');
?>
<div class="cabecalho">
    <div>
        <a class="link-voltar" href="<?= e($voltarPara) ?>"><?= icone('voltar') ?> Voltar</a>
        <h1><?= $id ? 'Editar cliente' : 'Novo cliente' ?></h1>
        <p class="cabecalho__sub">Campos marcados com <span class="obrigatorio">*</span> são obrigatórios.</p>
    </div>
</div>

<?php if ($erros !== []): ?>
    <div class="alerta alerta--erro" role="alert"><?= icone('alerta') ?><span>Confira os campos destacados abaixo.</span></div>
<?php endif; ?>

<form method="post" action="<?= e(url('clientes/salvar')) ?>" class="cartao formulario" novalidate>
    <?= csrf_campo() ?>
    <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <h2 class="formulario__titulo">Identificação</h2>
    <div class="grade-campos">
        <div class="campo campo--largo">
            <label for="nome">Nome ou razão social <span class="obrigatorio">*</span></label>
            <input id="nome" name="nome" type="text" value="<?= e($cliente['nome']) ?>" maxlength="150" required<?= attr_erro($erros, 'nome') ?>>
            <?= erro_campo($erros, 'nome') ?>
        </div>
        <div class="campo">
            <label for="nome_fantasia">Nome fantasia</label>
            <input id="nome_fantasia" name="nome_fantasia" type="text" value="<?= e($cliente['nome_fantasia']) ?>" maxlength="150"<?= attr_erro($erros, 'nome_fantasia') ?>>
            <?= erro_campo($erros, 'nome_fantasia') ?>
        </div>
        <div class="campo">
            <label for="documento">CPF ou CNPJ <span class="obrigatorio">*</span></label>
            <input id="documento" name="documento" type="text" inputmode="numeric" value="<?= e($cliente['documento']) ?>"
                   maxlength="18" placeholder="000.000.000-00 ou 00.000.000/0000-00" data-mascara="documento" required<?= attr_erro($erros, 'documento') ?>>
            <?= erro_campo($erros, 'documento') ?>
        </div>
    </div>

    <h2 class="formulario__titulo">Contato e endereço</h2>
    <div class="grade-campos">
        <div class="campo">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" value="<?= e($cliente['email']) ?>" maxlength="150" placeholder="contato@empresa.com.br"<?= attr_erro($erros, 'email') ?>>
            <?= erro_campo($erros, 'email') ?>
        </div>
        <div class="campo">
            <label for="telefone">Telefone</label>
            <input id="telefone" name="telefone" type="tel" value="<?= e($cliente['telefone']) ?>" maxlength="15"
                   placeholder="(31) 99999-8888" data-mascara="telefone"<?= attr_erro($erros, 'telefone') ?>>
            <?= erro_campo($erros, 'telefone') ?>
        </div>
        <div class="campo campo--largo">
            <label for="endereco">Endereço</label>
            <input id="endereco" name="endereco" type="text" value="<?= e($cliente['endereco']) ?>" maxlength="200" placeholder="Rua, número, complemento, bairro"<?= attr_erro($erros, 'endereco') ?>>
            <?= erro_campo($erros, 'endereco') ?>
        </div>
        <div class="campo">
            <label for="cidade">Cidade</label>
            <input id="cidade" name="cidade" type="text" value="<?= e($cliente['cidade']) ?>" maxlength="100" placeholder="Belo Horizonte/MG"<?= attr_erro($erros, 'cidade') ?>>
            <?= erro_campo($erros, 'cidade') ?>
        </div>
        <div class="campo campo--largo">
            <label for="observacoes">Observações</label>
            <textarea id="observacoes" name="observacoes" rows="4" maxlength="2000" placeholder="Regime tributário, particularidades, contatos..."<?= attr_erro($erros, 'observacoes') ?>><?= e($cliente['observacoes']) ?></textarea>
            <?= erro_campo($erros, 'observacoes') ?>
        </div>
    </div>

    <div class="formulario__acoes">
        <a class="botao botao--secundario" href="<?= e($voltarPara) ?>">Cancelar</a>
        <button type="submit" class="botao"><?= icone('check') ?> <?= $id ? 'Salvar alterações' : 'Cadastrar cliente' ?></button>
    </div>
</form>
