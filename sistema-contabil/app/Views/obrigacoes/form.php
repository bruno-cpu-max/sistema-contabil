<?php
/**
 * Cadastro e edição de obrigação fiscal.
 * @var ?int $id
 * @var array $obrigacao
 * @var array $erros
 * @var array $clientes
 * @var string $voltar página de origem (para voltar a ela depois de salvar)
 */
$voltarPara = $voltar !== '' ? $voltar : url('obrigacoes');
?>
<div class="cabecalho">
    <div>
        <a class="link-voltar" href="<?= e($voltarPara) ?>"><?= icone('voltar') ?> Voltar</a>
        <h1><?= $id ? 'Editar obrigação' : 'Nova obrigação' ?></h1>
        <p class="cabecalho__sub">Campos marcados com <span class="obrigatorio">*</span> são obrigatórios.</p>
    </div>
</div>

<?php if ($clientes === []): ?>
    <div class="alerta alerta--erro" role="alert">
        <?= icone('alerta') ?><span>Cadastre um cliente antes de registrar obrigações. <a href="<?= e(url('clientes/form')) ?>">Cadastrar cliente</a></span>
    </div>
<?php elseif ($erros !== []): ?>
    <div class="alerta alerta--erro" role="alert"><?= icone('alerta') ?><span>Confira os campos destacados abaixo.</span></div>
<?php endif; ?>

<form method="post" action="<?= e(url('obrigacoes/salvar')) ?>" class="cartao formulario" novalidate>
    <?= csrf_campo() ?>
    <input type="hidden" name="voltar" value="<?= e($voltar) ?>">
    <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <div class="grade-campos">
        <div class="campo campo--largo">
            <label for="cliente_id">Cliente <span class="obrigatorio">*</span></label>
            <select id="cliente_id" name="cliente_id" required<?= attr_erro($erros, 'cliente_id') ?>>
                <option value="">Selecione o cliente</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"<?= selecionado($obrigacao['cliente_id'], $c['id']) ?>>
                        <?= e($c['nome']) ?><?= $c['nome_fantasia'] ? ' (' . e($c['nome_fantasia']) . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= erro_campo($erros, 'cliente_id') ?>
        </div>

        <div class="campo campo--largo">
            <label for="nome">Obrigação <span class="obrigatorio">*</span></label>
            <input id="nome" name="nome" type="text" value="<?= e($obrigacao['nome']) ?>" maxlength="100" list="tipos-obrigacao"
                   placeholder="Ex.: DAS - Simples Nacional" required<?= attr_erro($erros, 'nome') ?>>
            <datalist id="tipos-obrigacao">
                <?php foreach (\App\Models\Obrigacao::TIPOS_COMUNS as $tipo): ?><option value="<?= e($tipo) ?>"><?php endforeach; ?>
            </datalist>
            <p class="campo__ajuda">Escolha uma sugestão ou digite outro nome.</p>
            <?= erro_campo($erros, 'nome') ?>
        </div>

        <div class="campo">
            <label for="valor">Valor (R$)</label>
            <input id="valor" name="valor" type="text" inputmode="decimal" value="<?= e($obrigacao['valor']) ?>" maxlength="16"
                   placeholder="0,00"<?= attr_erro($erros, 'valor') ?>>
            <p class="campo__ajuda">Deixe em branco para declarações sem valor a pagar.</p>
            <?= erro_campo($erros, 'valor') ?>
        </div>

        <div class="campo">
            <label for="vencimento">Data de vencimento <span class="obrigatorio">*</span></label>
            <input id="vencimento" name="vencimento" type="date" value="<?= e($obrigacao['vencimento']) ?>" required<?= attr_erro($erros, 'vencimento') ?>>
            <?= erro_campo($erros, 'vencimento') ?>
        </div>

        <div class="campo">
            <label for="status">Situação <span class="obrigatorio">*</span></label>
            <select id="status" name="status"<?= attr_erro($erros, 'status') ?>>
                <option value="pendente"<?= selecionado($obrigacao['status'], 'pendente') ?>>Pendente</option>
                <option value="concluida"<?= selecionado($obrigacao['status'], 'concluida') ?>>Concluída</option>
            </select>
            <p class="campo__ajuda">"Vencida" é identificada automaticamente pela data.</p>
            <?= erro_campo($erros, 'status') ?>
        </div>

        <div class="campo campo--largo">
            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" rows="3" maxlength="2000" placeholder="Competência, detalhes da guia, observações..."<?= attr_erro($erros, 'descricao') ?>><?= e($obrigacao['descricao']) ?></textarea>
            <?= erro_campo($erros, 'descricao') ?>
        </div>
    </div>

    <div class="formulario__acoes">
        <a class="botao botao--secundario" href="<?= e($voltarPara) ?>">Cancelar</a>
        <button type="submit" class="botao"><?= icone('check') ?> <?= $id ? 'Salvar alterações' : 'Cadastrar obrigação' ?></button>
    </div>
</form>
