<div class="erro-pagina">
    <p class="erro-pagina__codigo">500</p>
    <h1>Algo deu errado</h1>
    <p>Não foi possível concluir a operação. Tente de novo; se continuar, verifique a conexão com o banco de dados
        (o detalhe técnico foi registrado em storage/logs/erros.log).</p>
    <a class="botao" href="<?= e(url('dashboard')) ?>"><?= icone('voltar') ?> Voltar ao painel</a>
</div>
