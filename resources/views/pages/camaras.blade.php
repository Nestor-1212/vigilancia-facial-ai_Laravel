@extends('layouts.app')

@section('title', 'Cámaras — VigiFacial')
@section('page-title', '📷 Gestión de Cámaras')

@push('scripts')
<script>
(function(){ const u = JSON.parse(localStorage.getItem('user') || '{}'); if (u.rol !== 'admin') window.location.href = '/dashboard'; })();
</script>
@endpush

@section('content')
<div x-data="camarasApp()" x-init="init()">

    <div style="display:flex; gap:12px; align-items:center; margin-bottom:20px;">
        <div style="font-size:13px; color:var(--text-secondary);">
            <span x-text="camaras.filter(c=>c.estado==='activa').length"></span> activas ·
            <span x-text="camaras.filter(c=>c.estado==='inactiva').length"></span> inactivas ·
            <span x-text="camaras.filter(c=>c.estado==='error').length" style="color:var(--danger);"></span> con error
        </div>
        <button class="btn btn-primary" style="margin-left:auto;" @click="openModal()">
            ➕ Nueva Cámara
        </button>
    </div>

    <!-- Grid de cámaras -->
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:16px; margin-bottom:20px;">
        <template x-for="c in camaras" :key="c.id">
            <div class="card" style="display:flex; flex-direction:column; gap:12px;">
                <!-- Preview — MJPEG si hay stream activo, placeholder si no -->
                <div style="background:#000; border-radius:8px; height:140px; display:flex; align-items:center; justify-content:center; position:relative; overflow:hidden; border:1px solid var(--border);">
                    <template x-if="streamsActivos.includes(c.id)">
                        <img :src="`http://localhost:8001/stream/video/${c.id}`"
                             style="width:100%; height:140px; object-fit:cover; display:block; border-radius:8px;">
                    </template>
                    <template x-if="!streamsActivos.includes(c.id) && c.estado === 'activa'">
                        <div style="text-align:center;">
                            <div style="font-size:36px; margin-bottom:8px;">📹</div>
                            <div class="badge badge-success"><span class="live-dot" style="width:6px; height:6px;"></span> LIVE</div>
                        </div>
                    </template>
                    <template x-if="!streamsActivos.includes(c.id) && c.estado === 'inactiva'">
                        <div style="text-align:center; color:var(--text-muted);">
                            <div style="font-size:36px; margin-bottom:8px; filter:grayscale(1);">📷</div>
                            <div class="badge badge-gray">Inactiva</div>
                        </div>
                    </template>
                    <template x-if="!streamsActivos.includes(c.id) && c.estado === 'error'">
                        <div style="text-align:center;">
                            <div style="font-size:36px; margin-bottom:8px;">⚠️</div>
                            <div class="badge badge-danger">Error</div>
                        </div>
                    </template>
                    <div x-show="streamsActivos.includes(c.id)" style="position:absolute; top:6px; left:6px;">
                        <span class="badge badge-danger" style="font-size:10px; padding:2px 6px;"><span class="live-dot" style="width:5px;height:5px;"></span> LIVE</span>
                    </div>
                    <div style="position:absolute; top:6px; right:6px; font-size:10px; background:rgba(0,0,0,0.6); padding:2px 8px; border-radius:10px; color:var(--text-secondary);" x-text="c.ip ?? ''"></div>
                </div>

                <div>
                    <div style="font-weight:600; margin-bottom:2px;" x-text="c.nombre"></div>
                    <div style="font-size:12px; color:var(--text-secondary);" x-text="c.ubicacion"></div>
                    <div style="font-size:11px; color:var(--text-muted); margin-top:4px; font-family:monospace; word-break:break-all;" x-text="c.rtsp_url"></div>
                </div>

                <div style="display:flex; gap:8px; padding-top:8px; border-top:1px solid var(--border); flex-wrap:wrap;">
                    <div style="font-size:12px; color:var(--text-muted); align-self:center;">
                        🔔 <span x-text="c.alertas_count ?? 0"></span> alertas
                    </div>
                    <div style="margin-left:auto; display:flex; gap:6px; flex-wrap:wrap;">
                        <!-- Botón Iniciar / Detener stream Python -->
                        <button x-show="!streamsActivos.includes(c.id)"
                                class="btn btn-sm" style="background:#10b981; color:white; border:none;"
                                @click="iniciarStream(c)" title="Iniciar procesamiento de video IA">
                            ▶️ Stream
                        </button>
                        <button x-show="streamsActivos.includes(c.id)"
                                class="btn btn-danger btn-sm"
                                @click="detenerStream(c)" title="Detener procesamiento de video IA">
                            ⏹️ Stream
                        </button>
                        <button class="btn btn-ghost btn-sm" @click="openModal(c)">✏️</button>
                        <button class="btn btn-danger btn-sm" @click="eliminar(c)">🗑️</button>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="!loading && camaras.length === 0">
            <div class="card" style="grid-column:1/-1; text-align:center; color:var(--text-muted); padding:40px;">
                No hay cámaras registradas. ¡Agrega la primera!
            </div>
        </template>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" :class="{ open: modalAbierto }">
        <div class="modal" @click.stop>
            <div class="modal-title" x-text="editando ? '✏️ Editar Cámara' : '➕ Nueva Cámara'"></div>
            <form @submit.prevent="guardar()">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input class="form-control" x-model="form.nombre" placeholder="Entrada Principal" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ubicación *</label>
                        <input class="form-control" x-model="form.ubicacion" placeholder="Lobby - Puerta A" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">URL / Fuente *</label>
                    <input class="form-control" x-model="form.rtsp_url" placeholder="rtsp://192.168.1.100:554/stream" required>
                    <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                        💡 Para usar la cámara de tu laptop escribe: <strong>webcam:0</strong>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">IP de la cámara</label>
                        <input class="form-control" x-model="form.ip" placeholder="192.168.1.100">
                    </div>
                    <div class="form-group" x-show="editando">
                        <label class="form-label">Estado</label>
                        <select class="form-control" x-model="form.estado">
                            <option value="activa">Activa</option>
                            <option value="inactiva">Inactiva</option>
                            <option value="error">Error</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" x-model="form.grabacion_activa">
                        <span class="form-label" style="margin:0;">Grabación activa</span>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-ghost" @click="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" :disabled="guardando">
                        <span x-show="!guardando">💾 Guardar</span>
                        <span x-show="guardando">⏳ Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function camarasApp() {
    return {
        camaras: [], streamsActivos: [], loading: false,
        modalAbierto: false, editando: null, guardando: false,
        form: { nombre: '', ubicacion: '', rtsp_url: '', ip: '', estado: 'inactiva', grabacion_activa: false },

        async init() {
            await this.load();
            try {
                const r = await fetch('http://localhost:8001/stream/list');
                const d = await r.json();
                this.streamsActivos = d.streams.map(s => s.camara_id);
            } catch (e) {}
        },

        async load() {
            this.loading = true;
            try {
                const res = await fetch('/api/camaras', { headers: this.headers() });
                this.camaras = await res.json();
            } catch (e) {} finally { this.loading = false; }
        },

        async toggleEstado(c) {
            await fetch(`/api/camaras/${c.id}/toggle`, { method: 'PATCH', headers: this.headers() });
            await this.load();
        },

        openModal(c = null) {
            this.editando = c;
            this.form = c ? { nombre: c.nombre, ubicacion: c.ubicacion, rtsp_url: c.rtsp_url, ip: c.ip ?? '', estado: c.estado, grabacion_activa: c.grabacion_activa } : { nombre: '', ubicacion: '', rtsp_url: '', ip: '', estado: 'inactiva', grabacion_activa: false };
            this.modalAbierto = true;
        },
        cerrarModal() { this.modalAbierto = false; this.editando = null; },

        async guardar() {
            this.guardando = true;
            const url = this.editando ? `/api/camaras/${this.editando.id}` : '/api/camaras';
            const method = this.editando ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, { method, headers: { ...this.headers(), 'Content-Type': 'application/json' }, body: JSON.stringify(this.form) });
                if (res.ok) { this.cerrarModal(); await this.load(); }
            } catch (e) {} finally { this.guardando = false; }
        },

        async eliminar(c) {
            if (!confirm(`¿Eliminar cámara "${c.nombre}"?`)) return;
            await fetch(`/api/camaras/${c.id}`, { method: 'DELETE', headers: this.headers() });
            await this.load();
        },

        async iniciarStream(c) {
            // Leer config para determinar el modo de detección
            let modo = 'reconocimiento_facial';
            try {
                const cfg = await fetch('/api/configuracion', { headers: this.headers() });
                if (cfg.ok) {
                    const d = await cfg.json();
                    const tapaboca = d.deteccion_tapaboca === '1' || d.deteccion_tapaboca === true;
                    const casco    = d.deteccion_casco    === '1' || d.deteccion_casco    === true;
                    if (tapaboca && casco) modo = 'ambos';
                    else if (tapaboca)     modo = 'tapaboca';
                    else if (casco)        modo = 'casco';
                }
            } catch (e) {}

            try {
                const res = await fetch('http://localhost:8001/stream/start', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ camara_id: c.id, rtsp_url: c.rtsp_url, nombre: c.nombre, modo }),
                });
                if (res.ok) {
                    this.streamsActivos = [...this.streamsActivos, c.id];
                    // Marcar activa en Laravel si no lo está
                    if (c.estado !== 'activa') {
                        await fetch(`/api/camaras/${c.id}/toggle`, { method: 'PATCH', headers: this.headers() });
                        await this.load();
                    }
                    alert(`✅ Stream iniciado en modo: ${modo}`);
                } else {
                    const err = await res.json().catch(() => ({}));
                    alert('❌ Error al iniciar stream: ' + (err.detail ?? res.status));
                }
            } catch (e) {
                alert('❌ No se pudo conectar con el microservicio IA (¿está corriendo en localhost:8001?)');
            }
        },

        async detenerStream(c) {
            try {
                await fetch(`http://localhost:8001/stream/stop/${c.id}`, { method: 'POST' });
                this.streamsActivos = this.streamsActivos.filter(id => id !== c.id);
            } catch (e) {}
        },

        headers() { return { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || ''), 'Accept': 'application/json' }; },
    };
}
</script>
@endpush
