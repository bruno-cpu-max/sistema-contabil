<?php
/**
 * Navegação entre páginas de uma listagem, mantendo os filtros.
 * @var \App\Core\Paginacao $paginacao
 * @var string $rotaPaginacao
 * @var array $parametrosPaginacao filtros ativos
 */
?>
<nav class="paginacao" aria-label="Páginas">
    <span class="paginacao__resumo"><?= e($paginacao->resumo()) ?></span>
    <?php if ($paginacao->totalPaginas > 1): ?>
        <ul class="paginacao__lista">
            <li>
                <?php if ($paginacao->pagina > 1): ?>
                    <a href="<?= e(url($rotaPaginacao, $parametrosPaginacao + ['pagina' => $paginacao->pagina - 1])) ?>" aria-label="Página anterior">‹</a>
                <?php else: ?>
                    <span class="desativado" aria-hidden="true">‹</span>
                <?php endif; ?>
            </li>
            <?php foreach ($paginacao->paginasVisiveis() as $numero): ?>
                <li>
                    <?php if ($numero === null): ?>
                        <span class="reticencias">…</span>
                    <?php elseif ($numero === $paginacao->pagina): ?>
                        <span class="atual" aria-current="page"><?= $numero ?></span>
                    <?php else: ?>
                        <a href="<?= e(url($rotaPaginacao, $parametrosPaginacao + ['pagina' => $numero])) ?>"><?= $numero ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <li>
                <?php if ($paginacao->pagina < $paginacao->totalPaginas): ?>
                    <a href="<?= e(url($rotaPaginacao, $parametrosPaginacao + ['pagina' => $paginacao->pagina + 1])) ?>" aria-label="Próxima página">›</a>
                <?php else: ?>
                    <span class="desativado" aria-hidden="true">›</span>
                <?php endif; ?>
            </li>
        </ul>
    <?php endif; ?>
</nav>
