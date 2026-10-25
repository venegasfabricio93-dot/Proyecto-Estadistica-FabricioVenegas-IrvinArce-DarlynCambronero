<?php

namespace App\Console\Commands;

use App\Models\Driver;
use App\Models\Race;
use App\Models\Result;
use App\Models\Season;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportarTemporada extends Command
{
    protected $signature = 'f1:importar {year : Año de la temporada}';
    protected $description = 'Importa carreras y resultados de una temporada de F1 desde Jolpica';

    private string $base = 'https://api.jolpi.ca/ergast/f1';

    public function handle(): int
    {
        $year = (int) $this->argument('year');

        $calendario = $this->pedir("$year/races/?limit=100");
        $carreras = $calendario['MRData']['RaceTable']['Races'] ?? [];

        if (empty($carreras)) {
            $this->error("No se encontraron carreras para $year.");
            return self::FAILURE;
        }

        $season = Season::firstOrCreate(['year' => $year]);

        foreach ($carreras as $c) {
            $ronda = (int) $c['round'];
            $this->info("Importando ronda $ronda: {$c['raceName']}");

            $resultados = $this->pedir("$year/$ronda/results/?limit=100")
                ['MRData']['RaceTable']['Races'][0]['Results'] ?? [];
            $sprint = $this->pedir("$year/$ronda/sprint/?limit=100")
                ['MRData']['RaceTable']['Races'][0]['SprintResults'] ?? [];

            $puntosSprint = [];
            foreach ($sprint as $s) {
                $puntosSprint[$s['Driver']['driverId']] = (float) $s['points'];
            }

            DB::transaction(function () use ($season, $c, $ronda, $resultados, $sprint, $puntosSprint) {
                $race = Race::updateOrCreate(
                    ['season_id' => $season->id, 'round' => $ronda],
                    [
                        'name' => $c['raceName'],
                        'date' => $c['date'] ?? null,
                        'has_sprint' => !empty($sprint),
                    ]
                );

                foreach ($resultados as $r) {
                    $d = $r['Driver'];

                    $driver = Driver::firstOrCreate(
                        ['name' => $d['givenName'] . ' ' . $d['familyName']],
                        ['code' => $d['code'] ?? null, 'nationality' => $d['nationality'] ?? null]
                    );

                    Result::updateOrCreate(
                        ['race_id' => $race->id, 'driver_id' => $driver->id],
                        [
                            // positionText es numérico si terminó clasificado; "R", "D", "W"... si no
                            'position' => is_numeric($r['positionText']) ? (int) $r['positionText'] : null,
                            'points' => (float) $r['points'],
                            'sprint_points' => $puntosSprint[$d['driverId']] ?? 0,
                        ]
                    );
                }
            });
        }

        $this->info("Temporada $year importada.");
        return self::SUCCESS;
    }

    private function pedir(string $ruta): array
    {
        $respuesta = Http::retry(3, 2000)->get("{$this->base}/$ruta")->throw()->json();
        usleep(500000); // pausa de 0.5 s para respetar el límite de la API
        return $respuesta ?? [];
    }
}
