@extends('layouts.app')

@section('title', 'Dashboard — VigiFacial')
@section('page-title', '📊 Dashboard')

@section('content')
<div x-data="dashboardApp()" x-init="init()">

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">📷 Cámaras Activas</div>
            <div class="stat-value" x-text="stats.camaras?.activas ?? '–'"></div>
            <div class="stat-sub" x-text="`de ${stats.camaras?.total ?? 0} totales`"></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">👥 Personas Registradas</div>
            <div class="stat-value" x-text="stats.personas?.activas ?? '–'"></div>
            <div class="stat-sub" x-text="`${stats.personas?.restringidas ?? 0} restringidas`"></div>
        </div>
        <div class="stat-card warning-card">
            <div class="stat-label">🔔 Alertas Hoy</div>
            <div class="stat-value" x-text="stats.alertas?.hoy ?? '–'"></div>
            <div class="stat-sub" x-text="`${stats.alertas?.pendientes ?? 0} pendientes`"></div>
        </div>
        <div class="stat-card danger-card">
            <div class="stat-label">🚨 Alertas Críticas</div>
            <div class="stat-value" x-text="stats.alertas?.criticas_hoy ?? '–'"></div>
            <div class="stat-sub">hoy</div>
        </div>
        <div class="stat-card success-card">
            <div class="stat-label">✅ Accesos Permitidos</div>
            <div class="stat-value" x-text="stats.accesos_hoy?.permitidos ?? '–'"></div>
            <div class="stat-sub" x-text="`${stats.accesos_hoy?.denegados ?? 0} denegados`"></div>
        </div>
    </div>

    <!-- Gráficos + Alertas recientes -->
    <div style="display:grid; grid-template-columns: 1fr 380px; gap: 20px; margin-bottom: 24px;">

        <!-- Gráfico de alertas por semana -->
        <div class="card">
            <div class="card-title">Alertas últimos 7 días</div>
            <canvas id="chartAlertas" height="220"></canvas>
        </div>

        <!-- Alertas recientes en tiempo real -->
        <div class="card" style="display:flex; flex-direction:column;">
            <div class="card-title" style="display:flex; align-items:center; gap:8px;">
                <span class="live-dot"></span> Alertas en vivo
            </div>
            <div style="flex:1; overflow-y:auto; max-height:280px; display:flex; flex-direction:column; gap:8px;">
                <template x-for="alerta in alertasVivo" :key="alerta.id">
                    <div style="display:flex; gap:10px; align-items:flex-start; padding:10px; background:var(--bg-primary); border-radius:8px;">
                        <span x-text="nivelIcon(alerta.nivel)" style="font-size:18px;"></span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:12px; font-weight:600;" x-text="tipoLabel(alerta.tipo)"></div>
                            <div style="font-size:11px; color:var(--text-secondary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" x-text="alerta.camara?.nombre + ' · ' + (alerta.persona?.nombre || 'Desconocido')"></div>
                        </div>
                        <span class="badge" :class="nivelBadge(alerta.nivel)" x-text="Math.round(alerta.confianza * 100) + '%'"></span>
                    </div>
                </template>
                <div x-show="alertasVivo.length === 0" style="text-align:center; color:var(--text-muted); font-size:13px; padding:20px;">
                    Sin alertas recientes
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla últimas alertas -->
    <div class="card">
        <div class="card-title">Últimas alertas del sistema</div>
        <table>
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Tipo</th>
                    <th>Nivel</th>
                    <th>Cámara</th>
                    <th>Persona</th>
                    <th>Confianza</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="a in stats.ultimas_alertas" :key="a.id">
                    <tr>
                        <td x-text="formatHora(a.created_at)" style="color:var(--text-secondary); font-size:12px;"></td>
                        <td x-text="tipoLabel(a.tipo)" style="font-weight:500;"></td>
                        <td>
                            <span class="badge" :class="nivelBadge(a.nivel)">
                                <span x-text="nivelIcon(a.nivel)"></span>
                                <span x-text="a.nivel"></span>
                            </span>
                        </td>
                        <td x-text="a.camara?.nombre ?? '–'" style="color:var(--text-secondary);"></td>
                        <td x-text="a.persona?.nombre_completo ?? 'Desconocido'"></td>
                        <td x-text="Math.round((a.confianza ?? 0) * 100) + '%'"></td>
                        <td>
                            <span class="badge" :class="a.revisada ? 'badge-success' : 'badge-warning'" x-text="a.revisada ? '✓ Revisada' : 'Pendiente'"></span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboardApp() {
    return {
        stats: {},
        alertasVivo: [],
        chart: null,

        async init() {
            await this.loadStats();
            this.initChart();

            // Escuchar alertas en tiempo real
            window.addEventListener('alerta-nueva', (e) => {
                this.alertasVivo.unshift(e.detail);
                if (this.alertasVivo.length > 20) this.alertasVivo.pop();
            });
        },

        async loadStats() {
            try {
                const res = await fetch('/api/dashboard/stats', {
                    headers: { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || '') }
                });
                const data = await res.json();
                this.stats = data;
                this.alertasVivo = data.ultimas_alertas?.slice(0, 10) || [];
            } catch (e) {}
        },

        initChart() {
            const ctx = document.getElementById('chartAlertas');
            if (!ctx) return;

            const raw = this.stats.alertas_semana || [];
            const labels = [...new Set(raw.map(r => r.fecha))].sort();
            const nivelesColors = {
                critico: '#ef4444',
                advertencia: '#f59e0b',
                info: '#3b82f6',
            };

            const datasets = ['critico', 'advertencia', 'info'].map(nivel => ({
                label: nivel.charAt(0).toUpperCase() + nivel.slice(1),
                data: labels.map(fecha => {
                    const row = raw.find(r => r.fecha === fecha && r.nivel === nivel);
                    return row ? row.total : 0;
                }),
                backgroundColor: nivelesColors[nivel] + '33',
                borderColor: nivelesColors[nivel],
                borderWidth: 2,
                fill: true,
                tension: 0.4,
            }));

            this.chart = new Chart(ctx, {
                type: 'line',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    plugins: { legend: { labels: { color: '#94a3b8', font: { size: 12 } } } },
                    scales: {
                        x: { ticks: { color: '#475569' }, grid: { color: '#1e2d4a' } },
                        y: { ticks: { color: '#475569' }, grid: { color: '#1e2d4a' }, beginAtZero: true },
                    }
                }
            });
        },

        nivelIcon(nivel) {
            return { critico: '🚨', advertencia: '⚠️', info: '📌' }[nivel] || '🔔';
        },
        nivelBadge(nivel) {
            return { critico: 'badge-danger', advertencia: 'badge-warning', info: 'badge-info' }[nivel] || 'badge-gray';
        },
        tipoLabel(tipo) {
            return {
                persona_restringida: '🚫 Persona Restringida',
                desconocido: '❓ Desconocido',
                rostro_detectado: '✅ Rostro Detectado',
                multiples_rostros: '👥 Múltiples Rostros',
            }[tipo] || tipo;
        },
        formatHora(ts) {
            return ts ? new Date(ts).toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' }) : '–';
        },
    };
}
</script>
@endpush
