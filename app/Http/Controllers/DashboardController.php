<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Services\DriverStatsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DriverStatsService $servicio)
    {
        $temporadas = Season::orderByDesc('year')->pluck('year');
        $year = (int) $request->query('year', $temporadas->first());
        $min = max(1, (int) $request->query('min', 5));

        $datos = $servicio->temporada($year, $min);

        return view('dashboard', compact('temporadas', 'year', 'min', 'datos'));
    }
}
