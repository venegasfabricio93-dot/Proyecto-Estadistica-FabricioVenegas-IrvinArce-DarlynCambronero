<?php

namespace App\Services;

use App\Models\Result;

class DriverStatsService
{
    public const CATEGORIAS = [
        'Podio (1-3)',
        'Zona de puntos (4-10)',
        'Fuera de puntos (11+)',
        'No terminó',
    ];

    public function temporada(int $year, int $minCarreras = 5): array
    {
        $filas = Result::query()
            ->join('races', 'races.id', '=', 'results.race_id')
            ->join('seasons', 'seasons.id', '=', 'races.season_id')
            ->join('drivers', 'drivers.id', '=', 'results.driver_id')
            ->where('seasons.year', $year)
            ->orderBy('races.round')
            ->get([
                'drivers.id as driver_id', 'drivers.name',
                'races.round', 'results.position',
                'results.points', 'results.sprint_points',
            ]);

        $rondas = $filas->pluck('round')->unique()->sort()->values()->all();

        $pilotos = $filas->groupBy('driver_id')
            ->filter(fn ($f) => $f->count() >= $minCarreras)
            ->map(fn ($f) => $this->estadisticasPiloto($f, $rondas))
            ->sortByDesc('puntos_totales')
            ->values();

        return [
            'rondas' => $rondas,
            'pilotos' => $pilotos->all(),
            'grupos' => [
                'con' => $this->resumenGrupo($pilotos->where('victorias', '>', 0)),
                'sin' => $this->resumenGrupo($pilotos->where('victorias', '=', 0)),
            ],
        ];
    }

    private function estadisticasPiloto($f, array $rondas): array
    {
        $posiciones = $f->pluck('position')
            ->filter(fn ($p) => $p !== null)
            ->map(fn ($p) => (int) $p)
            ->values()->all();

        // Puntos acumulados ronda por ronda (se arrastra el valor si no corrió)
        $porRonda = $f->keyBy('round');
        $acum = 0;
        $evolucion = [];
        foreach ($rondas as $ronda) {
            if ($r = $porRonda->get($ronda)) {
                $acum += (float) $r->points + (float) $r->sprint_points;
            }
            $evolucion[] = $acum;
        }

        return [
            'name' => $f->first()->name,
            'carreras' => $f->count(),
            'terminadas' => count($posiciones),
            'victorias' => $f->filter(fn ($r) => $r->position !== null && (int) $r->position === 1)->count(),
            'puntos_totales' => $acum,
            'pos_media' => $this->r(Estadistica::media($posiciones)),
            'pos_mediana' => $this->r(Estadistica::mediana($posiciones)),
            'pos_moda' => Estadistica::moda($posiciones),
            'pos_rango' => $this->r(Estadistica::rango($posiciones)),
            'pos_varianza' => $this->r(Estadistica::varianza($posiciones)),
            'pos_desv' => $this->r(Estadistica::desviacion($posiciones)),
            'frec_pos' => array_count_values($posiciones),
            'categorias' => $f->map(fn ($r) => $this->categoria(
                $r->position !== null ? (int) $r->position : null
            ))->countBy()->all(),
            'evolucion' => $evolucion,
        ];
    }

    private function resumenGrupo($lista): array
    {
        $frec = array_fill_keys(self::CATEGORIAS, 0);
        $pos = [];

        foreach ($lista as $p) {
            foreach ($p['categorias'] as $cat => $n) {
                $frec[$cat] += $n;
            }
            foreach ($p['frec_pos'] as $k => $n) {
                $pos[$k] = ($pos[$k] ?? 0) + $n;
            }
        }
        $total = array_sum($frec);

        $col = fn ($campo) => $lista->pluck($campo)
            ->filter(fn ($v) => $v !== null)->values()->all();

        return [
            'pilotos' => $lista->count(),
            'puntos_media' => $this->r(Estadistica::media($col('puntos_totales'))),
            'pos_media' => $this->r(Estadistica::media($col('pos_media'))),
            'pos_desv_media' => $this->r(Estadistica::media($col('pos_desv'))),
            'frec_pos' => $pos,
            'frecuencias' => collect($frec)->map(fn ($n) => [
                'f' => $n,
                'pct' => $total ? round($n * 100 / $total, 1) : 0,
            ])->all(),
        ];
    }

    private function categoria(?int $p): string
    {
        return match (true) {
            $p === null => 'No terminó',
            $p <= 3 => 'Podio (1-3)',
            $p <= 10 => 'Zona de puntos (4-10)',
            default => 'Fuera de puntos (11+)',
        };
    }

    private function r(?float $v): ?float
    {
        return $v === null ? null : round($v, 2);
    }
}
