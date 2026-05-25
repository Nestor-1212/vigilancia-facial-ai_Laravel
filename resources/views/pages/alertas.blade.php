@extends('layouts.app')

@section('title', 'Alertas — VigiFacial')
@section('page-title', '🔔 Centro de Alertas')

@section('content')
<div x-data="alertasApp()" x-init="init()">

    <!-- Resumen de pendientes -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom:20px;">
        <div class="stat-card danger-card">
            <div class="stat-label">🚨 Críticas pendientes</div>
            <div class="stat-value" x-text="pendientes.critico ?? 0"></div>
        </div>
        <div class="stat-card warning-card">
            <div class="stat-label">⚠️ Advertencias</div>
            <div class="stat-value" x-text="pendientes.advertencia ?? 0"></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">📌 Informativas</div>
            <div class="stat-value" x-text="pendientes.info ?? 0"></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">🔔 Total pendientes</div>
            <div class="stat-value" x-text="pendientes.total ?? 0"></div>
        </div>
    </div>

    <!-- Filtros -->
    <div style="display:flex; gap:12px; align-items:center; margin-bottom:16px; flex-wrap:wrap;">
        <select class="form-control" style="max-width:150px;" x-model="filtros.nivel" @change="load()">
            <option value="">Todos los niveles</option>
            <option value="critico">🚨 Crítico</option>
            <option value="advertencia">⚠️ Advertencia</option>
            <option value="info">📌 Info</option>
        </select>
        <select class="form-control" style="max-width:180px;" x-model="filtros.tipo" @change="load()">
            <option value="">Todos los tipos</option>
            <option value="persona_restringida">Persona Restringida</option>
            <option value="desconocido">Desconocido</option>
            <option value="rostro_detectado">Rostro Detectado</option>
        </select>
        <select class="form-control" style="max-width:150px;" x-model="filtros.revisada" @change="load()">
            <option value="">Todas</option>
            <option value="0">Solo pendientes</option>
            <option value="1">Solo revisadas</option>
        </select>
        <button class="btn btn-ghost btn-sm" @click="marcarTodas()">✅ Marcar todas revisadas</button>
        <div style="margin-left:auto; font-size:13px; color:var(--text-secondary);" x-text="`${meta.total ?? 0} alertas`"></div>
    </div>

    <!-- Tabla -->
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Nivel</th>
                    <th>Tipo</th>
                    <th>Cámara</th>
                    <th>Persona</th>
                    <th>Confianza</th>
                    <th>Captura</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading">
                    <tr><td colspan="9" style="text-align:center; color:var(--text-muted); padding:30px;">⏳ Cargando alertas...</td></tr>
                </template>
                <template x-for="a in alertas" :key="a.id">
                    <tr :style="!a.revisada && a.nivel === 'critico' ? 'background:rgba(239,68,68,0.04)' : ''">
                        <td style="font-size:12px; color:var(--text-secondary); white-space:nowrap;" x-text="formatTs(a.created_at)"></td>
                        <td>
                            <span class="badge" :class="nivelBadge(a.nivel)">
                                <span x-text="nivelIcon(a.nivel)"></span>
                                <span x-text="a.nivel"></span>
                            </span>
                        </td>
                        <td x-text="tipoLabel(a.tipo)" style="font-size:13px; font-weight:500;"></td>
                        <td x-text="a.camara?.nombre ?? '—'" style="font-size:13px; color:var(--text-secondary);"></td>
                        <td>
                            <template x-if="a.persona">
                                <span :class="a.persona?.tipo === 'restringido' ? 'badge badge-danger' : ''" x-text="a.persona?.nombre_completo ?? '—'"></span>
                            </template>
                            <template x-if="!a.persona">
                                <span style="color:var(--text-muted);">Desconocido</span>
                            </template>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="flex:1; height:4px; background:var(--border); border-radius:2px; min-width:60px;">
                                    <div :style="`width:${Math.round(a.confianza*100)}%; height:100%; background:${a.confianza > 0.8 ? 'var(--success)' : a.confianza > 0.5 ? 'var(--warning)' : 'var(--danger)'}; border-radius:2px;`"></div>
                                </div>
                                <span style="font-size:12px;" x-text="Math.round(a.confianza*100) + '%'"></span>
                            </div>
                        </td>
                        <td>
                            <template x-if="a.captura">
                                <img :src="`/storage/${a.captura}`" style="width:40px; height:40px; object-fit:cover; border-radius:6px; cursor:pointer;" @click="verCaptura(a.captura)" alt="Captura">
                            </template>
                            <template x-if="!a.captura">
                                <span style="color:var(--text-muted); font-size:12px;">—</span>
                            </template>
                        </td>
                        <td>
                            <span class="badge" :class="a.revisada ? 'badge-success' : 'badge-warning'" x-text="a.revisada ? '✓ Revisada' : '⏳ Pendiente'"></span>
                        </td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <button x-show="!a.revisada" class="btn btn-ghost btn-sm" @click="revisar(a)">✅</button>
                                <button class="btn btn-danger btn-sm" @click="eliminar(a)">🗑️</button>
                            </div>
                        </td>
                    </tr>
                </template>
                <template x-if="!loading && alertas.length === 0">
                    <tr><td colspan="9" style="text-align:center; color:var(--text-muted); padding:30px;">No hay alertas con los filtros seleccionados.</td></tr>
                </template>
            </tbody>
        </table>

        <!-- Paginación -->
        <div style="display:flex; align-items:center; gap:12px; padding:16px 0 0; border-top:1px solid var(--border); margin-top:12px;">
            <div style="margin-left:auto; display:flex; gap:6px;">
                <button class="btn btn-ghost btn-sm" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">← Ant</button>
                <span style="font-size:13px; padding:5px 10px;" x-text="`${meta.current_page ?? 1} / ${meta.last_page ?? 1}`"></span>
                <button class="btn btn-ghost btn-sm" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">Sig →</button>
            </div>
        </div>
    </div>

    <!-- Modal captura -->
    <div class="modal-overlay" :class="{ open: capturaUrl }" @click="capturaUrl = null">
        <div @click.stop style="background:var(--bg-card); border-radius:12px; padding:16px; max-width:600px;">
            <img :src="capturaUrl ? `/storage/${capturaUrl}` : ''" style="max-width:100%; border-radius:8px;" alt="Captura">
            <button class="btn btn-ghost btn-sm" style="margin-top:12px; width:100%;" @click="capturaUrl = null">Cerrar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function alertasApp() {
    return {
        alertas: [], meta: {}, loading: false,
        pendientes: {},
        filtros: { nivel: '', tipo: '', revisada: '' },
        capturaUrl: null,

        async init() {
            await Promise.all([this.load(), this.loadPendientes()]);
            window.addEventListener('alerta-nueva', async () => {
                await Promise.all([this.load(), this.loadPendientes()]);
            });
        },

        async load(page = 1) {
            this.loading = true;
            const params = new URLSearchParams({ page, ...this.filtros });
            Object.keys(this.filtros).forEach(k => !this.filtros[k] && params.delete(k));
            try {
                const res = await fetch(`/api/alertas?${params}`, { headers: this.headers() });
                const data = await res.json();
                this.alertas = data.data;
                this.meta = { total: data.total, current_page: data.current_page, last_page: data.last_page };
            } catch (e) {} finally { this.loading = false; }
        },

        async loadPendientes() {
            try {
                const res = await fetch('/api/alertas/pendientes', { headers: this.headers() });
                this.pendientes = await res.json();
            } catch (e) {}
        },

        async revisar(a) {
            await fetch(`/api/alertas/${a.id}/revisar`, { method: 'PATCH', headers: this.headers() });
            await Promise.all([this.load(), this.loadPendientes()]);
        },

        async marcarTodas() {
            if (!confirm('¿Marcar todas las alertas como revisadas?')) return;
            await fetch('/api/alertas/revisar-todas', { method: 'POST', headers: this.headers() });
            await Promise.all([this.load(), this.loadPendientes()]);
        },

        async eliminar(a) {
            if (!confirm('¿Eliminar esta alerta?')) return;
            await fetch(`/api/alertas/${a.id}`, { method: 'DELETE', headers: this.headers() });
            await this.load();
        },

        verCaptura(url) { this.capturaUrl = url; },

        headers() { return { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || ''), 'Accept': 'application/json' }; },
        nivelIcon(nivel) { return { critico: '🚨', advertencia: '⚠️', info: '📌' }[nivel] || '🔔'; },
        nivelBadge(nivel) { return { critico: 'badge-danger', advertencia: 'badge-warning', info: 'badge-info' }[nivel] || 'badge-gray'; },
        tipoLabel(tipo) {
            return { persona_restringida: '🚫 Persona Restringida', desconocido: '❓ Desconocido', rostro_detectado: '✅ Rostro Detectado', multiples_rostros: '👥 Múltiples Rostros' }[tipo] || tipo;
        },
        formatTs(ts) {
            if (!ts) return '—';
            const d = new Date(ts);
            return d.toLocaleDateString('es') + ' ' + d.toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' });
        },
    };
}
</script>
@endpush
