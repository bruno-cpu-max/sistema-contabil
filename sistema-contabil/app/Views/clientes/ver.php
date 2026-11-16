<?php
/**
 * Ficha do cliente: dados, obrigações e documentos (tudo do cliente em um só lugar).
 * @var array $cliente
 * @var array $obrigacoes
 * @var array $documentos
 */
$contagem = ['pendente' => 0, 'vencida' => 0, 'concluida' => 0];
foreach ($obrigacoes as $o) {
    $contagem[$o['situacao']]++;
}
$docsAguardando = count(array_filter($documentos, static fn ($d) => $d['situacao'] === 'aguardando'));
?>
<div class="cabecalho">
    <div>
        <a class="link-voltar" href="<?= e(url('clientes')) ?>"><?= icone('voltar') ?> Clientes</a>
        <h1><?= e($cliente['nome']) ?></h1>
        <p class="cabecalho__sub">
            <?= $cliente['nome_fantasia'] ? e($cliente['nome_fantasia']) . ' · ' : '' ?>
            <?= strlen($cliente['documento']) === 14 ? 'CNPJ' : 'CPF' ?> <?= e(documento_formatado($cliente['documento'])) ?>
        </p>
    </div>
    <div class="cabecalho__acoes">
        <a class="botao botao--secundario" href="<?= e(url('clientes/form', ['id' => $cliente['id']])) ?>"><?= icone('editar') ?> Editar</a>
        <form method="post" action="<?= e(url('clientes/excluir')) ?>"
              data-confirmar="Excluir o cliente &quot;<?= e($cliente['nome']) ?>&quot;? <?= e(plural(count($obrigacoes), "obrigação", "obrigações")) ?> e <?= e(plural(count($documentos), "documento", "documentos")) ?> deste cliente também serão excluídos. Esta ação não pode ser desfeita.">
            <?= csrf_campo() ?>
            <input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>">
            <button type="submit" class="botao botao--perigo-suave"><?= icone('excluir') ?> Excluir</button>
        </form>
    </div>
</div>

<div class="grade-ficha">
    <section class="cartao ficha">
        <h2>Dados cadastrais</h2>
        <dl class="dados">
            <dt>E-mail</dt><dd><?= $cliente['email'] ? '<a href="mailto:' . e($cliente['email']) . '">' . e($cliente['email']) . '</a>' : '—' ?></dd>
            <dt>Telefone</dt><dd><?= e($cliente['telefone'] ? telefone_formatado($cliente['telefone']) : '—') ?></dd>
            <dt>Endereço</dt><dd><?= e($cliente['endereco'] ?? '—') ?></dd>
            <dt>Cidade</dt><dd><?= e($cliente['cidade'] ?? '—') ?></dd>
            <dt>Cliente desde</dt><dd><?= e(data_br($cliente['criado_em'])) ?></dd>
        </dl>
        <?php if ($cliente['observacoes']): ?>
            <h3>Observações</h3>
            <p class="observacoes"><?= nl2br(e($cliente['observacoes'])) ?></p>
        <?php endif; ?>

        <div class="mini-indicadores">
            <div><strong><?= $contagem['vencida'] ?></strong><span>vencidas</span></div>
            <div><strong><?= $contagem['pendente'] ?></strong><span>pendentes</span></div>
            <div><strong><?= $contagem['concluida'] ?></strong><span>concluídas</span></div>
            <div><strong><?= $docsAguardando ?></strong><span>docs. aguardando</span></div>
        </div>
    </section>

    <div class="pilha">
        <section class="cartao">
            <div class="cartao__cabecalho">
                <h2>Obrigações fiscais</h2>
                <a class="botao botao--pequeno" href="<?= e(url('obrigacoes/form', ['cliente_id' => $cliente['id']])) ?>"><?= icone('mais') ?> Nova obrigação</a>
            </div>
            <?php if ($obrigacoes === []): ?>
                <?php $mensagemVazio = 'Nenhuma obrigação cadastrada para este cliente.'; require RAIZ . '/app/Views/partials/vazio.php'; ?>
            <?php else: ?>
                <div class="tabela-rolagem">
                    <table class="tabela">
                        <thead><tr><th>Obrigação</th><th>Vencimento</th><th class="numero">Valor</th><th>Situação</th><th class="acoes-titulo">Ações</th></tr></thead>
                        <tbody>
                        <?php foreach ($obrigacoes as $o): ?>
                            <tr>
                                <td><?= e($o['nome']) ?><?php if ($o['descricao']): ?><small class="sublinha"><?= e(mb_strimwidth($o['descricao'], 0, 70, '…')) ?></small><?php endif; ?></td>
                                <td class="nao-quebra"><?= e(data_br($o['vencimento'])) ?></td>
                                <td class="numero"><?= e(dinheiro($o['valor'])) ?></td>
                                <td><?= selo_obrigacao($o['situacao']) ?></td>
                                <td class="acoes">
                                    <?php if ($o['situacao'] !== 'concluida'): ?>
                                        <form method="post" action="<?= e(url('obrigacoes/concluir')) ?>">
                                            <?= csrf_campo() ?><?= voltar_campo() ?>
                                            <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                            <button type="submit" class="botao-icone botao-icone--sucesso" title="Marcar como concluída"><?= icone('check') ?><span class="oculto">Concluir <?= e($o['nome']) ?></span></button>
                                        </form>
                                    <?php endif; ?>
                                    <a class="botao-icone" href="<?= e(url('obrigacoes/form', ['id' => $o['id']])) ?>" title="Editar"><?= icone('editar') ?><span class="oculto">Editar <?= e($o['nome']) ?></span></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="cartao">
            <div class="cartao__cabecalho">
                <h2>Documentos</h2>
                <a class="botao botao--pequeno" href="<?= e(url('documentos/form', ['cliente_id' => $cliente['id']])) ?>"><?= icone('mais') ?> Solicitar documento</a>
            </div>
            <?php if ($documentos === []): ?>
                <?php $mensagemVazio = 'Nenhum documento solicitado a este cliente.'; require RAIZ . '/app/Views/partials/vazio.php'; ?>
            <?php else: ?>
                <div class="tabela-rolagem">
                    <table class="tabela">
                        <thead><tr><th>Documento</th><th>Prazo</th><th>Situação</th><th>Arquivo</th><th class="acoes-titulo">Ações</th></tr></thead>
                        <tbody>
                        <?php foreach ($documentos as $d): ?>
                            <tr>
                                <td><?= e($d['titulo']) ?></td>
                                <td class="nao-quebra"><?= e(data_br($d['data_limite'])) ?></td>
                                <td><?= selo_documento($d['situacao'], (bool) $d['atrasado']) ?></td>
                                <td>
                                    <?php if ($d['arquivo_salvo']): ?>
                                        <a class="arquivo" href="<?= e(url('documentos/arquivo', ['id' => $d['id']])) ?>" target="_blank" rel="noopener">
                                            <?= icone('anexo') ?> <?= e(mb_strimwidth($d['arquivo_nome'], 0, 30, '…')) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="texto-suave">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="acoes">
                                    <a class="botao-icone" href="<?= e(url('documentos/form', ['id' => $d['id']])) ?>" title="Editar / anexar"><?= icone('editar') ?><span class="oculto">Editar <?= e($d['titulo']) ?></span></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
