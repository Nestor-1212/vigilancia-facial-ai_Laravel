<!DOCTYPE html>
<html lang="es" x-data="appLayout()" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Vigilancia Facial')</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Pusher JS (para Reverb) -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

    <style>
        :root {
            --bg-primary: #0a0f1e;
            --bg-secondary: #0d1526;
            --bg-card: #111827;
            --bg-card-hover: #1a2540;
            --border: #1e2d4a;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --accent-glow: rgba(59,130,246,0.15);
            --danger: #ef4444;
            --danger-bg: rgba(239,68,68,0.1);
            --warning: #f59e0b;
            --warning-bg: rgba(245,158,11,0.1);
            --success: #10b981;
            --success-bg: rgba(16,185,129,0.1);
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #475569;
            --sidebar-w: 260px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
        }

        /* === SIDEBAR === */
        #sidebar {
            width: var(--sidebar-w);
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0; top: 0; bottom: 0;
            z-index: 100;
            transition: transform .3s ease;
        }
        .sidebar-logo {
            padding: 20px 20px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-logo .logo-icon {
            width: 38px; height: 38px;
            background: var(--accent);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .sidebar-logo .logo-text { font-size: 14px; font-weight: 700; line-height: 1.2; }
        .sidebar-logo .logo-sub { font-size: 11px; color: var(--text-secondary); }

        .sidebar-nav { flex: 1; padding: 12px 0; overflow-y: auto; }
        .nav-section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            padding: 12px 20px 6px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            border-left: 3px solid transparent;
            transition: all .2s;
            cursor: pointer;
        }
        .nav-item:hover { background: var(--accent-glow); color: var(--text-primary); }
        .nav-item.active {
            background: var(--accent-glow);
            color: var(--accent);
            border-left-color: var(--accent);
        }
        .nav-item .icon { font-size: 18px; width: 20px; text-align: center; }
        .nav-badge {
            margin-left: auto;
            background: var(--danger);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }

        .sidebar-user {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar {
            width: 34px; height: 34px;
            background: var(--accent);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 13px;
        }
        .user-info .name { font-size: 13px; font-weight: 600; }
        .user-info .role { font-size: 11px; color: var(--text-secondary); }
        .logout-btn {
            margin-left: auto;
            background: none; border: none;
            color: var(--text-muted);
            cursor: pointer; font-size: 18px;
            padding: 4px;
            transition: color .2s;
        }
        .logout-btn:hover { color: var(--danger); }

        /* === MAIN === */
        #main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .topbar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar h1 { font-size: 16px; font-weight: 600; flex: 1; }
        .topbar-actions { display: flex; align-items: center; gap: 12px; }

        /* Campana de alertas */
        .alert-bell {
            position: relative;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            width: 36px; height: 36px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 16px;
            transition: background .2s;
        }
        .alert-bell:hover { background: var(--bg-card-hover); }
        .alert-bell .badge {
            position: absolute;
            top: -4px; right: -4px;
            background: var(--danger);
            color: white;
            font-size: 10px; font-weight: 700;
            width: 18px; height: 18px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }

        /* Toast de alerta en tiempo real */
        #toast-container {
            position: fixed;
            top: 70px; right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 16px;
            min-width: 300px;
            max-width: 380px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            animation: slideIn .3s ease;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .toast.critico  { border-left: 4px solid var(--danger); }
        .toast.advertencia { border-left: 4px solid var(--warning); }
        .toast.info     { border-left: 4px solid var(--accent); }
        .toast-icon { font-size: 22px; line-height: 1; }
        .toast-body .toast-title { font-size: 13px; font-weight: 600; margin-bottom: 3px; }
        .toast-body .toast-msg   { font-size: 12px; color: var(--text-secondary); }
        .toast-close {
            margin-left: auto; background: none; border: none;
            color: var(--text-muted); cursor: pointer; font-size: 16px;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: none; } }

        .page-content { padding: 24px; flex: 1; }

        /* === CARDS === */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }
        .card-title { font-size: 14px; font-weight: 600; color: var(--text-secondary); margin-bottom: 16px; }

        /* Stat cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 20px;
            display: flex; flex-direction: column; gap: 8px;
        }
        .stat-card .stat-label { font-size: 12px; color: var(--text-secondary); }
        .stat-card .stat-value { font-size: 28px; font-weight: 700; }
        .stat-card .stat-sub   { font-size: 12px; color: var(--text-muted); }
        .stat-card.danger-card  { border-color: var(--danger); background: var(--danger-bg); }
        .stat-card.warning-card { border-color: var(--warning); background: var(--warning-bg); }
        .stat-card.success-card { border-color: var(--success); background: var(--success-bg); }

        /* Tabla */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 10px 14px; font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid var(--border); }
        td { padding: 12px 14px; font-size: 13px; border-bottom: 1px solid var(--border); }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--bg-card-hover); }

        /* Badges */
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-success  { background: var(--success-bg); color: var(--success); }
        .badge-danger   { background: var(--danger-bg); color: var(--danger); }
        .badge-warning  { background: var(--warning-bg); color: var(--warning); }
        .badge-info     { background: var(--accent-glow); color: var(--accent); }
        .badge-gray     { background: rgba(100,116,139,0.15); color: var(--text-secondary); }

        /* Botones */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; transition: all .2s; text-decoration: none; }
        .btn-primary { background: var(--accent); color: white; }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-danger  { background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger); }
        .btn-danger:hover  { background: var(--danger); color: white; }
        .btn-ghost   { background: var(--bg-card); color: var(--text-secondary); border: 1px solid var(--border); }
        .btn-ghost:hover   { background: var(--bg-card-hover); color: var(--text-primary); }
        .btn-sm { padding: 5px 10px; font-size: 12px; }

        /* Modal */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);
            z-index: 1000; align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            width: 100%; max-width: 520px;
            max-height: 90vh; overflow-y: auto;
        }
        .modal-title { font-size: 16px; font-weight: 700; margin-bottom: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-label { font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; display: block; }
        .form-control {
            width: 100%; background: var(--bg-primary);
            border: 1px solid var(--border); border-radius: 8px;
            padding: 10px 12px; color: var(--text-primary);
            font-size: 13px; transition: border-color .2s;
        }
        .form-control:focus { outline: none; border-color: var(--accent); }
        .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }

        /* Pulsing dot for live */
        .live-dot {
            display: inline-block; width: 8px; height: 8px;
            background: var(--danger); border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.3); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: none; }
            #main { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<nav id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">👁️</div>
        <div>
            <div class="logo-text">VigiFacial</div>
            <div class="logo-sub">Sistema de Vigilancia</div>
        </div>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section-label">Principal</div>
        <a href="/dashboard" class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
            <span class="icon">📊</span> Dashboard
        </a>
        <a href="/live" class="nav-item {{ request()->is('live') ? 'active' : '' }}">
            <span class="icon"><span class="live-dot"></span></span> Live Feed
        </a>
        <a href="/alertas" class="nav-item {{ request()->is('alertas*') ? 'active' : '' }}" x-data x-on:alertas-updated.window="$el.querySelector('.nav-badge') && null">
            <span class="icon">🔔</span> Alertas
            <span class="nav-badge" id="sidebar-alertas-badge" style="display:none">0</span>
        </a>

        <template x-if="esAdmin()">
            <div>
                <div class="nav-section-label">Gestión</div>
                <a href="/personas" class="nav-item {{ request()->is('personas*') ? 'active' : '' }}">
                    <span class="icon">👥</span> Personas
                </a>
                <a href="/camaras" class="nav-item {{ request()->is('camaras*') ? 'active' : '' }}">
                    <span class="icon">📷</span> Cámaras
                </a>
            </div>
        </template>

        <div class="nav-section-label">Sistema</div>
        <a href="/reportes" class="nav-item {{ request()->is('reportes*') ? 'active' : '' }}">
            <span class="icon">📋</span> Reportes
        </a>
        <template x-if="esAdmin()">
            <a href="/configuracion" class="nav-item {{ request()->is('configuracion*') ? 'active' : '' }}">
                <span class="icon">⚙️</span> Configuración
            </a>
        </template>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar">{{ substr(auth()->user()?->name ?? 'U', 0, 1) }}</div>
        <div class="user-info">
            <div class="name">{{ auth()->user()?->name ?? 'Usuario' }}</div>
            <div class="role">{{ auth()->user()?->rol ?? 'admin' }}</div>
        </div>
        <form action="/logout" method="POST" style="display:inline">
            @csrf
            <button type="submit" class="logout-btn" title="Cerrar sesión">⏻</button>
        </form>
    </div>
</nav>

<!-- MAIN -->
<div id="main">
    <!-- Topbar -->
    <div class="topbar">
        <h1>@yield('page-title', 'Dashboard')</h1>
        <div class="topbar-actions">
            <div class="alert-bell" onclick="toggleAlertsPanel()" title="Alertas pendientes">
                🔔
                <div class="badge" id="topbar-badge" style="display:none">0</div>
            </div>
            <span style="font-size:13px; color: var(--text-secondary)">
                <span class="live-dot"></span>&nbsp;Sistema activo
            </span>
        </div>
    </div>

    <!-- Contenido de la página -->
    <div class="page-content">
        @yield('content')
    </div>
</div>

<!-- Toast container (alertas real-time) -->
<div id="toast-container"></div>

<script>
function appLayout() {
    return {
        darkMode: true,
        _user: JSON.parse(localStorage.getItem('user') || '{}'),
        esAdmin() { return this._user.rol === 'admin'; },
    };
}

// ====== Reverb / WebSocket ======
function initReverb() {
    const pusher = new Pusher('{{ env("REVERB_APP_KEY") }}', {
        wsHost: '{{ env("REVERB_HOST", "localhost") }}',
        wsPort: {{ env("REVERB_PORT", 8080) }},
        forceTLS: false,
        enabledTransports: ['ws'],
        cluster: 'mt1',
    });

    const channel = pusher.subscribe('alertas');

    channel.bind('alerta.nueva', function(data) {
        showToast(data);
        incrementBadge();
        window.dispatchEvent(new CustomEvent('alerta-nueva', { detail: data }));
    });
}

function showToast(alerta) {
    const tipos = {
        persona_restringida: 'Persona Restringida Detectada',
        desconocido: 'Persona Desconocida',
        rostro_detectado: 'Rostro Detectado',
        multiples_rostros: 'Múltiples Rostros',
        sin_tapaboca: 'Sin Tapaboca Detectado',
        sin_casco: 'Sin Casco de Seguridad',
    };
    const icons = { critico: '🚨', advertencia: '⚠️', info: '📌', sin_tapaboca: '😷', sin_casco: '⛑️' };

    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    const nivelClass = alerta.tipo === 'sin_tapaboca' ? 'advertencia' : alerta.nivel;
    toast.className = `toast ${nivelClass}`;
    const meta = alerta.tipo === 'sin_tapaboca' && alerta.metadata
        ? ` · ${alerta.metadata.sin_tapaboca} persona(s) sin tapaboca`
        : '';
    toast.innerHTML = `
        <span class="toast-icon">${icons[alerta.tipo] || icons[alerta.nivel] || '🔔'}</span>
        <div class="toast-body">
            <div class="toast-title">${tipos[alerta.tipo] || alerta.tipo}</div>
            <div class="toast-msg">${alerta.camara?.nombre || ''} · ${alerta.persona?.nombre || 'Desconocido'} · ${Math.round(alerta.confianza * 100)}% confianza${meta}</div>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">✕</button>
    `;
    container.prepend(toast);
    setTimeout(() => toast.remove(), 8000);
}

function incrementBadge() {
    ['topbar-badge', 'sidebar-alertas-badge'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.style.display = 'flex';
            el.textContent = parseInt(el.textContent || '0') + 1;
        }
    });
}

function toggleAlertsPanel() {
    window.location.href = '/alertas';
}

// Cargar badge inicial
async function loadBadge() {
    try {
        const res = await fetch('/api/alertas/pendientes', {
            headers: { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || '') }
        });
        if (!res.ok) return;
        const data = await res.json();
        if (data.total > 0) {
            ['topbar-badge', 'sidebar-alertas-badge'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.style.display = 'flex'; el.textContent = data.total; }
            });
        }
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', () => {
    initReverb();
    loadBadge();
});
</script>

@stack('scripts')
</body>
</html>
