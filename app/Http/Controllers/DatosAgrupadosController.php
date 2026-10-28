<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Services\DatosAgrupados;
use App\Services\DriverStatsService;
use Illuminate\Http\Request;

class DatosAgrupadosController extends Controller
{
    public function index(Request $request, DriverStatsService $servicio)
    {
        $temporadas = Season::orderByDesc('year')->pluck('year');
        $year = (int) $request->query('year', $temporadas->first());
        $min  = max(1, (int) $request->query('min', 5));
        $k    = min(12, max(3, (int) $request->query('k', 6)));

        $pilotos = $servicio->temporada($year, $min)['pilotos'];
        $valores = array_map(fn ($p) => (int) round($p['puntos_totales']), $pilotos);

        $tabla = DatosAgrupados::calcular($valores, $k);

        return view('agrupados', compact('temporadas', 'year', 'min', 'k', 'tabla'));
    }
}
