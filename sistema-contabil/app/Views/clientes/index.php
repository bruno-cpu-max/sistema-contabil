<?php
/**
 * @var string $busca
 * @var array $clientes
 * @var \App\Core\Paginacao $paginacao
 */
?>
<div class="cabecalho">
    <div>
        <h1>Clientes</h1>
        <p class="cabecalho__sub">Empresas e pessoas físicas atendidas pelo escritório.</p>
    </div>
    <div class="cabecalho__acoes">
        <a class="botao" href="<?= e(url('clientes/form')) ?>"><?= icone('mais') ?> Novo cliente</a>
    </div>
</div>

<section class="cartao">
    <form class="filtros" method="get" action="<?= e($_SERVER['SCRIPT_NAME']) ?>" role="search">
        <input type="hidden" name="r" value="clientes">
        <div class="filtros__busca">
            <label class="oculto" for="busca">Pesquisar clientes</label>
            <?= icone('busca') ?>
            <input id="busca" name="busca" type="search" value="<?= e($busca) ?>" placeholder="Pesquisar por nome, nome fantasia ou CPF/CNPJ" maxlength="100">
        </div>
        <button type="submit" class="botao"><?= icone('busca') ?> Pesquisar</button>
        <?php if ($busca !== ''): ?>
            <a class="botao botao--secundario" href="<?= e(url('clientes')) ?>"><?= icone('fechar') ?> Limpar</a>
        <?php endif; ?>
    </form>

    <?php if ($clientes === []): ?>
        <?php $mensagemVazio = $busca !== '' ? 'Nenhum cliente encontrado para "' . $busca . '".' : 'Nenhum cliente cadastrado ainda.'; ?>
        <?php require RAIZ . '/app/Views/partials/vazio.php'; ?>
    <?php else: ?>
        <div class="tabela-rolagem">
            <table class="tabela">
                <thead>
                <tr>
                    <th>Cliente</th>
                    <th>CPF / CNPJ</th>
                    <th>Contato</th>
                    <th>Cidade</th>
                    <th>Pendências</th>
                    <th class="acoes-titulo">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td>
                            <a class="link-forte" href="<?= e(url('clientes/ver', ['id' => $c['id']])) ?>"><?= e($c['nome']) ?></a>
                            <?php if ($c['nome_fantasia']): ?><small class="sublinha"><?= e($c['nome_fantasia']) ?></small><?php endif; ?>
                        </td>
                        <td class="nao-quebra"><?= e(documento_formatado($c['documento'])) ?></td>
                        <td>
                            <?= e($c['email'] ?? '—') ?>
                            <?php if ($c['telefone']): ?><small class="sublinha"><?= e(telefone_formatado($c['telefone'])) ?></small><?php endif; ?>
                        </td>
                        <td><?= e($c['cidade'] ?? '—') ?></td>
                        <td>
                            <div class="selos">
                                <?php if ($c['obrigacoes_vencidas'] > 0): ?><?= selo(plural((int) $c['obrigacoes_vencidas'], 'vencida', 'vencidas'), 'perigo') ?><?php endif; ?>
                                <?php if ($c['obrigacoes_pendentes'] > 0): ?><?= selo(plural((int) $c['obrigacoes_pendentes'], 'pendente', 'pendentes'), 'aviso') ?><?php endif; ?>
                                <?php if ($c['documentos_aguardando'] > 0): ?><?= selo(plural((int) $c['documentos_aguardando'], 'doc. aguardando', 'docs. aguardando'), 'info') ?><?php endif; ?>
                                <?php if ($c['obrigacoes_vencidas'] + $c['obrigacoes_pendentes'] + $c['documentos_aguardando'] == 0): ?><?= selo('Em dia', 'sucesso') ?><?php endif; ?>
                            </div>
                        </td>
                        <td class="acoes">
                            <a class="botao-icone" href="<?= e(url('clientes/ver', ['id' => $c['id']])) ?>" title="Ver ficha">
                                <?= icone('ver') ?><span class="oculto">Ver ficha de <?= e($c['nome']) ?></span>
                            </a>
                            <a class="botao-icone" href="<?= e(url('clientes/form', ['id' => $c['id']])) ?>" title="Editar">
                                <?= icone('editar') ?><span class="oculto">Editar <?= e($c['nome']) ?></span>
                            </a>
                            <form method="post" action="<?= e(url('clientes/excluir')) ?>"
                                  data-confirmar="Excluir o cliente &quot;<?= e($c['nome']) ?>&quot;? As obrigações e os documentos dele também serão excluídos. Esta ação não pode ser desfeita.">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <button type="submit" class="botao-icone botao-icone--perigo" title="Excluir">
                                    <?= icone('excluir') ?><span class="oculto">Excluir <?= e($c['nome']) ?></span>
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
    $rotaPaginacao = 'clientes';
    $parametrosPaginacao = ['busca' => $busca];
    require RAIZ . '/app/Views/partials/paginacao.php';
    ?>
</section>
