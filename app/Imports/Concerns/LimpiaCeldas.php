<?php

namespace App\Imports\Concerns;

/**
 * Excel entrega los números como int/float (un documento «1234567890» llega como número, no como texto).
 * Antes de validar y guardar, cada celda se convierte a texto limpio.
 */
trait LimpiaCeldas
{
    protected function texto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (is_float($valor)) {
            // Sin notación científica ni «.0» sobrante
            $valor = floor($valor) == $valor ? sprintf('%.0f', $valor) : (string) $valor;
        }

        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }
}
