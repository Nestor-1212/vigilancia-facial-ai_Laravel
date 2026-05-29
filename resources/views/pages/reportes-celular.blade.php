@extends('layouts.app')

@section('title', 'Reporte Celular — VigiFacial')
@section('page-title', '📱 Reporte: Uso de Celular')

@section('content')
<div x-data="celularReporteApp()" x-init="init()">

    <!-- Filtros -->
    <div style="display:flex; gap:12px; align-items:center; margin-bottom:20px; flex-wrap:wrap;">
        <input type="date" class="form-control" style="max-width:160px;" x-model="filtros.desde" @change="load()">
        <span style="color:var(--text-secondary);">a</span>
        <input type="date" class="form-control" style="max-width:160px;" x-model="filtros.hasta" @change="load()">

        <select class="form-control" style="max-width:150px;" x-model="filtros.estado" @change="load()">
            <option value="">Todos</option>
            <option value="activo">En curso</option>
            <option value="finalizado">Finalizados</option>
        </select>

        <button class="btn btn-ghost btn-sm" @click="resetFiltros()">↺ Limpiar</button>

        <div style="margin-left:auto; display:flex; align-items:center; gap:8px;">
            <span style="font-size:13px; color:var(--text-secondary);" x-text="`${meta.total ?? 0} registros`"></span>
            <div style="width:1px; height:20px; background:var(--border);"></div>
            <button class="btn btn-danger btn-sm" @click="eliminarTodos()" title="Eliminar todos los registros del filtro actual">
                🗑️ Eliminar todos
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr); margin-bottom:20px;">
        <div class="stat-card warning-card">
            <div class="stat-label">📱 Total Sesiones</div>
            <div class="stat-value" x-text="stats.total_sesiones ?? 0"></div>
        </div>
        <div class="stat-card danger-card">
            <div class="stat-label">🔴 En Curso</div>
            <div class="stat-value" x-text="stats.sesiones_activas ?? 0"></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">⏱ Duración Prom.</div>
            <div class="stat-value" style="font-size:20px;" x-text="formatDur(stats.duracion_promedio ?? 0)"></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">⏱ Duración Máx.</div>
            <div class="stat-value" style="font-size:20px;" x-text="formatDur(stats.duracion_maxima ?? 0)"></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">👤 Personas</div>
            <div class="stat-value" x-text="stats.personas_distintas ?? 0"></div>
        </div>
    </div>

    <!-- Tabla principal -->
    <div class="card">
        <div class="card-title">Sesiones de Uso de Celular</div>
        <table>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Persona</th>
                    <th>Cámara</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Duración</th>
                    <th>Conf. Celular</th>
                    <th>Conf. Facial</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading">
                    <tr><td colspan="10" style="text-align:center; color:var(--text-muted); padding:30px;">⏳ Cargando...</td></tr>
                </template>
                <template x-for="r in registros" :key="r.id">
                    <tr :style="r.estado === 'activo' ? 'background:rgba(245,158,11,0.04)' : ''">

                        <!-- Foto del rostro -->
                        <td>
                            <template x-if="r.foto_rostro">
                                <img :src="`/storage/${r.foto_rostro}`"
                                     style="width:48px; height:48px; object-fit:cover; border-radius:8px; cursor:pointer; border:2px solid var(--border);"
                                     @click="verFoto(r.foto_rostro)" alt="Rostro">
                            </template>
                            <template x-if="!r.foto_rostro">
                                <div style="width:48px; height:48px; background:var(--bg-primary); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:20px; border:1px solid var(--border);">
                                    👤
                                </div>
                            </template>
                        </td>

                        <!-- Persona -->
                        <td>
                            <template x-if="r.persona">
                                <div>
                                    <div style="font-weight:600; font-size:13px;" x-text="r.persona.nombre_completo ?? (r.persona.nombre + ' ' + (r.persona.apellido ?? ''))"></div>
                                    <div style="font-size:11px; color:var(--text-secondary);">
                                        <span class="badge badge-gray" style="font-size:10px;" x-text="r.persona.tipo"></span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!r.persona">
                                <span style="color:var(--text-muted); font-size:13px;">Desconocido</span>
                            </template>
                        </td>

                        <!-- Cámara -->
                        <td>
                            <div style="font-size:13px;" x-text="r.camara?.nombre ?? '—'"></div>
                            <div style="font-size:11px; color:var(--text-secondary);" x-text="r.camara?.ubicacion ?? ''"></div>
                        </td>

                        <!-- Inicio -->
                        <td style="font-size:12px; white-space:nowrap;">
                            <div x-text="formatFecha(r.inicio)"></div>
                            <div style="color:var(--text-secondary);" x-text="formatHora(r.inicio)"></div>
                        </td>

                        <!-- Fin -->
                        <td style="font-size:12px; white-space:nowrap;">
                            <template x-if="r.fin">
                                <div>
                                    <div x-text="formatFecha(r.fin)"></div>
                                    <div style="color:var(--text-secondary);" x-text="formatHora(r.fin)"></div>
                                </div>
                            </template>
                            <template x-if="!r.fin">
                                <span style="color:var(--warning);">En curso...</span>
                            </template>
                        </td>

                        <!-- Duración -->
                        <td>
                            <template x-if="r.duracion_segundos !== null">
                                <span :style="`font-weight:600; color:${r.duracion_segundos > 120 ? 'var(--danger)' : r.duracion_segundos > 30 ? 'var(--warning)' : 'var(--text-primary)'};`"
                                      x-text="formatDur(r.duracion_segundos)">
                                </span>
                            </template>
                            <template x-if="r.duracion_segundos === null">
                                <span class="badge badge-warning">En curso</span>
                            </template>
                        </td>

                        <!-- Confianza Celular -->
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="flex:1; height:4px; background:var(--border); border-radius:2px; min-width:50px;">
                                    <div :style="`width:${Math.round((r.confianza_celular ?? 0)*100)}%; height:100%; background:var(--warning); border-radius:2px;`"></div>
                                </div>
                                <span style="font-size:12px;" x-text="Math.round((r.confianza_celular ?? 0)*100) + '%'"></span>
                            </div>
                        </td>

                        <!-- Confianza Facial -->
                        <td>
                            <template x-if="r.confianza_facial > 0">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="flex:1; height:4px; background:var(--border); border-radius:2px; min-width:50px;">
                                        <div :style="`width:${Math.round(r.confianza_facial*100)}%; height:100%; background:${r.confianza_facial > 0.8 ? 'var(--success)' : 'var(--warning)'}; border-radius:2px;`"></div>
                                    </div>
                                    <span style="font-size:12px;" x-text="Math.round(r.confianza_facial*100) + '%'"></span>
                                </div>
                            </template>
                            <template x-if="!r.confianza_facial || r.confianza_facial === 0">
                                <span style="color:var(--text-muted); font-size:12px;">—</span>
                            </template>
                        </td>

                        <!-- Estado -->
                        <td>
                            <span class="badge" :class="r.estado === 'activo' ? 'badge-warning' : 'badge-success'"
                                  x-text="r.estado === 'activo' ? '🔴 En curso' : '✓ Finalizado'">
                            </span>
                        </td>

                        <!-- Acción -->
                        <td>
                            <button class="btn btn-danger btn-sm" @click="eliminar(r)" title="Eliminar este registro">🗑️</button>
                        </td>
                    </tr>
                </template>
                <template x-if="!loading && registros.length === 0">
                    <tr><td colspan="10" style="text-align:center; color:var(--text-muted); padding:40px;">
                        📱 No hay registros de uso de celular en este período.
                    </td></tr>
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

    <!-- Modal foto rostro -->
    <div class="modal-overlay" :class="{ open: fotoUrl }" @click="fotoUrl = null">
        <div @click.stop style="background:var(--bg-card); border-radius:12px; padding:16px; max-width:520px; text-align:center;">
            <p style="font-size:12px; color:var(--text-secondary); margin-bottom:12px;">Foto del portador del celular</p>
            <img :src="fotoUrl ? `/storage/${fotoUrl}` : ''" style="max-width:100%; border-radius:8px;" alt="Rostro detectado">
            <button class="btn btn-ghost btn-sm" style="margin-top:12px; width:100%;" @click="fotoUrl = null">Cerrar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function celularReporteApp() {
    return {
        registros: [],
        stats: {},
        meta: {},
        loading: false,
        fotoUrl: null,
        filtros: {
            desde: new Date(Date.now() - 7*86400000).toISOString().split('T')[0],
            hasta: new Date().toISOString().split('T')[0],
            estado: '',
        },

        async init() {
            await this.load();
            // Auto-refresh cada 30s para ver sesiones activas
            setInterval(() => this.load(this.meta.current_page || 1), 30000);
        },

        async load(page = 1) {
            this.loading = true;
            const params = new URLSearchParams({ page });
            if (this.filtros.desde)  params.set('desde',  this.filtros.desde);
            if (this.filtros.hasta)  params.set('hasta',  this.filtros.hasta);
            if (this.filtros.estado) params.set('estado', this.filtros.estado);

            try {
                const res = await fetch(`/api/celular/reportes?${params}`, { headers: this.headers() });
                if (!res.ok) return;
                const data = await res.json();
                this.registros = data.data;
                this.meta      = data.meta;
                this.stats     = data.stats ?? {};
            } catch (e) {
                console.error('Error cargando reportes celular:', e);
            } finally {
                this.loading = false;
            }
        },

        resetFiltros() {
            this.filtros = {
                desde: new Date(Date.now() - 7*86400000).toISOString().split('T')[0],
                hasta: new Date().toISOString().split('T')[0],
                estado: '',
            };
            this.load();
        },

        async eliminar(r) {
            const nombre = r.persona?.nombre_completo ?? r.persona?.nombre ?? 'Desconocido';
            if (!confirm(`¿Eliminar el registro de "${nombre}"?\n\nEsta acción no se puede deshacer.`)) return;
            await fetch(`/api/celular/registros/${r.id}`, { method: 'DELETE', headers: this.headers() });
            await this.load(this.meta.current_page || 1);
        },

        async eliminarTodos() {
            const n = this.meta.total ?? 0;
            if (n === 0) return;
            if (!confirm(`¿Eliminar PERMANENTEMENTE los ${n} registros con los filtros actuales?\n\nEsta acción no se puede deshacer.`)) return;
            const params = new URLSearchParams();
            if (this.filtros.desde)  params.set('desde',  this.filtros.desde);
            if (this.filtros.hasta)  params.set('hasta',  this.filtros.hasta);
            if (this.filtros.estado) params.set('estado', this.filtros.estado);
            await fetch(`/api/celular/registros?${params}`, { method: 'DELETE', headers: this.headers() });
            await this.load();
        },

        verFoto(url) { this.fotoUrl = url; },

        formatFecha(ts) {
            if (!ts) return '—';
            return new Date(ts).toLocaleDateString('es-ES', { day:'2-digit', month:'2-digit', year:'numeric' });
        },
        formatHora(ts) {
            if (!ts) return '';
            return new Date(ts).toLocaleTimeString('es-ES', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
        },
        formatDur(seg) {
            if (!seg && seg !== 0) return '—';
            if (seg < 60) return seg + 's';
            const m = Math.floor(seg / 60);
            const s = seg % 60;
            return s > 0 ? `${m}m ${s}s` : `${m}m`;
        },

        headers() {
            return { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || ''), 'Accept': 'application/json' };
        },
    };
}
</script>
@endpush
