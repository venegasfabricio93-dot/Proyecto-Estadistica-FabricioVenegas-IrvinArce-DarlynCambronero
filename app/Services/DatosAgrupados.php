<?php

namespace App\Services;

class DatosAgrupados
{
    /**
     * Agrupa valores enteros en clases, igual que en clase:
     * amplitud de clase = rango / k (redondeada hacia arriba),
     * límites indicados = [li, li + amplitud - 1], límites reales = ±0.5.
     */
    public static function calcular(array $valores, int $kPedido = 6): ?array
    {
        $valores = array_values(array_map('intval', $valores));
        sort($valores);
        $n = count($valores);
        if ($n < 2) return null;

        $min = $valores[0];
        $max = $valores[$n - 1];
        $rango = $max - $min;
        if ($rango === 0) return null;

        $amp = max(1, (int) ceil($rango / $kPedido));
        $k = (int) ceil($rango / $amp); // nº de clases efectivo (sin clases vacías al final)

        $clases = [];
        $acumMenos = 0;
        $sumXF = 0.0;
        $sumX2F = 0.0;

        for ($i = 0; $i < $k; $i++) {
            $li = $min + $i * $amp;
            $ls = $li + $amp - 1;
            if ($i === $k - 1) {
                $ls = max($ls, $max); // la última clase llega hasta el máximo
            }

            $fi = count(array_filter($valores, fn ($v) => $v >= $li && $v <= $ls));
            $xi = ($li + $ls) / 2;
            $masDe = $n - $acumMenos;
            $acumMenos += $fi;

            $clases[] = [
                'li' => $li, 'ls' => $ls,
                'inter_sup' => $li + $amp,
                'real_inf' => $li - 0.5, 'real_sup' => $ls + 0.5,
                'xi' => $xi, 'fi' => $fi,
                'fr' => $fi * 100 / $n,
                'menos_abs' => $acumMenos, 'menos_rel' => $acumMenos * 100 / $n,
                'mas_abs' => $masDe, 'mas_rel' => $masDe * 100 / $n,
                'xi2' => $xi ** 2,
                'xifi' => $xi * $fi,
                'x2fi' => ($xi ** 2) * $fi,
            ];

            $sumXF += $xi * $fi;
            $sumX2F += ($xi ** 2) * $fi;
        }

        $media = $sumXF / $n;
        $varianza = ($sumX2F - ($sumXF ** 2) / $n) / ($n - 1); // muestral, como en clase
        $desv = sqrt($varianza);
        $cv = $media != 0 ? $desv / $media * 100 : null;

        return [
            'valores' => $valores, 'n' => $n,
            'min' => $min, 'max' => $max, 'rango' => $rango,
            'k_pedido' => $kPedido, 'k' => $k, 'amp' => $amp,
            'clases' => $clases,
            'sum_xf' => $sumXF, 'sum_x2f' => $sumX2F,
            'media' => $media, 'varianza' => $varianza, 'desv' => $desv, 'cv' => $cv,
        ];
    }
}
