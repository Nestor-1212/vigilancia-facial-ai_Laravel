@extends('layouts.app')

@section('title', 'Configuración — VigiFacial')
@section('page-title', '⚙️ Configuración del Sistema')

@push('scripts')
<script>
(function(){ const u = JSON.parse(localStorage.getItem('user') || '{}'); if (u.rol !== 'admin') window.location.href = '/dashboard'; })();
</script>
@endpush

@section('content')
<div x-data="configApp()" x-init="init()">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

        <!-- Microservicio IA -->
        <div class="card">
            <div class="card-title">🤖 Microservicio IA (FastAPI)</div>
            <div class="form-group">
                <label class="form-label">URL del servicio facial</label>
                <input class="form-control" x-model="config.facial_api_url" placeholder="http://localhost:8001">
            </div>
            <div class="form-group">
                <label class="form-label">Umbral de confianza mínima (%)</label>
                <input type="number" class="form-control" x-model="config.confianza_minima" min="0" max="100" placeholder="60">
                <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Detecciones por debajo de este umbral se marcan como desconocidas.</div>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="font-size:13px; color:var(--text-secondary);">Estado del servicio:</span>
                <span class="badge" :class="estadoIA === 'online' ? 'badge-success' : 'badge-danger'" x-text="estadoIA"></span>
                <button class="btn btn-ghost btn-sm" @click="pingIA()">🔄 Verificar</button>
            </div>
        </div>

        <!-- Alertas -->
        <div class="card">
            <div class="card-title">🔔 Configuración de Alertas</div>
            <div class="form-group">
                <label class="form-label">Tiempo sin actividad para alerta (minutos)</label>
                <input type="number" class="form-control" x-model="config.tiempo_inactividad" placeholder="5">
            </div>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" x-model="config.alertas_desconocidos">
                    <span class="form-label" style="margin:0;">Generar alerta por personas desconocidas</span>
                </label>
            </div>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" x-model="config.alertas_multiples">
                    <span class="form-label" style="margin:0;">Alertar cuando se detecten múltiples rostros</span>
                </label>
            </div>
        </div>

        <!-- Detección de EPP -->
        <div class="card" style="border-color: #f59e0b; background: rgba(245,158,11,0.04);">
            <div class="card-title" style="color: #f59e0b;">🦺 Detección de Equipo de Protección (EPP)</div>
            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:16px;">
                Activa la detección automática por cámara. Al guardar, el stream usa el modo correspondiente.
            </div>

            <!-- Tapaboca -->
            <div style="
                background: var(--bg-primary);
                border: 1px solid var(--border);
                border-radius: 10px;
                padding: 14px 16px;
                margin-bottom: 12px;
                display: flex; align-items: center; gap: 14px;
            ">
                <span style="font-size:28px; line-height:1;">😷</span>
                <div style="flex:1;">
                    <div style="font-size:14px; font-weight:600; margin-bottom:3px;">Sin Tapaboca / Mascarilla</div>
                    <div style="font-size:11px; color:var(--text-muted);">Alerta cuando se detecte una persona sin mascarilla en cámara</div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; cursor:pointer; flex-shrink:0;"
                     @click="config.deteccion_tapaboca = !config.deteccion_tapaboca; autoGuardar()">
                    <div style="position:relative; display:inline-block; width:44px; height:24px;">
                        <div :style="`position:absolute; inset:0; background:${config.deteccion_tapaboca ? '#f59e0b' : 'var(--border)'}; border-radius:12px; transition:background .2s; cursor:pointer;`"></div>
                        <div :style="`position:absolute; top:3px; left:${config.deteccion_tapaboca ? '23px' : '3px'}; width:18px; height:18px; background:white; border-radius:50%; transition:left .2s; pointer-events:none;`"></div>
                    </div>
                    <span style="font-size:12px; font-weight:600;" :style="`color:${config.deteccion_tapaboca ? '#f59e0b' : 'var(--text-muted)'}`"
                          x-text="config.deteccion_tapaboca ? 'Activo' : 'Inactivo'"></span>
                </div>
            </div>

            <!-- Casco -->
            <div style="
                background: var(--bg-primary);
                border: 1px solid var(--border);
                border-radius: 10px;
                padding: 14px 16px;
                margin-bottom: 12px;
                display: flex; align-items: center; gap: 14px;
            ">
                <span style="font-size:28px; line-height:1;">⛑️</span>
                <div style="flex:1;">
                    <div style="font-size:14px; font-weight:600; margin-bottom:3px;">Sin Casco de Seguridad</div>
                    <div style="font-size:11px; color:var(--text-muted);">Alerta cuando se detecte una persona sin casco en zona de riesgo</div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; cursor:pointer; flex-shrink:0;"
                     @click="config.deteccion_casco = !config.deteccion_casco; autoGuardar()">
                    <div style="position:relative; display:inline-block; width:44px; height:24px;">
                        <div :style="`position:absolute; inset:0; background:${config.deteccion_casco ? '#f59e0b' : 'var(--border)'}; border-radius:12px; transition:background .2s; cursor:pointer;`"></div>
                        <div :style="`position:absolute; top:3px; left:${config.deteccion_casco ? '23px' : '3px'}; width:18px; height:18px; background:white; border-radius:50%; transition:left .2s; pointer-events:none;`"></div>
                    </div>
                    <span style="font-size:12px; font-weight:600;" :style="`color:${config.deteccion_casco ? '#f59e0b' : 'var(--text-muted)'}`"
                          x-text="config.deteccion_casco ? 'Activo' : 'Inactivo'"></span>
                </div>
            </div>

            <!-- Celular -->
            <div style="
                background: var(--bg-primary);
                border: 1px solid var(--border);
                border-radius: 10px;
                padding: 14px 16px;
                display: flex; align-items: center; gap: 14px;
            ">
                <span style="font-size:28px; line-height:1;">📱</span>
                <div style="flex:1;">
                    <div style="font-size:14px; font-weight:600; margin-bottom:3px;">Uso de Celular en Mano</div>
                    <div style="font-size:11px; color:var(--text-muted);">Alerta y registra sesión cuando se detecte un celular en la mano con YOLOv8</div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; cursor:pointer; flex-shrink:0;"
                     @click="config.deteccion_celular = !config.deteccion_celular; autoGuardar()">
                    <div style="position:relative; display:inline-block; width:44px; height:24px;">
                        <div :style="`position:absolute; inset:0; background:${config.deteccion_celular ? '#f59e0b' : 'var(--border)'}; border-radius:12px; transition:background .2s; cursor:pointer;`"></div>
                        <div :style="`position:absolute; top:3px; left:${config.deteccion_celular ? '23px' : '3px'}; width:18px; height:18px; background:white; border-radius:50%; transition:left .2s; pointer-events:none;`"></div>
                    </div>
                    <span style="font-size:12px; font-weight:600;" :style="`color:${config.deteccion_celular ? '#f59e0b' : 'var(--text-muted)'}`"
                          x-text="config.deteccion_celular ? 'Activo' : 'Inactivo'"></span>
                </div>
            </div>

            <!-- Modo activo combinado -->
            <div x-show="config.deteccion_tapaboca || config.deteccion_casco || config.deteccion_celular"
                 style="margin-top:12px; padding:10px 12px; background:rgba(245,158,11,0.1); border-radius:8px; font-size:12px; color:#f59e0b;">
                <strong>Modo activo:</strong>
                <span x-text="modoEPP()"></span>
                — el stream usará modo <span x-text="`&quot;${modoStr()}&quot;`"></span>.
            </div>
        </div>

        <!-- Reverb WebSocket -->
        <div class="card">
            <div class="card-title">⚡ WebSocket (Laravel Reverb)</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label class="form-label">Host</label>
                    <input class="form-control" value="{{ env('REVERB_HOST', 'localhost') }}" readonly style="opacity:0.6;">
                </div>
                <div class="form-group">
                    <label class="form-label">Puerto</label>
                    <input class="form-control" value="{{ env('REVERB_PORT', '8080') }}" readonly style="opacity:0.6;">
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="font-size:13px; color:var(--text-secondary);">Estado WebSocket:</span>
                <span class="badge badge-success"><span class="live-dot" style="width:6px; height:6px;"></span> Conectado</span>
            </div>
        </div>

        <!-- Almacenamiento -->
        <div class="card">
            <div class="card-title">💾 Almacenamiento</div>
            <div class="form-group">
                <label class="form-label">Retención de capturas (días)</label>
                <input type="number" class="form-control" x-model="config.retencion_dias" placeholder="30">
            </div>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" x-model="config.guardar_capturas_permitidas">
                    <span class="form-label" style="margin:0;">Guardar capturas de accesos permitidos</span>
                </label>
            </div>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" x-model="config.guardar_capturas_desconocidos">
                    <span class="form-label" style="margin:0;">Guardar capturas de desconocidos</span>
                </label>
            </div>
        </div>
    </div>

    <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:12px;">
        <button class="btn btn-ghost" @click="init()">↺ Restaurar</button>
        <button class="btn btn-primary" @click="guardar()" :disabled="guardando">
            <span x-show="!guardando">💾 Guardar configuración</span>
            <span x-show="guardando">⏳ Guardando...</span>
        </button>
    </div>

    <!-- Toast de confirmación -->
    <div x-show="toastMsg" x-transition
         style="position:fixed; bottom:24px; right:24px; background:#10b981; color:white; padding:12px 20px; border-radius:10px; font-size:13px; font-weight:600; z-index:9999; box-shadow:0 4px 20px rgba(0,0,0,0.3);"
         x-text="toastMsg"></div>
</div>
@endsection

@push('scripts')
<script>
function configApp() {
    return {
        config: {
            facial_api_url: 'http://localhost:8001',
            confianza_minima: 60,
            tiempo_inactividad: 5,
            alertas_desconocidos: true,
            alertas_multiples: false,
            retencion_dias: 30,
            guardar_capturas_permitidas: false,
            guardar_capturas_desconocidos: true,
            deteccion_tapaboca: false,
            deteccion_casco: false,
            deteccion_celular: false,
        },
        estadoIA: 'verificando...',
        guardando: false,
        toastMsg: '',

        async init() {
            await Promise.all([this.cargarConfig(), this.pingIA()]);
        },

        async cargarConfig() {
            try {
                const res = await fetch('/api/configuracion', {
                    headers: { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || ''), 'Accept': 'application/json' }
                });
                if (!res.ok) return;
                const data = await res.json();
                // Merge valores de DB, convirtiendo strings a tipos correctos
                for (const [k, v] of Object.entries(data)) {
                    if (v === null || v === undefined) continue;
                    if (k in this.config) {
                        if (typeof this.config[k] === 'boolean') {
                            this.config[k] = v === '1' || v === true;
                        } else if (typeof this.config[k] === 'number') {
                            this.config[k] = Number(v);
                        } else {
                            this.config[k] = v;
                        }
                    }
                }
            } catch (e) {}
        },

        async pingIA() {
            try {
                const res = await fetch('/api/configuracion', {
                    headers: { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || ''), 'Accept': 'application/json' }
                });
                // Intenta el endpoint real de status del microservicio via proxy
                this.estadoIA = res.ok ? 'verificando...' : 'error';
                // Ping directo al FastAPI (puede fallar por CORS en browser)
                const ia = await fetch('http://localhost:8001/status').catch(() => null);
                this.estadoIA = ia?.ok ? 'online' : 'offline';
            } catch (e) {
                this.estadoIA = 'offline';
            }
        },

        modoEPP() {
            const t = this.config.deteccion_tapaboca;
            const c = this.config.deteccion_casco;
            const p = this.config.deteccion_celular;
            const partes = [];
            if (t && c) partes.push('Tapaboca + Casco');
            else { if (t) partes.push('Tapaboca'); if (c) partes.push('Casco'); }
            if (p) partes.push('Celular');
            return partes.join(' + ');
        },

        modoStr() {
            const t = this.config.deteccion_tapaboca;
            const c = this.config.deteccion_casco;
            const p = this.config.deteccion_celular;
            if (t && c && p) return 'ambos_celular';
            if (t && c)      return 'ambos';
            if (t && p)      return 'tapaboca_celular';
            if (c && p)      return 'casco_celular';
            if (t)           return 'tapaboca';
            if (c)           return 'casco';
            if (p)           return 'celular';
            return 'reconocimiento_facial';
        },

        async guardar() {
            this.guardando = true;
            try {
                const res = await fetch('/api/configuracion', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + (localStorage.getItem('token') || ''),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.config),
                });
                if (res.ok) {
                    this.mostrarToast('✅ Configuración guardada correctamente');
                    await this.reiniciarStreamsActivos();
                } else {
                    this.mostrarToast('❌ Error al guardar');
                }
            } catch (e) {
                this.mostrarToast('❌ Error de conexión');
            } finally {
                this.guardando = false;
            }
        },

        async autoGuardar() {
            try {
                const res = await fetch('/api/configuracion', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + (localStorage.getItem('token') || ''),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.config),
                });
                if (res.ok) {
                    this.mostrarToast('✅ Guardado automáticamente');
                    await this.reiniciarStreamsActivos();
                }
            } catch (e) {}
        },

        async reiniciarStreamsActivos() {
            try {
                const r = await fetch('http://localhost:8001/stream/list');
                if (!r.ok) return;
                const d = await r.json();
                if (!d.streams || d.streams.length === 0) return;

                const modo = this.modoStr();
                for (const s of d.streams) {
                    await fetch(`http://localhost:8001/stream/stop/${s.camara_id}`, { method: 'POST' });
                    await fetch('http://localhost:8001/stream/start', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ camara_id: s.camara_id, rtsp_url: s.url, nombre: s.nombre, modo }),
                    });
                }
                this.mostrarToast(`🔄 ${d.streams.length} stream(s) actualizados → modo "${modo}"`);
            } catch (e) {}
        },

        mostrarToast(msg) {
            this.toastMsg = msg;
            setTimeout(() => this.toastMsg = '', 3000);
        },
    };
}
</script>
@endpush
