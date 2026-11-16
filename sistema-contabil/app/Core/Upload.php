<?php

declare(strict_types=1);

namespace App\Core;

use finfo;
use RuntimeException;

/**
 * Recebimento seguro de arquivos.
 *
 * - Confere extensão (lista fechada), tamanho e o TIPO REAL pelo conteúdo do arquivo: renomear
 *   um "virus.exe" para "nota.pdf" não engana a verificação.
 * - Salva com um nome aleatório, fora da pasta pública: o arquivo nunca é executado pelo servidor
 *   e só é entregue pelo sistema a quem está logado.
 */
final class Upload
{
    /**
     * Valida o arquivo enviado no campo $_FILES[...].
     *
     * @return array{0: ?array, 1: ?string} [dados do arquivo validado, mensagem de erro]
     */
    public static function validar(?array $arquivo): array
    {
        // Formulário sem campo de arquivo: nada foi enviado (não é erro).
        if ($arquivo === null) {
            return [null, null];
        }
        // Vários arquivos no mesmo campo ou dados malformados.
        if (!isset($arquivo['error']) || is_array($arquivo['error'])) {
            return [null, 'Envio de arquivo inválido.'];
        }

        $maximo = config('uploads.tamanho_maximo');
        $maximoTexto = tamanho_arquivo($maximo);

        switch ($arquivo['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return [null, null];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return [null, "O arquivo é grande demais. O limite é {$maximoTexto}."];
            case UPLOAD_ERR_PARTIAL:
                return [null, 'O envio foi interrompido. Tente de novo.'];
            default:
                return [null, 'O servidor não conseguiu receber o arquivo. Tente de novo.'];
        }

        if (!is_uploaded_file($arquivo['tmp_name'])) {
            return [null, 'Envio de arquivo inválido.'];
        }
        if ($arquivo['size'] <= 0) {
            return [null, 'O arquivo está vazio.'];
        }
        if ($arquivo['size'] > $maximo) {
            return [null, "O arquivo é grande demais. O limite é {$maximoTexto}."];
        }

        $tipos = config('uploads.tipos');
        $extensao = strtolower(pathinfo((string) $arquivo['name'], PATHINFO_EXTENSION));
        if (!isset($tipos[$extensao])) {
            return [null, 'Tipo de arquivo não permitido. Envie ' . self::extensoesPermitidas() . '.'];
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']) ?: '';
        if (!in_array($mime, $tipos[$extensao], true)) {
            return [null, 'O conteúdo do arquivo não corresponde à extensão .' . $extensao . '. Envie um arquivo válido.'];
        }

        return [[
            'tmp_name' => $arquivo['tmp_name'],
            'nome_original' => self::nomeSeguro((string) $arquivo['name']),
            'extensao' => $extensao,
            'mime' => $mime,
            'tamanho' => (int) $arquivo['size'],
        ], null];
    }

    /** Move o arquivo validado para a pasta de uploads; devolve o nome com que foi salvo. */
    public static function salvar(array $arquivo): string
    {
        $pasta = config('uploads.pasta');
        if (!is_dir($pasta) && !mkdir($pasta, 0750, true) && !is_dir($pasta)) {
            throw new RuntimeException('Não foi possível criar a pasta de uploads.');
        }

        $nome = bin2hex(random_bytes(16)) . '.' . $arquivo['extensao'];
        if (!move_uploaded_file($arquivo['tmp_name'], $pasta . '/' . $nome)) {
            throw new RuntimeException('Não foi possível salvar o arquivo enviado.');
        }
        return $nome;
    }

    public static function caminho(string $nomeSalvo): ?string
    {
        // O nome salvo é sempre gerado pelo sistema; a conferência impede "../" mesmo assim.
        if (!preg_match('/^[a-zA-Z0-9_-]+\.[a-z0-9]{2,5}$/', $nomeSalvo)) {
            return null;
        }
        $caminho = config('uploads.pasta') . '/' . $nomeSalvo;
        return is_file($caminho) ? $caminho : null;
    }

    public static function remover(?string $nomeSalvo): void
    {
        if ($nomeSalvo !== null && ($caminho = self::caminho($nomeSalvo)) !== null) {
            unlink($caminho);
        }
    }

    public static function extensoesPermitidas(): string
    {
        $lista = array_map('strtoupper', array_unique(array_keys(config('uploads.tipos'))));
        $lista = array_diff($lista, ['JPEG']);
        $ultimo = array_pop($lista);
        return implode(', ', $lista) . ' ou ' . $ultimo;
    }

    /** Nome original só para exibir e baixar: sem caminhos nem caracteres de controle. */
    private static function nomeSeguro(string $nome): string
    {
        $nome = basename(str_replace('\\', '/', $nome));
        $nome = preg_replace('/[\x00-\x1F\x7F"]/u', '', $nome) ?? 'arquivo';
        return mb_substr($nome, 0, 200) ?: 'arquivo';
    }
}
