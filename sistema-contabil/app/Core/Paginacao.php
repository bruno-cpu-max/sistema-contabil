<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Cálculo da paginação das listagens (qual fatia buscar no banco e quais links mostrar).
 */
final class Paginacao
{
    public int $pagina;
    public int $totalPaginas;

    public function __construct(public int $total, int $pagina, public int $porPagina)
    {
        $this->totalPaginas = max(1, (int) ceil($total / $porPagina));
        // Página além da última (ex.: depois de excluir o último item dela) mostra a última.
        $this->pagina = min(max(1, $pagina), $this->totalPaginas);
    }

    public function offset(): int
    {
        return ($this->pagina - 1) * $this->porPagina;
    }

    /** "Mostrando 11–20 de 37" */
    public function resumo(): string
    {
        if ($this->total === 0) {
            return 'Nenhum registro';
        }
        $inicio = $this->offset() + 1;
        $fim = min($this->offset() + $this->porPagina, $this->total);
        return "Mostrando {$inicio}–{$fim} de {$this->total}";
    }

    /** Números de página a exibir, com null onde entram reticências: [1, null, 4, 5, 6, null, 12]. */
    public function paginasVisiveis(): array
    {
        $paginas = [];
        $anterior = 0;
        for ($p = 1; $p <= $this->totalPaginas; $p++) {
            if ($p === 1 || $p === $this->totalPaginas || abs($p - $this->pagina) <= 1) {
                if ($anterior && $p - $anterior > 1) {
                    $paginas[] = null;
                }
                $paginas[] = $p;
                $anterior = $p;
            }
        }
        return $paginas;
    }
}
