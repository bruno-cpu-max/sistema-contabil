<?php
/** Mensagens de sucesso/erro vindas da ação anterior. @var array $flash */
?>
<?php foreach ($flash as $mensagem): ?>
    <div class="alerta alerta--<?= e($mensagem['tipo']) ?>" role="<?= $mensagem['tipo'] === 'erro' ? 'alert' : 'status' ?>">
        <?= icone($mensagem['tipo'] === 'erro' ? 'alerta' : 'check') ?>
        <span><?= e($mensagem['mensagem']) ?></span>
        <button type="button" class="alerta__fechar" data-fechar-alerta aria-label="Fechar aviso"><?= icone('fechar') ?></button>
    </div>
<?php endforeach; ?>
