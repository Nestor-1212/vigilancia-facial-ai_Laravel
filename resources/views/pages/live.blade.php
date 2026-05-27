@extends('layouts.app')

@section('title', 'Live Feed — VigiFacial')
@section('page-title', '🔴 Live Feed de Cámaras')

@section('content')
<div x-data="liveApp()" x-init="init()">

    <div style="display:flex; gap:12px; align-items:center; margin-bottom:20px;">
        <span style="font-size:13px; color:var(--text-secondary);">
            <span class="live-dot"></span>&nbsp;
            <span x-text="camaras.filter(c=>c.estado==='activa').length"></span> cámaras activas en vivo
        </span>
        <select class="form-control" style="max-width:150px; margin-left:auto;" x-model="layout" @change="updateLayout()">
            <option value="2x2">Grid 2×2</option>
            <option value="3x2">Grid 3×2</option>
            <option value="1x1">Vista única</option>
        </select>
        <button class="btn btn-ghost btn-sm" @click="fullscreenGrid()">⛶ Pantalla completa</button>
    </div>

    <!-- Grid de feeds -->
    <div id="camera-grid" :style="gridStyle()">
        <template x-for="c in camarasVisibles" :key="c.id">
            <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:10px; overflow:hidden; position:relative; aspect-ratio:16/9;"
                 :style="camara_seleccionada === c.id ? 'border-color:var(--accent); box-shadow:0 0 0 2px var(--accent-glow);' : ''">

                <!-- Feed en vivo — MJPEG desde FastAPI -->
                <div style="width:100%; height:100%; background:#000; display:flex; align-items:center; justify-content:center; position:relative;">
                    <template x-if="streamsActivos.includes(c.id)">
                        <img :src="`http://localhost:8001/stream/video/${c.id}`"
                             style="width:100%; height:100%; object-fit:cover; display:block;"
                             @@error="streamsActivos = streamsActivos.filter(id => id !== c.id)">
                    </template>
                    <template x-if="!streamsActivos.includes(c.id)">
                        <div style="text-align:center; color:var(--text-muted);">
                            <div style="font-size:48px; filter:grayscale(1); opacity:0.3; margin-bottom:8px;">📷</div>
                            <div style="font-size:12px;">Cámara sin señal</div>
                            <div style="font-size:10px; margin-top:4px; font-family:monospace;" x-text="c.rtsp_url ?? ''"></div>
                        </div>
                    </template>

                    <!-- Overlay: info de cámara -->
                    <div style="position:absolute; top:0; left:0; right:0; padding:8px 12px; background:linear-gradient(rgba(0,0,0,0.7),transparent); display:flex; align-items:center; gap:8px;">
                        <span x-show="c.estado === 'activa'" class="badge badge-danger" style="font-size:10px; padding:2px 6px;">
                            <span class="live-dot" style="width:5px;height:5px;"></span> REC
                        </span>
                        <span style="font-size:12px; font-weight:600;" x-text="c.nombre"></span>
                    </div>
                    <div style="position:absolute; bottom:0; left:0; right:0; padding:8px 12px; background:linear-gradient(transparent,rgba(0,0,0,0.7)); display:flex; align-items:center; gap:8px;">
                        <span style="font-size:11px; color:var(--text-secondary);" x-text="c.ubicacion"></span>
                        <span style="margin-left:auto; font-size:11px; color:var(--text-muted);" x-text="c.ip ?? ''"></span>
                    </div>

                    <!-- Alerta en tiempo real sobre la cámara -->
                    <div x-show="alertaEnCamara[c.id]" style="position:absolute; inset:0; border:3px solid var(--danger); border-radius:10px; pointer-events:none; animation:borderPulse 1s infinite;"></div>
                    <div x-show="alertaEnCamara[c.id]" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:rgba(239,68,68,0.9); border-radius:10px; padding:8px 16px; font-weight:700; font-size:13px; text-align:center;">
                        🚨 <span x-text="alertaEnCamara[c.id]?.tipo ?? ''"></span>
                    </div>
                </div>

                <!-- Click para ver detalle -->
                <div style="position:absolute; inset:0; cursor:pointer;" @click="seleccionar(c.id)"></div>
            </div>
        </template>
    </div>

    <!-- Panel lateral de alertas en vivo -->
    <div style="margin-top:20px;" class="card">
        <div class="card-title" style="display:flex; align-items:center; gap:8px;">
            <span class="live-dot"></span> Actividad detectada en tiempo real
        </div>
        <div style="max-height:200px; overflow-y:auto; display:flex; flex-direction:column; gap:8px;">
            <template x-for="evento in eventos" :key="evento.ts">
                <div style="display:flex; gap:10px; align-items:center; padding:8px 10px; background:var(--bg-primary); border-radius:8px;">
                    <span x-text="nivelIcon(evento.nivel)" style="font-size:18px;"></span>
                    <div style="flex:1;">
                        <span style="font-weight:600; font-size:13px;" x-text="tipoLabel(evento.tipo)"></span>
                        <span style="color:var(--text-secondary); font-size:12px;"> · </span>
                        <span style="color:var(--text-secondary); font-size:12px;" x-text="evento.camara?.nombre"></span>
                    </div>
                    <span style="font-size:11px; color:var(--text-muted); white-space:nowrap;" x-text="formatHora(evento.ts)"></span>
                </div>
            </template>
            <div x-show="eventos.length === 0" style="color:var(--text-muted); font-size:13px; text-align:center; padding:20px;">
                Sin actividad detectada
            </div>
        </div>
    </div>
</div>

<style>
@@keyframes borderPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>
@endsection

@push('scripts')
<script>
function liveApp() {
    return {
        camaras: [],
        camarasVisibles: [],
        streamsActivos: [],
        layout: '2x2',
        camara_seleccionada: null,
        alertaEnCamara: {},
        eventos: [],

        async init() {
            await this.loadCamaras();
            await this.loadStreamsActivos();
            window.addEventListener('alerta-nueva', (e) => {
                this.onAlerta(e.detail);
            });
        },

        async loadCamaras() {
            try {
                const res = await fetch('/api/camaras', { headers: { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || '') } });
                this.camaras = await res.json();
                this.updateLayout();
            } catch (e) {}
        },

        async loadStreamsActivos() {
            try {
                const res = await fetch('http://localhost:8001/stream/list');
                const data = await res.json();
                this.streamsActivos = data.streams.map(s => s.camara_id);
            } catch (e) {
                this.streamsActivos = [];
            }
        },

        updateLayout() {
            const maxCams = { '1x1': 1, '2x2': 4, '3x2': 6 }[this.layout] || 4;
            this.camarasVisibles = this.camaras.slice(0, maxCams);
        },

        gridStyle() {
            const cols = { '1x1': 1, '2x2': 2, '3x2': 3 }[this.layout] || 2;
            return `display:grid; grid-template-columns:repeat(${cols}, 1fr); gap:12px; margin-bottom:20px;`;
        },

        seleccionar(id) {
            this.camara_seleccionada = this.camara_seleccionada === id ? null : id;
        },

        onAlerta(alerta) {
            this.eventos.unshift({ ...alerta, ts: new Date().toISOString() });
            if (this.eventos.length > 50) this.eventos.pop();

            // Mostrar alerta sobre la cámara por 5s
            if (alerta.camara?.id) {
                this.alertaEnCamara[alerta.camara.id] = alerta;
                setTimeout(() => {
                    delete this.alertaEnCamara[alerta.camara.id];
                    this.alertaEnCamara = { ...this.alertaEnCamara };
                }, 5000);
            }
        },

        fullscreenGrid() {
            const el = document.getElementById('camera-grid');
            if (el.requestFullscreen) el.requestFullscreen();
        },

        nivelIcon(nivel) { return { critico: '🚨', advertencia: '⚠️', info: '📌' }[nivel] || '🔔'; },
        tipoLabel(tipo) {
            return {
                persona_restringida: '🚫 Persona Restringida',
                desconocido: '❓ Desconocido',
                rostro_detectado: '✅ Rostro Detectado',
                sin_tapaboca: '😷 Sin Tapaboca',
                sin_casco: '⛑️ Sin Casco',
            }[tipo] || tipo;
        },
        formatHora(ts) { return ts ? new Date(ts).toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) : ''; },
    };
}
</script>
@endpush
