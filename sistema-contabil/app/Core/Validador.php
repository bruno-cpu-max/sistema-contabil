<?php

declare(strict_types=1);

namespace App\Core;

use DateTime;

/**
 * Validação dos dados no servidor, com uma mensagem por campo.
 * (A validação do navegador ajuda quem digita, mas pode ser contornada; esta não.)
 */
final class Validador
{
    /** @var array<string, string> */
    private array $erros = [];

    public function erro(string $campo, string $mensagem): void
    {
        // Só a primeira mensagem de cada campo: a mais importante.
        $this->erros[$campo] ??= $mensagem;
    }

    public function valido(): bool
    {
        return $this->erros === [];
    }

    public function erros(): array
    {
        return $this->erros;
    }

    public function obrigatorio(string $campo, string $valor, string $mensagem): void
    {
        if ($valor === '') {
            $this->erro($campo, $mensagem);
        }
    }

    public function tamanhoMaximo(string $campo, string $valor, int $maximo): void
    {
        if (mb_strlen($valor) > $maximo) {
            $this->erro($campo, "Use no máximo {$maximo} caracteres.");
        }
    }

    public function email(string $campo, string $valor): void
    {
        if ($valor !== '' && filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
            $this->erro($campo, 'Informe um e-mail válido, como nome@empresa.com.br.');
        }
    }

    /** Data no formato do campo de data do navegador (AAAA-MM-DD); devolve a data ou null. */
    public function data(string $campo, string $valor, string $mensagem): ?string
    {
        $dt = DateTime::createFromFormat('!Y-m-d', $valor);
        if ($valor === '' || $dt === false || $dt->format('Y-m-d') !== $valor) {
            $this->erro($campo, $mensagem);
            return null;
        }
        return $valor;
    }

    /** Opção de uma lista fechada (ex.: situação). */
    public function opcao(string $campo, string $valor, array $permitidas, string $mensagem): void
    {
        if (!in_array($valor, $permitidas, true)) {
            $this->erro($campo, $mensagem);
        }
    }

    /**
     * Valor em reais: aceita "1.234,56", "1234,56" ou "1234.56". Devolve "1234.56" (texto, para o
     * DECIMAL do banco não ter erro de arredondamento) ou null se inválido.
     */
    public static function valorMonetario(string $texto): ?string
    {
        $texto = str_replace(['R$', ' '], '', $texto);
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d{1,2})?$/', $texto) || preg_match('/^\d+(,\d{1,2})?$/', $texto)) {
            $texto = str_replace(['.', ','], ['', '.'], $texto);
        } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $texto)) {
            return null;
        }
        return number_format((float) $texto, 2, '.', '');
    }

    public static function somenteNumeros(string $texto): string
    {
        return preg_replace('/\D/', '', $texto) ?? '';
    }

    public static function cpfValido(string $cpf): bool
    {
        if (!preg_match('/^\d{11}$/', $cpf) || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $digito = ((10 * $soma) % 11) % 10;
            if ((int) $cpf[$t] !== $digito) {
                return false;
            }
        }
        return true;
    }

    public static function cnpjValido(string $cnpj): bool
    {
        if (!preg_match('/^\d{14}$/', $cnpj) || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }
        $pesos = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        foreach ([12, 13] as $posicao) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cnpj[$i] * $pesos[$i + (13 - $posicao)];
            }
            $resto = $soma % 11;
            $digito = $resto < 2 ? 0 : 11 - $resto;
            if ((int) $cnpj[$posicao] !== $digito) {
                return false;
            }
        }
        return true;
    }
}
