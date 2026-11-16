<?php
/**
 * Solicitação de documento: cadastro, edição, troca de situação e envio do arquivo.
 * @var ?int $id
 * @var array $documento
 * @var array $erros
 * @var array $clientes
 * @var string $voltar
 */
$voltarPara = $voltar !== '' ? $voltar : url('documentos');
$tiposAceitos = '.' . implode(',.', array_keys(config('uploads.tipos')));
?>
<div class="cabecalho">
    <div>
        <a class="link-voltar" href="<?= e($voltarPara) ?>"><?= icone('voltar') ?> Voltar</a>
        <h1><?= $id ? 'Solicitação de documento' : 'Solicitar documento' ?></h1>
        <p class="cabecalho__sub">
            <?= $id ? 'Altere os dados, anexe o arquivo recebido ou mude a situação.' : 'Registre o documento que o cliente precisa enviar ao escritório.' ?>
        </p>
    </div>
</div>

<?php if ($clientes === []): ?>
    <div class="alerta alerta--erro" role="alert">
        <?= icone('alerta') ?><span>Cadastre um cliente antes de solicitar documentos. <a href="<?= e(url('clientes/form')) ?>">Cadastrar cliente</a></span>
    </div>
<?php elseif ($erros !== []): ?>
    <div class="alerta alerta--erro" role="alert">
        <?= icone('alerta') ?>
        <span>Confira os campos destacados abaixo.<?= isset($_FILES['arquivo']) && ($_FILES['arquivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? ' Por segurança, escolha o arquivo de novo.' : '' ?></span>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(url('documentos/salvar')) ?>" class="cartao formulario" enctype="multipart/form-data" novalidate>
    <?= csrf_campo() ?>
    <input type="hidden" name="voltar" value="<?= e($voltar) ?>">
    <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <h2 class="formulario__titulo">Solicitação</h2>
    <div class="grade-campos">
        <div class="campo campo--largo">
            <label for="cliente_id">Cliente que deve enviar <span class="obrigatorio">*</span></label>
            <select id="cliente_id" name="cliente_id" required<?= attr_erro($erros, 'cliente_id') ?>>
                <option value="">Selecione o cliente</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"<?= selecionado($documento['cliente_id'], $c['id']) ?>>
                        <?= e($c['nome']) ?><?= $c['nome_fantasia'] ? ' (' . e($c['nome_fantasia']) . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= erro_campo($erros, 'cliente_id') ?>
        </div>

        <div class="campo campo--largo">
            <label for="titulo">Documento solicitado <span class="obrigatorio">*</span></label>
            <input id="titulo" name="titulo" type="text" value="<?= e($documento['titulo']) ?>" maxlength="150" list="tipos-documento"
                   placeholder="Ex.: Extrato bancário" required<?= attr_erro($erros, 'titulo') ?>>
            <datalist id="tipos-documento">
                <?php foreach (\App\Models\Documento::TIPOS_COMUNS as $tipo): ?><option value="<?= e($tipo) ?>"><?php endforeach; ?>
            </datalist>
            <?= erro_campo($erros, 'titulo') ?>
        </div>

        <div class="campo">
            <label for="data_limite">Data limite para envio <span class="obrigatorio">*</span></label>
            <input id="data_limite" name="data_limite" type="date" value="<?= e($documento['data_limite']) ?>" required<?= attr_erro($erros, 'data_limite') ?>>
            <?= erro_campo($erros, 'data_limite') ?>
        </div>

        <div class="campo">
            <label for="situacao">Situação <span class="obrigatorio">*</span></label>
            <select id="situacao" name="situacao"<?= attr_erro($erros, 'situacao') ?>>
                <?php foreach (\App\Models\Documento::SITUACOES as $valor => [$rotulo]): ?>
                    <option value="<?= e($valor) ?>"<?= selecionado($documento['situacao'], $valor) ?>><?= e($rotulo) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="campo__ajuda">Ao anexar um arquivo, um pedido "Aguardando envio" passa a "Recebido".</p>
            <?= erro_campo($erros, 'situacao') ?>
        </div>

        <div class="campo campo--largo">
            <label for="descricao">Instruções para o cliente</label>
            <textarea id="descricao" name="descricao" rows="3" maxlength="2000" placeholder="Período, formato, detalhes do que precisa ser enviado..."<?= attr_erro($erros, 'descricao') ?>><?= e($documento['descricao']) ?></textarea>
            <?= erro_campo($erros, 'descricao') ?>
        </div>
    </div>

    <h2 class="formulario__titulo">Arquivo</h2>
    <?php if (!empty($documento['arquivo_salvo'])): ?>
        <div class="arquivo-atual">
            <span class="arquivo-atual__icone"><?= icone('anexo') ?></span>
            <span class="arquivo-atual__texto">
                <strong><?= e($documento['arquivo_nome']) ?></strong>
                <small><?= e(tamanho_arquivo((int) $documento['arquivo_tamanho'])) ?> · enviado em <?= e(data_hora_br($documento['enviado_em'])) ?></small>
            </span>
            <a class="botao botao--secundario botao--pequeno" href="<?= e(url('documentos/arquivo', ['id' => $id])) ?>" target="_blank" rel="noopener"><?= icone('ver') ?> Abrir</a>
            <a class="botao botao--secundario botao--pequeno" href="<?= e(url('documentos/arquivo', ['id' => $id, 'baixar' => 1])) ?>"><?= icone('baixar') ?> Baixar</a>
        </div>
    <?php endif; ?>

    <div class="campo">
        <label for="arquivo"><?= !empty($documento['arquivo_salvo']) ? 'Substituir arquivo' : 'Anexar arquivo recebido' ?></label>
        <label class="envio<?= isset($erros['arquivo']) ? ' envio--erro' : '' ?>" for="arquivo">
            <?= icone('upload', 'icone envio__icone') ?>
            <span class="envio__texto" data-nome-arquivo>Clique para escolher o arquivo</span>
            <small>PDF, JPG, PNG, DOC, DOCX, XLS ou XLSX · até <?= e(tamanho_arquivo(config('uploads.tamanho_maximo'))) ?></small>
            <input id="arquivo" name="arquivo" type="file" accept="<?= e($tiposAceitos) ?>" class="envio__input"<?= attr_erro($erros, 'arquivo') ?>>
        </label>
        <?= erro_campo($erros, 'arquivo') ?>
    </div>

    <div class="formulario__acoes">
        <a class="botao botao--secundario" href="<?= e($voltarPara) ?>">Cancelar</a>
        <button type="submit" class="botao"><?= icone('check') ?> <?= $id ? 'Salvar' : 'Registrar solicitação' ?></button>
    </div>
</form>
