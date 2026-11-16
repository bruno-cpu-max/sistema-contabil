<?php
/**
 * @var array $filtros busca, cliente_id, situacao, de, ate
 * @var array $documentos
 * @var array $resumo aguardando, atrasados, recebidos, conferidos
 * @var array $porCliente
 * @var array $clientes
 * @var \App\Core\Paginacao $paginacao
 */
$filtrando = $filtros['busca'] !== '' || $filtros['cliente_id'] || $filtros['situacao'] !== '' || $filtros['de'] || $filtros['ate'];
$atalhos = [
    ['aguardando', 'Aguardando envio', $resumo['aguardando'], 'ambar', 'relogio'],
    ['atrasado', 'Prazo vencido', $resumo['atrasados'], 'vermelho', 'alerta'],
    ['recebido', 'Recebidos (conferir)', $resumo['recebidos'], 'azul', 'baixar'],
    ['conferido', 'Conferidos', $resumo['conferidos'], 'verde', 'check-duplo'],
];
?>
<div class="cabecalho">
    <div>
        <h1>Documentos</h1>
        <p class="cabecalho__sub">Solicite documentos aos clientes, anexe os arquivos recebidos e acompanhe a conferência.</p>
    </div>
    <div class="cabecalho__acoes">
        <a class="botao" href="<?= e(url('documentos/form')) ?>"><?= icone('mais') ?> Solicitar documento</a>
    </div>
</div>

<nav class="atalhos" aria-label="Filtrar por situação">
    <?php foreach ($atalhos as [$valor, $rotulo, $quantidade, $cor, $iconeAtalho]): ?>
        <a class="atalho atalho--<?= e($cor) ?>" href="<?= e(url('documentos', ['situacao' => $valor])) ?>"<?= $filtros['situacao'] === $valor ? ' aria-current="true"' : '' ?>>
            <span class="atalho__icone"><?= icone($iconeAtalho) ?></span>
            <span class="atalho__texto"><strong><?= (int) $quantidade ?></strong> <?= e($rotulo) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($porCliente !== []): ?>
    <section class="cartao por-cliente" aria-labelledby="titulo-por-cliente">
        <div class="por-cliente__topo">
            <h2 id="titulo-por-cliente">Documentos por cliente</h2>
            <span class="texto-suave">Clique em um cliente para ver só os documentos dele.</span>
            <ul class="legenda legenda--compacta">
                <li><span class="legenda__cor legenda__cor--ambar"></span> Aguardando</li>
                <li><span class="legenda__cor legenda__cor--azul"></span> Recebidos</li>
                <li><span class="legenda__cor legenda__cor--verde"></span> Conferidos</li>
            </ul>
        </div>
        <ul class="por-cliente__lista">
            <?php foreach ($porCliente as $pc): ?>
                <li>
                    <a href="<?= e(url('documentos', ['cliente_id' => $pc['id']])) ?>"<?= (int) $filtros['cliente_id'] === (int) $pc['id'] ? ' aria-current="true"' : '' ?>>
                        <span><?= e(nome_cliente($pc, '')) ?></span>
                        <span class="por-cliente__numeros">
                            <?php if ($pc['aguardando'] > 0): ?><span class="ponto ponto--ambar" title="Aguardando envio"><?= (int) $pc['aguardando'] ?></span><?php endif; ?>
                            <?php if ($pc['recebidos'] > 0): ?><span class="ponto ponto--azul" title="Recebidos"><?= (int) $pc['recebidos'] ?></span><?php endif; ?>
                            <?php if ($pc['conferidos'] > 0): ?><span class="ponto ponto--verde" title="Conferidos"><?= (int) $pc['conferidos'] ?></span><?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<section class="cartao">
        <form class="filtros filtros--grade" method="get" action="<?= e($_SERVER['SCRIPT_NAME']) ?>" role="search">
            <input type="hidden" name="r" value="documentos">
            <div class="campo campo--busca">
                <label for="busca">Pesquisar</label>
                <div class="filtros__busca">
                    <?= icone('busca') ?>
                    <input id="busca" name="busca" type="search" value="<?= e($filtros['busca']) ?>" placeholder="Documento ou nome do cliente" maxlength="100">
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
                    <?php foreach (\App\Models\Documento::SITUACOES as $valor => [$rotulo]): ?>
                        <option value="<?= e($valor) ?>"<?= selecionado($filtros['situacao'], $valor) ?>><?= e($rotulo) ?></option>
                    <?php endforeach; ?>
                    <option value="atrasado"<?= selecionado($filtros['situacao'], 'atrasado') ?>>Prazo vencido</option>
                </select>
            </div>
            <div class="campo">
                <label for="de">Prazo de</label>
                <input id="de" name="de" type="date" value="<?= e($filtros['de'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="ate">até</label>
                <input id="ate" name="ate" type="date" value="<?= e($filtros['ate'] ?? '') ?>">
            </div>
            <div class="filtros__botoes">
                <button type="submit" class="botao"><?= icone('filtro') ?> Filtrar</button>
                <?php if ($filtrando): ?>
                    <a class="botao botao--secundario" href="<?= e(url('documentos')) ?>">Limpar</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($documentos === []): ?>
            <?php $mensagemVazio = $filtrando ? 'Nenhum documento encontrado com esses filtros.' : 'Nenhum documento solicitado ainda.'; ?>
            <?php require RAIZ . '/app/Views/partials/vazio.php'; ?>
        <?php else: ?>
            <div class="tabela-rolagem">
                <table class="tabela">
                    <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th>Prazo</th>
                        <th>Situação</th>
                        <th>Arquivo</th>
                        <th class="acoes-titulo">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($documentos as $d): ?>
                        <tr<?= $d['atrasado'] ? ' class="linha--alerta"' : '' ?>>
                            <td><a class="link-forte" href="<?= e(url('clientes/ver', ['id' => $d['cliente_id']])) ?>"><?= e(nome_cliente($d)) ?></a></td>
                            <td>
                                <?= e($d['titulo']) ?>
                                <?php if ($d['descricao']): ?><small class="sublinha"><?= e(mb_strimwidth($d['descricao'], 0, 60, '…')) ?></small><?php endif; ?>
                            </td>
                            <td class="nao-quebra">
                                <?= e(data_br($d['data_limite'])) ?>
                                <?php if ($d['situacao'] === 'aguardando'): ?>
                                    <small class="prazo prazo--<?= $d['atrasado'] ? 'atrasado' : 'normal' ?>"><?= e(prazo_relativo($d['data_limite'])) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= selo_documento($d['situacao'], (bool) $d['atrasado']) ?></td>
                            <td>
                                <?php if ($d['arquivo_salvo']): ?>
                                    <a class="arquivo nao-quebra" href="<?= e(url('documentos/arquivo', ['id' => $d['id']])) ?>" target="_blank" rel="noopener" title="<?= e($d['arquivo_nome']) ?>">
                                        <?= icone('anexo') ?> <?= e(mb_strimwidth($d['arquivo_nome'], 0, 24, '…')) ?>
                                    </a>
                                    <small class="sublinha"><?= e(tamanho_arquivo((int) $d['arquivo_tamanho'])) ?> · <?= e(data_hora_br($d['enviado_em'])) ?></small>
                                <?php else: ?>
                                    <span class="texto-suave">Não enviado</span>
                                <?php endif; ?>
                            </td>
                            <td class="acoes">
                                <?php if ($d['situacao'] === 'recebido'): ?>
                                    <form method="post" action="<?= e(url('documentos/situacao')) ?>">
                                        <?= csrf_campo() ?><?= voltar_campo() ?>
                                        <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                        <input type="hidden" name="situacao" value="conferido">
                                        <button type="submit" class="botao-icone botao-icone--sucesso" title="Marcar como conferido">
                                            <?= icone('check-duplo') ?><span class="oculto">Marcar <?= e($d['titulo']) ?> de <?= e(nome_cliente($d)) ?> como conferido</span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a class="botao-icone" href="<?= e(url('documentos/form', ['id' => $d['id']])) ?>" title="<?= $d['arquivo_salvo'] ? 'Editar' : 'Anexar arquivo / editar' ?>">
                                    <?= icone($d['arquivo_salvo'] ? 'editar' : 'upload') ?><span class="oculto">Editar <?= e($d['titulo']) ?> de <?= e(nome_cliente($d)) ?></span>
                                </a>
                                <form method="post" action="<?= e(url('documentos/excluir')) ?>"
                                      data-confirmar="Excluir a solicitação &quot;<?= e($d['titulo']) ?>&quot; de <?= e(nome_cliente($d)) ?><?= $d['arquivo_salvo'] ? ' e o arquivo anexado' : '' ?>?">
                                    <?= csrf_campo() ?><?= voltar_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                    <button type="submit" class="botao-icone botao-icone--perigo" title="Excluir">
                                        <?= icone('excluir') ?><span class="oculto">Excluir <?= e($d['titulo']) ?> de <?= e(nome_cliente($d)) ?></span>
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
        $rotaPaginacao = 'documentos';
        $parametrosPaginacao = [
            'busca' => $filtros['busca'], 'cliente_id' => $filtros['cliente_id'], 'situacao' => $filtros['situacao'],
            'de' => $filtros['de'], 'ate' => $filtros['ate'],
        ];
        require RAIZ . '/app/Views/partials/paginacao.php';
        ?>
    </section>

