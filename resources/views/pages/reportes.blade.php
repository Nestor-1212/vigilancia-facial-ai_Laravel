@extends('layouts.app')

@section('title', 'Reportes — VigiFacial')
@section('page-title', '📋 Reportes del Sistema')

@section('content')
<div x-data="reportesApp()" x-init="init()">

    <div style="display:flex; gap:12px; align-items:center; margin-bottom:20px; flex-wrap:wrap;">
        <input type="date" class="form-control" style="max-width:160px;" x-model="filtros.desde" @change="load()">
        <span style="color:var(--text-secondary);">a</span>
        <input type="date" class="form-control" style="max-width:160px;" x-model="filtros.hasta" @change="load()">
        <button class="btn btn-primary btn-sm" @click="exportarPDF()">📥 Exportar PDF</button>
    </div>

    <!-- 2 gráficos -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
        <div class="card">
            <div class="card-title">Alertas por Tipo</div>
            <canvas id="chartTipos" height="220"></canvas>
        </div>
        <div class="card">
            <div class="card-title">Accesos por Resultado</div>
            <canvas id="chartAccesos" height="220"></canvas>
        </div>
    </div>

    <!-- Tabla de resumen por cámara -->
    <div class="card">
        <div class="card-title">Resumen por Cámara</div>
        <table>
            <thead>
                <tr>
                    <th>Cámara</th>
                    <th>Ubicación</th>
                    <th>Total Alertas</th>
                    <th>Críticas</th>
                    <th>Advertencias</th>
                    <th>Accesos</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="c in camaras" :key="c.id">
                    <tr>
                        <td x-text="c.nombre" style="font-weight:600;"></td>
                        <td x-text="c.ubicacion" style="color:var(--text-secondary); font-size:13px;"></td>
                        <td x-text="c.alertas_count ?? 0"></td>
                        <td style="color:var(--danger);" x-text="'—'"></td>
                        <td style="color:var(--warning);" x-text="'—'"></td>
                        <td x-text="c.registros_acceso_count ?? 0"></td>
                        <td>
                            <span class="badge" :class="c.estado === 'activa' ? 'badge-success' : 'badge-gray'" x-text="c.estado"></span>
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
function reportesApp() {
    return {
        camaras: [],
        filtros: { desde: new Date(Date.now() - 7*86400000).toISOString().split('T')[0], hasta: new Date().toISOString().split('T')[0] },

        async init() {
            await this.load();
        },

        async load() {
            try {
                const res = await fetch('/api/camaras', { headers: { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || '') } });
                this.camaras = await res.json();
                this.$nextTick(() => this.initCharts());
            } catch (e) {}
        },

        initCharts() {
            const palette = ['#ef4444','#f59e0b','#3b82f6','#10b981','#8b5cf6'];
            const opts = { responsive: true, plugins: { legend: { labels: { color: '#94a3b8', font: { size: 12 } } } } };

            new Chart(document.getElementById('chartTipos'), {
                type: 'doughnut',
                data: {
                    labels: ['Restringidas', 'Desconocidos', 'Detectados'],
                    datasets: [{ data: [8, 23, 47], backgroundColor: palette }],
                },
                options: { ...opts }
            });

            new Chart(document.getElementById('chartAccesos'), {
                type: 'bar',
                data: {
                    labels: ['Permitido', 'Denegado', 'Desconocido'],
                    datasets: [{
                        label: 'Accesos',
                        data: [120, 18, 34],
                        backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                        borderRadius: 6,
                    }],
                },
                options: { ...opts, scales: { x: { ticks: { color: '#475569' }, grid: { color: '#1e2d4a' } }, y: { ticks: { color: '#475569' }, grid: { color: '#1e2d4a' }, beginAtZero: true } } }
            });
        },

        exportarPDF() {
            alert('La exportación PDF se generará en el backend con Laravel. Función próximamente.');
        },
    };
}
</script>
@endpush
