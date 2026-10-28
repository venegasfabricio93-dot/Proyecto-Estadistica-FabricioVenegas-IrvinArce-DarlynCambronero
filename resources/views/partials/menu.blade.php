<header style="background:#111827;color:#fff;padding:12px 24px;display:flex;align-items:center;gap:24px;flex-wrap:wrap">
    <strong style="font-size:18px">Graficadora F1</strong>
    <nav style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('dashboard', request()->only(['year', 'min'])) }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Gráficos y medidas</a>
        <a href="{{ route('agrupados', request()->only(['year', 'min'])) }}"
           class="nav-link {{ request()->routeIs('agrupados') ? 'active' : '' }}">Datos agrupados</a>
    </nav>
</header>
<style>
    .nav-link { color: #d1d5db; text-decoration: none; padding: 6px 12px; border-radius: 6px; }
    .nav-link:hover { background: #1f2937; }
    .nav-link.active { background: #e10600; color: #fff; }
</style>
