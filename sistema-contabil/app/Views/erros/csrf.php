<div class="erro-pagina">
    <p class="erro-pagina__codigo"><?= icone('cadeado', 'icone icone--grande') ?></p>
    <h1>Sessão expirada</h1>
    <p>Por segurança, o formulário não foi enviado (o token de proteção expirou ou é inválido).
        Volte, recarregue a página e tente de novo.</p>
    <a class="botao" href="<?= e(url('dashboard')) ?>"><?= icone('voltar') ?> Voltar ao início</a>
</div>
