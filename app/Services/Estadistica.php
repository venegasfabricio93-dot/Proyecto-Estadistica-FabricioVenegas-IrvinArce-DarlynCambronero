<?php

namespace App\Services;

class Estadistica
{
    public static function media(array $v): ?float
    {
        return $v ? array_sum($v) / count($v) : null;
    }

    public static function mediana(array $v): ?float
    {
        if (!$v) return null;
        sort($v);
        $n = count($v);
        $m = intdiv($n, 2);
        return $n % 2 ? $v[$m] : ($v[$m - 1] + $v[$m]) / 2;
    }

    /** Devuelve todas las modas; arreglo vacío si no hay (amodal). */
    public static function moda(array $v): array
    {
        if (!$v) return [];
        $f = array_count_values(array_map('strval', $v));
        $max = max($f);
        if ($max === 1) return [];
        $modas = array_keys(array_filter($f, fn ($c) => $c === $max));
        $modas = array_map(fn ($k) => $k + 0, $modas);
        sort($modas);
        return $modas;
    }

    public static function rango(array $v): ?float
    {
        return $v ? max($v) - min($v) : null;
    }

    /** Poblacional por defecto (divide entre n); muestral divide entre n-1. */
    public static function varianza(array $v, bool $muestral = false): ?float
    {
        $n = count($v);
        if ($n === 0 || ($muestral && $n < 2)) return null;
        $m = self::media($v);
        $suma = array_sum(array_map(fn ($x) => ($x - $m) ** 2, $v));
        return $suma / ($muestral ? $n - 1 : $n);
    }

    public static function desviacion(array $v, bool $muestral = false): ?float
    {
        $va = self::varianza($v, $muestral);
        return $va === null ? null : sqrt($va);
    }
}
