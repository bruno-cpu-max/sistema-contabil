<?php
/**
 * @var array $filtros busca, cliente_id, situacao, de, ate
 * @var array $obrigacoes
 * @var array $clientes
 * @var \App\Core\Paginacao $paginacao
 */
$filtrando = $filtros['busca'] !== '' || $filtros['cliente_id'] || $filtros['situacao'] !== '' || $filtros['de'] || $filtros['ate'];
?>
<div class="cabecalho">
    <div>
        <h1>Obrigações fiscais</h1>
        <p class="cabecalho__sub">Guias e declarações dos clientes. Uma obrigação pendente passa a <strong>Vencida</strong> sozinha quando o vencimento passa.</p>
    </div>
    <div class="cabecalho__acoes">
        <a class="botao" href="<?= e(url('obrigacoes/form')) ?>"><?= icone('mais') ?> Nova obrigação</a>
    </div>
</div>

<section class="cartao">
    <form class="filtros filtros--grade" method="get" action="<?= e($_SERVER['SCRIPT_NAME']) ?>" role="search">
        <input type="hidden" name="r" value="obrigacoes">
        <div class="campo campo--busca">
            <label for="busca">Pesquisar</label>
            <div class="filtros__busca">
                <?= icone('busca') ?>
                <input id="busca" name="busca" type="search" value="<?= e($filtros['busca']) ?>" placeholder="Obrigação ou nome do cliente" maxlength="100">
            </div>
        </div>
        <div class="campo">
            <label for="cliente_id">Cliente</label>
            <select id="cliente_id" name="cliente_id">
                <option value="">Todos os clientes</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"<?= selecionado($filtros['cliente_id'], $c['id']) ?>><?= e(nome_cliente($c, '')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="situacao">Situação</label>
            <select id="situacao" name="situacao">
                <option value="">Todas</option>
                <?php foreach (\App\Models\Obrigacao::SITUACOES as $valor => [$rotulo]): ?>
                    <option value="<?= e($valor) ?>"<?= selecionado($filtros['situacao'], $valor) ?>><?= e($rotulo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="de">Vencimento de</label>
            <input id="de" name="de" type="date" value="<?= e($filtros['de'] ?? '') ?>">
        </div>
        <div class="campo">
            <label for="ate">até</label>
            <input id="ate" name="ate" type="date" value="<?= e($filtros['ate'] ?? '') ?>">
        </div>
        <div class="filtros__botoes">
            <button type="submit" class="botao"><?= icone('filtro') ?> Filtrar</button>
            <?php if ($filtrando): ?>
                <a class="botao botao--secundario" href="<?= e(url('obrigacoes')) ?>">Limpar</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($obrigacoes === []): ?>
        <?php $mensagemVazio = $filtrando ? 'Nenhuma obrigação encontrada com esses filtros.' : 'Nenhuma obrigação cadastrada ainda.'; ?>
        <?php require RAIZ . '/app/Views/partials/vazio.php'; ?>
    <?php else: ?>
        <div class="tabela-rolagem">
            <table class="tabela">
                <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Obrigação</th>
                    <th>Vencimento</th>
                    <th class="numero">Valor</th>
                    <th>Situação</th>
                    <th class="acoes-titulo">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($obrigacoes as $o): ?>
                    <tr<?= $o['situacao'] === 'vencida' ? ' class="linha--alerta"' : '' ?>>
                        <td><a class="link-forte" href="<?= e(url('clientes/ver', ['id' => $o['cliente_id']])) ?>"><?= e(nome_cliente($o)) ?></a></td>
                        <td>
                            <?= e($o['nome']) ?>
                            <?php if ($o['descricao']): ?><small class="sublinha"><?= e(mb_strimwidth($o['descricao'], 0, 70, '…')) ?></small><?php endif; ?>
                        </td>
                        <td class="nao-quebra">
                            <?= e(data_br($o['vencimento'])) ?>
                            <?php if ($o['situacao'] !== 'concluida'): ?>
                                <small class="prazo prazo--<?= $o['situacao'] === 'vencida' ? 'atrasado' : 'normal' ?>"><?= e(prazo_relativo($o['vencimento'])) ?></small>
                            <?php else: ?>
                                <small class="sublinha">concluída em <?= e(data_br($o['concluida_em'])) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="numero"><?= e(dinheiro($o['valor'])) ?></td>
                        <td><?= selo_obrigacao($o['situacao']) ?></td>
                        <td class="acoes">
                            <?php if ($o['situacao'] !== 'concluida'): ?>
                                <form method="post" action="<?= e(url('obrigacoes/concluir')) ?>">
                                    <?= csrf_campo() ?><?= voltar_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                    <button type="submit" class="botao-icone botao-icone--sucesso" title="Marcar como concluída">
                                        <?= icone('check') ?><span class="oculto">Concluir <?= e($o['nome']) ?> de <?= e(nome_cliente($o)) ?></span>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a class="botao-icone" href="<?= e(url('obrigacoes/form', ['id' => $o['id']])) ?>" title="Editar">
                                <?= icone('editar') ?><span class="oculto">Editar <?= e($o['nome']) ?> de <?= e(nome_cliente($o)) ?></span>
                            </a>
                            <form method="post" action="<?= e(url('obrigacoes/excluir')) ?>"
                                  data-confirmar="Excluir a obrigação &quot;<?= e($o['nome']) ?>&quot; de <?= e(nome_cliente($o)) ?>?">
                                <?= csrf_campo() ?><?= voltar_campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                <button type="submit" class="botao-icone botao-icone--perigo" title="Excluir">
                                    <?= icone('excluir') ?><span class="oculto">Excluir <?= e($o['nome']) ?> de <?= e(nome_cliente($o)) ?></span>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php
    $rotaPaginacao = 'obrigacoes';
    $parametrosPaginacao = [
        'busca' => $filtros['busca'], 'cliente_id' => $filtros['cliente_id'], 'situacao' => $filtros['situacao'],
        'de' => $filtros['de'], 'ate' => $filtros['ate'],
    ];
    require RAIZ . '/app/Views/partials/paginacao.php';
    ?>
</section>
