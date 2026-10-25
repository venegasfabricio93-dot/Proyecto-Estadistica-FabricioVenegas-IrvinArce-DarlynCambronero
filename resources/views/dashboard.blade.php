<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Graficadora F1</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f4f5f7; color: #1f2430; }
        header { background: #111827; color: #fff; padding: 16px 24px; }
        main { max-width: 1400px; margin: auto; padding: 24px; }
        .card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; margin-bottom: 20px; }
        .grid .card { margin-bottom: 0; }
        .full { grid-column: 1 / -1; }
        .wrap { overflow-x: auto; }
        .chart { position: relative; height: 480px; }
        .tall { height: 720px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        h2 { margin-top: 0; font-size: 18px; }
        @media (max-width: 900px) {
            .grid { grid-template-columns: minmax(0, 1fr); }
            .chart { height: 380px; }
            .tall { height: 600px; }
        }
    </style>
</head>
<body>
<header><h1 style="margin:0;font-size:20px">Graficadora F1 · Regularidad y puntos acumulados</h1></header>
<main>
    <form method="GET" class="card">
        <label>Temporada
            <select name="year">
                @foreach($temporadas as $t)
                    <option value="{{ $t }}" @selected($t == $year)>{{ $t }}</option>
                @endforeach
            </select>
        </label>
        <label style="margin-left:16px">Mínimo de carreras
            <input type="number" name="min" value="{{ $min }}" min="1" style="width:70px">
        </label>
        <button type="submit">Ver</button>
    </form>

    @if(empty($datos['pilotos']))
        <div class="card">No hay datos para esa temporada. Ejecuta <code>php artisan f1:importar {{ $year }}</code>.</div>
    @else
        {{-- Resumen por grupo --}}
        <div class="card wrap">
            <h2>Pilotos con victorias vs. sin victorias</h2>
            <table>
                <thead><tr>
                    <th>Grupo</th><th>Pilotos</th><th>Puntos totales (prom.)</th>
                    <th>Posición media (prom.)</th><th>Desv. est. de posiciones (prom.)</th>
                </tr></thead>
                <tbody>
                @foreach(['con' => 'Con victorias', 'sin' => 'Sin victorias'] as $k => $etiqueta)
                    @php $g = $datos['grupos'][$k]; @endphp
                    <tr>
                        <td>{{ $etiqueta }}</td><td>{{ $g['pilotos'] }}</td>
                        <td>{{ $g['puntos_media'] ?? '—' }}</td>
                        <td>{{ $g['pos_media'] ?? '—' }}</td>
                        <td>{{ $g['pos_desv_media'] ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Distribución de frecuencias --}}
        <div class="card wrap">
            <h2>Distribución de frecuencias de los resultados por carrera</h2>
            <table>
                <thead><tr>
                    <th>Resultado</th><th>Con victorias (f)</th><th>Con victorias (%)</th>
                    <th>Sin victorias (f)</th><th>Sin victorias (%)</th>
                </tr></thead>
                <tbody>
                @foreach(\App\Services\DriverStatsService::CATEGORIAS as $cat)
                    <tr>
                        <td>{{ $cat }}</td>
                        <td>{{ $datos['grupos']['con']['frecuencias'][$cat]['f'] }}</td>
                        <td>{{ $datos['grupos']['con']['frecuencias'][$cat]['pct'] }}%</td>
                        <td>{{ $datos['grupos']['sin']['frecuencias'][$cat]['f'] }}</td>
                        <td>{{ $datos['grupos']['sin']['frecuencias'][$cat]['pct'] }}%</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Gráficos vistos en clase --}}
        <div class="grid">
            <div class="card full"><h2>1. Barras horizontales: puntos totales por piloto</h2>
                <div class="chart tall"><canvas id="gPuntos"></canvas></div></div>

            <div class="card full"><h2>2. Barras verticales comparativas: % de carreras por posición final</h2>
                <div class="chart"><canvas id="gPosiciones"></canvas></div></div>

            <div class="card full"><h2>3. Barras compuestas: tipo de resultado por piloto (top 10)</h2>
                <div class="chart"><canvas id="gCompuesta"></canvas></div></div>

            <div class="card full"><h2>4. Circular: % de resultados por grupo</h2>
                <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:20px">
                    <div class="chart"><canvas id="gPie_con"></canvas></div>
                    <div class="chart"><canvas id="gPie_sin"></canvas></div>
                </div></div>

            <div class="card full"><h2>5. Lineal aritmético: puntos acumulados (top 5)</h2>
                <div class="chart"><canvas id="gEvolucion"></canvas></div></div>

            <div class="card full"><h2>6. Áreas sombreadas: promedio de puntos acumulados por grupo</h2>
                <div class="chart"><canvas id="gAreas"></canvas></div></div>

            <div class="card"><h2>7. Radial: perfil de resultados por grupo (%)</h2>
                <div class="chart"><canvas id="gRadar"></canvas></div></div>

            <div class="card"><h2>8. Pictograma: victorias por piloto</h2>
                @forelse(collect($datos['pilotos'])->where('victorias', '>', 0)->sortByDesc('victorias') as $p)
                    <div style="display:flex;gap:8px;align-items:center;padding:4px 0">
                        <span style="width:150px">{{ $p['name'] }}</span>
                        <span style="font-size:22px">{{ str_repeat('🏆', $p['victorias']) }}</span>
                        <span>({{ $p['victorias'] }})</span>
                    </div>
                @empty
                    <p>Ningún piloto ganó carreras en esta temporada.</p>
                @endforelse
                <p style="font-size:13px;color:#6b7280">🏆 = 1 victoria</p>
            </div>
        </div>

        {{-- Medidas estadísticas por piloto --}}
        <div class="card wrap">
            <h2>Medidas estadísticas de las posiciones finales por piloto</h2>
            <table>
                <thead><tr>
                    <th>Piloto</th><th>Carreras</th><th>Victorias</th><th>Puntos</th>
                    <th>Media</th><th>Mediana</th><th>Moda</th><th>Rango</th><th>Varianza</th><th>Desv. est.</th>
                </tr></thead>
                <tbody>
                @foreach($datos['pilotos'] as $p)
                    <tr>
                        <td>{{ $p['name'] }}</td><td>{{ $p['carreras'] }}</td><td>{{ $p['victorias'] }}</td>
                        <td>{{ $p['puntos_totales'] }}</td><td>{{ $p['pos_media'] ?? '—' }}</td>
                        <td>{{ $p['pos_mediana'] ?? '—' }}</td>
                        <td>{{ implode(', ', $p['pos_moda']) ?: '—' }}</td>
                        <td>{{ $p['pos_rango'] ?? '—' }}</td><td>{{ $p['pos_varianza'] ?? '—' }}</td>
                        <td>{{ $p['pos_desv'] ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p style="font-size:13px;color:#6b7280">Las posiciones consideran solo las carreras en que el piloto terminó clasificado.</p>
        </div>

        <script>
            const pilotos = @json($datos['pilotos']);
            const rondas  = @json($datos['rondas']);
            const grupos  = @json($datos['grupos']);
            const CATS    = @json(\App\Services\DriverStatsService::CATEGORIAS);

            Chart.defaults.font.size = 14;

            const ROJO = '#e10600', AZUL = '#2563eb';
            const COLS = ['#16a34a', '#2563eb', '#f59e0b', '#6b7280'];
            const opt  = { maintainAspectRatio: false };
            const colorDe = p => p.victorias > 0 ? ROJO : AZUL;

            // 1. Barras horizontales (simple)
            new Chart(document.getElementById('gPuntos'), {
                type: 'bar',
                data: { labels: pilotos.map(p => p.name),
                        datasets: [{ label: 'Puntos totales', data: pilotos.map(p => p.puntos_totales),
                                     backgroundColor: pilotos.map(colorDe) }] },
                options: { ...opt, indexAxis: 'y', plugins: { legend: { display: false } } }
            });

            // 2. Barras verticales comparativas (% por posición final)
            const maxPos = Math.max(20, ...Object.keys(grupos.con.frec_pos).concat(Object.keys(grupos.sin.frec_pos)).map(Number));
            const posLabels = Array.from({ length: maxPos }, (_, i) => i + 1);
            const pct = g => {
                const total = posLabels.reduce((s, p) => s + (grupos[g].frec_pos[p] ?? 0), 0);
                return posLabels.map(p => total ? +((grupos[g].frec_pos[p] ?? 0) * 100 / total).toFixed(1) : 0);
            };
            new Chart(document.getElementById('gPosiciones'), {
                type: 'bar',
                data: { labels: posLabels, datasets: [
                    { label: 'Con victorias', data: pct('con'), backgroundColor: ROJO },
                    { label: 'Sin victorias', data: pct('sin'), backgroundColor: AZUL },
                ]},
                options: { ...opt, scales: {
                    x: { title: { display: true, text: 'Posición final' } },
                    y: { title: { display: true, text: '% de carreras' } } } }
            });

            // 3. Barras compuestas (apiladas, top 10)
            const primeros = pilotos.slice(0, 10);
            new Chart(document.getElementById('gCompuesta'), {
                type: 'bar',
                data: { labels: primeros.map(p => p.name),
                        datasets: CATS.map((c, i) => ({ label: c, data: primeros.map(p => p.categorias[c] ?? 0), backgroundColor: COLS[i] })) },
                options: { ...opt, indexAxis: 'y', scales: { x: { stacked: true }, y: { stacked: true } } }
            });

            // 4. Circular (uno por grupo)
            ['con', 'sin'].forEach(k => new Chart(document.getElementById('gPie_' + k), {
                type: 'pie',
                data: { labels: CATS, datasets: [{ data: CATS.map(c => grupos[k].frecuencias[c].pct), backgroundColor: COLS }] },
                options: { ...opt, plugins: {
                    title: { display: true, text: k === 'con' ? 'Con victorias' : 'Sin victorias', font: { size: 16 } },
                    tooltip: { callbacks: { label: c => `${c.label}: ${c.parsed}%` } } } }
            }));

            // 5. Lineal aritmético (top 5)
            new Chart(document.getElementById('gEvolucion'), {
                type: 'line',
                data: { labels: rondas.map(r => 'R' + r),
                        datasets: pilotos.slice(0, 5).map(p => ({ label: p.name, data: p.evolucion, tension: 0.2, pointRadius: 2 })) },
                options: opt
            });

            // 6. Áreas sombreadas (promedio por grupo)
            const promEvol = cond => {
                const g = pilotos.filter(cond);
                return rondas.map((_, i) => g.length ? +(g.reduce((s, p) => s + p.evolucion[i], 0) / g.length).toFixed(1) : 0);
            };
            new Chart(document.getElementById('gAreas'), {
                type: 'line',
                data: { labels: rondas.map(r => 'R' + r), datasets: [
                    { label: 'Con victorias', data: promEvol(p => p.victorias > 0), fill: true,
                      borderColor: ROJO, backgroundColor: 'rgba(225,6,0,.25)', pointRadius: 0 },
                    { label: 'Sin victorias', data: promEvol(p => p.victorias === 0), fill: true,
                      borderColor: AZUL, backgroundColor: 'rgba(37,99,235,.25)', pointRadius: 0 },
                ]},
                options: opt
            });

            // 7. Radial o araña
            new Chart(document.getElementById('gRadar'), {
                type: 'radar',
                data: { labels: CATS, datasets: [
                    { label: 'Con victorias', data: CATS.map(c => grupos.con.frecuencias[c].pct),
                      borderColor: ROJO, backgroundColor: 'rgba(225,6,0,.2)' },
                    { label: 'Sin victorias', data: CATS.map(c => grupos.sin.frecuencias[c].pct),
                      borderColor: AZUL, backgroundColor: 'rgba(37,99,235,.2)' },
                ]},
                options: opt
            });
        </script>
    @endif
</main>
</body>
</html>
