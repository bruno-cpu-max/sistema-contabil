<?php
/**
 * Painel inicial.
 * @var int $totalClientes
 * @var array $resumo obrigações (total, pendentes, vencidas, concluidas, proximos_7_dias, valor_vencido)
 * @var array $resumoDocumentos
 * @var array $atencao obrigações vencidas ou que vencem em 7 dias
 * @var array $documentosAguardando
 */
$indicadores = [
    ['Clientes cadastrados', $totalClientes, 'clientes', 'azul', url('clientes'), 'Empresas e pessoas físicas'],
    ['Obrigações fiscais', (int) $resumo['total'], 'lista', 'marinho', url('obrigacoes'), 'Total cadastrado'],
    ['Pendentes', (int) $resumo['pendentes'], 'relogio', 'ambar', url('obrigacoes', ['situacao' => 'pendente']), 'Dentro do prazo'],
    ['Vencidas', (int) $resumo['vencidas'], 'alerta', 'vermelho', url('obrigacoes', ['situacao' => 'vencida']),
        $resumo['valor_vencido'] > 0 ? dinheiro($resumo['valor_vencido']) . ' em atraso' : 'Nenhum valor em atraso'],
    ['Concluídas', (int) $resumo['concluidas'], 'check-duplo', 'verde', url('obrigacoes', ['situacao' => 'concluida']), 'Entregues ou pagas'],
    ['Vencem em 7 dias', (int) $resumo['proximos_7_dias'], 'calendario', 'ciano',
        url('obrigacoes', ['situacao' => 'pendente', 'de' => hoje(), 'ate' => date('Y-m-d', strtotime('+7 days'))]), 'Próximos vencimentos'],
];
$totalDocumentos = max(1, $resumoDocumentos['aguardando'] + $resumoDocumentos['recebidos'] + $resumoDocumentos['conferidos']);
?>
<div class="cabecalho">
    <div>
        <h1>Painel do escritório</h1>
        <p class="cabecalho__sub">Situação de hoje, <?= e(data_br(hoje())) ?>. Os números são calculados a partir dos registros cadastrados.</p>
    </div>
    <div class="cabecalho__acoes">
        <a class="botao botao--secundario" href="<?= e(url('documentos/form')) ?>"><?= icone('documentos') ?> Solicitar documento</a>
        <a class="botao" href="<?= e(url('obrigacoes/form')) ?>"><?= icone('mais') ?> Nova obrigação</a>
    </div>
</div>

<section class="indicadores" aria-label="Indicadores">
    <?php foreach ($indicadores as [$rotulo, $valor, $iconeCard, $cor, $link, $detalhe]): ?>
        <a class="indicador indicador--<?= e($cor) ?>" href="<?= e($link) ?>">
            <span class="indicador__icone"><?= icone($iconeCard) ?></span>
            <span class="indicador__rotulo"><?= e($rotulo) ?></span>
            <strong class="indicador__valor"><?= e(number_format($valor, 0, ',', '.')) ?></strong>
            <span class="indicador__detalhe"><?= e($detalhe) ?></span>
        </a>
    <?php endforeach; ?>
</section>

<div class="grade-painel">
    <section class="cartao">
        <div class="cartao__cabecalho">
            <div>
                <h2>Obrigações que precisam de atenção</h2>
                <p class="texto-suave">Vencidas e as que vencem nos próximos 7 dias.</p>
            </div>
            <a class="link-seta" href="<?= e(url('obrigacoes')) ?>">Ver todas</a>
        </div>

        <?php if ($atencao === []): ?>
            <?php $mensagemVazio = 'Nenhuma obrigação vencida ou vencendo nos próximos 7 dias.'; require RAIZ . '/app/Views/partials/vazio.php'; ?>
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
                        <th><span class="oculto">Ações</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($atencao as $o): ?>
                        <tr>
                            <td><a class="link-forte" href="<?= e(url('clientes/ver', ['id' => $o['cliente_id']])) ?>"><?= e(nome_cliente($o)) ?></a></td>
                            <td><?= e($o['nome']) ?></td>
                            <td>
                                <?= e(data_br($o['vencimento'])) ?>
                                <small class="prazo prazo--<?= $o['situacao'] === 'vencida' ? 'atrasado' : 'normal' ?>"><?= e(prazo_relativo($o['vencimento'])) ?></small>
                            </td>
                            <td class="numero"><?= e(dinheiro($o['valor'])) ?></td>
                            <td><?= selo_obrigacao($o['situacao']) ?></td>
                            <td class="acoes">
                                <form method="post" action="<?= e(url('obrigacoes/concluir')) ?>">
                                    <?= csrf_campo() ?><?= voltar_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                    <button type="submit" class="botao-icone botao-icone--sucesso" title="Marcar como concluída">
                                        <?= icone('check') ?><span class="oculto">Concluir <?= e($o['nome']) ?> de <?= e(nome_cliente($o)) ?></span>
                                    </button>
                                </form>
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
            <div>
                <h2>Documentos dos clientes</h2>
                <p class="texto-suave">Pedidos aguardando envio.</p>
            </div>
            <a class="link-seta" href="<?= e(url('documentos')) ?>">Ver todos</a>
        </div>

        <div class="barra-progresso" role="img"
             aria-label="<?= e("{$resumoDocumentos['conferidos']} conferidos, {$resumoDocumentos['recebidos']} recebidos e {$resumoDocumentos['aguardando']} aguardando envio") ?>">
            <span class="barra-progresso__parte barra-progresso__parte--verde" data-largura="<?= round($resumoDocumentos['conferidos'] / $totalDocumentos * 100) ?>"></span>
            <span class="barra-progresso__parte barra-progresso__parte--azul" data-largura="<?= round($resumoDocumentos['recebidos'] / $totalDocumentos * 100) ?>"></span>
            <span class="barra-progresso__parte barra-progresso__parte--ambar" data-largura="<?= round($resumoDocumentos['aguardando'] / $totalDocumentos * 100) ?>"></span>
        </div>
        <ul class="legenda">
            <li><span class="legenda__cor legenda__cor--verde"></span> Conferidos <strong><?= $resumoDocumentos['conferidos'] ?></strong></li>
            <li><span class="legenda__cor legenda__cor--azul"></span> Recebidos <strong><?= $resumoDocumentos['recebidos'] ?></strong></li>
            <li><span class="legenda__cor legenda__cor--ambar"></span> Aguardando <strong><?= $resumoDocumentos['aguardando'] ?></strong></li>
            <?php if ($resumoDocumentos['atrasados'] > 0): ?>
                <li class="legenda__alerta"><?= icone('alerta') ?> <a href="<?= e(url('documentos', ['situacao' => 'atrasado'])) ?>"><?= $resumoDocumentos['atrasados'] ?> com prazo vencido</a></li>
            <?php endif; ?>
        </ul>

        <?php if ($documentosAguardando === []): ?>
            <?php $mensagemVazio = 'Nenhum documento aguardando envio.'; require RAIZ . '/app/Views/partials/vazio.php'; ?>
        <?php else: ?>
            <ul class="lista-documentos">
                <?php foreach ($documentosAguardando as $d): ?>
                    <li>
                        <span class="lista-documentos__icone<?= $d['atrasado'] ? ' lista-documentos__icone--atrasado' : '' ?>"><?= icone('documentos') ?></span>
                        <span class="lista-documentos__texto">
                            <a class="link-forte" href="<?= e(url('documentos/form', ['id' => $d['id']])) ?>"><?= e($d['titulo']) ?></a>
                            <small><?= e(nome_cliente($d)) ?> · prazo <?= e(data_br($d['data_limite'])) ?></small>
                        </span>
                        <?= selo_documento('aguardando', (bool) $d['atrasado']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
