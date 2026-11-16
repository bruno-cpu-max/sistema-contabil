<?php

/**
 * Configuração do sistema.
 *
 * Os valores padrão servem para o XAMPP (MySQL/MariaDB local, usuário root sem senha).
 * Em outro ambiente, defina as variáveis de ambiente (DB_HOST, DB_NAME...) ou altere aqui.
 */

declare(strict_types=1);

$env = static function (string $nome, string $padrao): string {
    $valor = getenv($nome);
    return $valor === false ? $padrao : $valor;
};

return [
    'app' => [
        'nome' => 'SisContábil',
        'fuso_horario' => 'America/Sao_Paulo',
        // Em demonstração deixe false: os erros vão para storage/logs e a tela mostra uma mensagem amigável.
        'debug' => $env('APP_DEBUG', '0') === '1',
        // Sessão expira após este tempo sem uso (em segundos).
        'sessao_inatividade' => 2 * 60 * 60,
        'itens_por_pagina' => 10,
    ],

    'db' => [
        'host' => $env('DB_HOST', '127.0.0.1'),
        'porta' => $env('DB_PORT', '3306'),
        'nome' => $env('DB_NAME', 'contabil'),
        'usuario' => $env('DB_USER', 'root'),
        'senha' => $env('DB_PASS', ''),
    ],

    'uploads' => [
        // Fora da pasta public: os arquivos só são entregues pelo sistema, a quem está logado.
        'pasta' => RAIZ . '/storage/uploads',
        'tamanho_maximo' => 5 * 1024 * 1024, // 5 MB
        // Extensão permitida => tipos (MIME) aceitos para ela, conferidos pelo conteúdo do arquivo.
        'tipos' => [
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'doc' => ['application/msword', 'application/vnd.ms-office', 'application/CDFV2'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'xls' => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/CDFV2'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        ],
    ],
];
