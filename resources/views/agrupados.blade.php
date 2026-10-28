<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Datos agrupados · Graficadora F1</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f4f5f7; color: #1f2430; }
        main { max-width: 1400px; margin: auto; padding: 24px; }
        .card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 8px 10px; border: 1px solid #d1d5db; text-align: center; }
        thead th { background: #dbeafe; }
        thead.naranja th { background: #fed7aa; }
        tfoot td { font-weight: 700; background: #f3f4f6; }
        h2 { margin-top: 0; font-size: 18px; }
        .formula { font-size: 16px; line-height: 1.9; }
        .resultado { font-weight: 700; color: #b91c1c; }
    </style>
</head>
<body>
@include('partials.menu')
@php $fmt = fn ($x, $d = 2) => rtrim(rtrim(number_format($x, $d, '.', ' '), '0'), '.'); @endphp

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
        <label style="margin-left:16px">Número de clases
            <input type="number" name="k" value="{{ $k }}" min="3" max="12" style="width:70px">
        </label>
        <button type="submit">Ver</button>
    </form>

    @if(!$tabla)
        <div class="card">No hay datos suficientes para esa temporada. Ejecuta <code>php artisan f1:importar {{ $year }}</code>.</div>
    @else
        <div class="card">
            <h2>Datos ordenados: puntos totales por piloto, temporada {{ $year }}</h2>
            <p>{{ implode(', ', $tabla['valores']) }}.</p>
            <p><strong>n = {{ $tabla['n'] }}</strong> pilotos</p>
        </div>

        <div class="card wrap">
            <h2>Amplitud e intervalos de clase</h2>
            <p class="formula">
                Mín. = <strong>{{ $tabla['min'] }}</strong> ·
                Máx. = <strong>{{ $tabla['max'] }}</strong> ·
                Amplitud = Máx − Mín = {{ $tabla['max'] }} − {{ $tabla['min'] }} = <strong>{{ $tabla['rango'] }}</strong> ·
                Intervalo de clase = {{ $tabla['rango'] }} / {{ $tabla['k_pedido'] }} = {{ $fmt($tabla['rango'] / $tabla['k_pedido']) }}
                → se usa <strong>{{ $tabla['amp'] }}</strong>
                ({{ $tabla['k'] }} clases)
            </p>
            <table>
                <thead><tr>
                    <th>Intervalo de clase</th>
                    <th>Límites indicados<br><small>(Lím. inf. – Lím. sup.)</small></th>
                    <th>Límites reales<br><small>(inf. − 0.5 / sup. + 0.5)</small></th>
                    <th>Puntos medios Xi<br><small>(Lím. inf. + Lím. sup.) / 2</small></th>
                </tr></thead>
                <tbody>
                @foreach($tabla['clases'] as $c)
                    <tr>
                        <td>{{ $c['li'] }} – {{ $c['inter_sup'] }}</td>
                        <td>{{ $c['li'] }} – {{ $c['ls'] }}</td>
                        <td>{{ $fmt($c['real_inf'], 1) }} – {{ $fmt($c['real_sup'], 1) }}</td>
                        <td>{{ $fmt($c['xi'], 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card wrap">
            <h2>Distribución de frecuencias</h2>
            <table>
                <thead>
                <tr>
                    <th rowspan="2">Límites indicados</th>
                    <th rowspan="2">Límites reales</th>
                    <th rowspan="2">Puntos medios Xi</th>
                    <th colspan="2">Frecuencias</th>
                    <th colspan="2">ACUM «menos de»</th>
                    <th colspan="2">ACUM «más de»</th>
                </tr>
                <tr>
                    <th>Absoluta Fi</th><th>Relativa</th>
                    <th>Absoluta</th><th>Relativa</th>
                    <th>Absoluta</th><th>Relativa</th>
                </tr>
                </thead>
                <tbody>
                @foreach($tabla['clases'] as $c)
                    <tr>
                        <td>{{ $c['li'] }} – {{ $c['ls'] }}</td>
                        <td>{{ $fmt($c['real_inf'], 1) }} – {{ $fmt($c['real_sup'], 1) }}</td>
                        <td>{{ $fmt($c['xi'], 2) }}</td>
                        <td>{{ $c['fi'] }}</td><td>{{ $fmt($c['fr'], 1) }}%</td>
                        <td>{{ $c['menos_abs'] }}</td><td>{{ $fmt($c['menos_rel'], 1) }}%</td>
                        <td>{{ $c['mas_abs'] }}</td><td>{{ $fmt($c['mas_rel'], 1) }}%</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr>
                    <td colspan="3">TOTAL</td>
                    <td>{{ $tabla['n'] }}</td><td>100%</td>
                    <td colspan="4"></td>
                </tr></tfoot>
            </table>
        </div>

        <div class="card wrap">
            <h2>Cálculos previos y media aritmética</h2>
            <table>
                <thead class="naranja"><tr>
                    <th>fᵢ</th><th>xᵢ (punto medio)</th><th>(xᵢ)²</th><th>xᵢ fᵢ</th><th>xᵢ² fᵢ</th>
                </tr></thead>
                <tbody>
                @foreach($tabla['clases'] as $c)
                    <tr>
                        <td>{{ $c['fi'] }}</td>
                        <td>{{ $fmt($c['xi'], 2) }}</td>
                        <td>{{ $fmt($c['xi2'], 2) }}</td>
                        <td>{{ $fmt($c['xifi'], 2) }}</td>
                        <td>{{ $fmt($c['x2fi'], 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr>
                    <td>{{ $tabla['n'] }}</td><td></td><td></td>
                    <td>{{ $fmt($tabla['sum_xf'], 2) }}</td>
                    <td>{{ $fmt($tabla['sum_x2f'], 2) }}</td>
                </tr></tfoot>
            </table>
            <p class="formula" style="margin-bottom:0">
                <strong>Media aritmética:</strong>
                X̄ = Σ xᵢ fᵢ / n = {{ $fmt($tabla['sum_xf'], 2) }} / {{ $tabla['n'] }} =
                <span class="resultado">{{ $fmt($tabla['media'], 3) }}</span>
            </p>
        </div>

        <div class="card wrap">
            <h2>Variabilidad de datos agrupados</h2>
            <table>
                <thead class="naranja"><tr>
                    <th>Varianza</th><th>Desviación estándar</th><th>Coeficiente de variación</th>
                </tr></thead>
                <tbody>
                <tr class="formula">
                    <td>s² = [ Σ xᵢ² fᵢ − (Σ xᵢ fᵢ)² / n ] / (n − 1)</td>
                    <td>s = √s²</td>
                    <td>C.V. = (desviación estándar / media aritmética) · 100</td>
                </tr>
                <tr class="formula">
                    <td>
                        s² = [ {{ $fmt($tabla['sum_x2f'], 2) }} − ({{ $fmt($tabla['sum_xf'], 2) }})² / {{ $tabla['n'] }} ] / ({{ $tabla['n'] }} − 1)<br>
                        s² = <span class="resultado">{{ $fmt($tabla['varianza'], 4) }}</span>
                    </td>
                    <td>
                        s = √{{ $fmt($tabla['varianza'], 4) }}<br>
                        s = <span class="resultado">{{ $fmt($tabla['desv'], 3) }}</span>
                    </td>
                    <td>
                        C.V. = ({{ $fmt($tabla['desv'], 3) }} / {{ $fmt($tabla['media'], 3) }}) · 100<br>
                        C.V. = <span class="resultado">{{ $tabla['cv'] !== null ? $fmt($tabla['cv'], 2) . '%' : '—' }}</span>
                    </td>
                </tr>
                </tbody>
            </table>
            <p style="font-size:13px;color:#6b7280;margin-bottom:0">
                Los puntos totales se redondean al entero más cercano para agrupar. Se usa la varianza muestral (n − 1), como en clase.
            </p>
        </div>
    @endif
</main>
</body>
</html>
