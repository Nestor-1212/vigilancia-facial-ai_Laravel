<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — VigiFacial</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0a0f1e;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        /* Fondo animado */
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(59,130,246,0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(16,185,129,0.05) 0%, transparent 50%),
                radial-gradient(ellipse at 60% 80%, rgba(239,68,68,0.06) 0%, transparent 50%);
        }
        /* Grid pattern */
        body::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(59,130,246,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59,130,246,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .login-container {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 0;
            width: 100%;
            max-width: 900px;
            min-height: 500px;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #1e2d4a;
            box-shadow: 0 25px 80px rgba(0,0,0,0.5);
        }

        /* Panel izquierdo — branding */
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #0d1526 0%, #111827 100%);
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 1px solid #1e2d4a;
        }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px;
        }
        .brand-name { font-size: 22px; font-weight: 800; }
        .brand-sub { font-size: 13px; color: #94a3b8; margin-top: 2px; }

        .features { list-style: none; display: flex; flex-direction: column; gap: 14px; }
        .feature { display: flex; align-items: center; gap: 12px; font-size: 14px; color: #94a3b8; }
        .feature .f-icon {
            width: 32px; height: 32px;
            background: rgba(59,130,246,0.1);
            border: 1px solid rgba(59,130,246,0.2);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; flex-shrink: 0;
        }

        .powered { font-size: 12px; color: #475569; }
        .powered span { color: #3b82f6; }

        /* Panel derecho — formulario */
        .login-right {
            width: 380px;
            background: #111827;
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-title { font-size: 22px; font-weight: 700; margin-bottom: 6px; }
        .login-sub { font-size: 13px; color: #94a3b8; margin-bottom: 32px; }

        .form-group { margin-bottom: 16px; }
        .form-label { font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 6px; display: block; }
        .form-control {
            width: 100%;
            background: #0a0f1e;
            border: 1px solid #1e2d4a;
            border-radius: 10px;
            padding: 11px 14px;
            color: #f1f5f9;
            font-size: 14px;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        /* Ocultar el ojo nativo del browser en campos password */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear,
        input[type="password"]::-webkit-contacts-auto-fill-button,
        input[type="password"]::-webkit-credentials-auto-fill-button {
            display: none !important;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: opacity .2s, transform .1s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-login:hover { opacity: 0.9; }
        .btn-login:active { transform: scale(0.99); }
        .btn-login:disabled { opacity: 0.6; cursor: not-allowed; }

        .error-msg {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #ef4444;
            margin-bottom: 16px;
            display: none;
        }
        .error-msg.show { display: block; }

        .demo-chips { display: flex; flex-direction: column; gap: 8px; margin-top: 20px; }
        .demo-chip {
            background: rgba(59,130,246,0.05);
            border: 1px solid rgba(59,130,246,0.15);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            color: #94a3b8;
            cursor: pointer;
            transition: all .2s;
            text-align: left;
        }
        .demo-chip:hover { background: rgba(59,130,246,0.1); border-color: rgba(59,130,246,0.3); color: #f1f5f9; }
        .demo-chip strong { color: #3b82f6; }

        @media (max-width: 700px) {
            .login-left { display: none; }
            .login-right { width: 100%; }
        }
    </style>
</head>
<body>
<div class="login-container" x-data="loginApp()">
    <!-- Panel izquierdo -->
    <div class="login-left">
        <div class="brand">
         
            <div>
                <div class="brand-name">VigiFacial</div>
                <div class="brand-sub">Sistema de Reconocimiento Facial</div>
            </div>
        </div>

        <ul class="features">
            <li class="feature">
                <div class="f-icon">🎥</div>
                Monitoreo en tiempo real de múltiples cámaras RTSP
            </li>
            <li class="feature">
                <div class="f-icon">🤖</div>
                IA con DeepFace: reconocimiento facial de alta precisión
            </li>
            <li class="feature">
                <div class="f-icon">⚡</div>
                Alertas instantáneas vía WebSocket (Laravel Reverb)
            </li>
            <li class="feature">
                <div class="f-icon">🔒</div>
                Control de acceso: permitido / denegado / desconocido
            </li>
            <li class="feature">
                <div class="f-icon">📊</div>
                Reportes y estadísticas de accesos y alertas
            </li>
        </ul>

        <div class="powered">Powered by <span>Laravel 11</span> · <span>FastAPI</span> · <span>DeepFace</span></div>
    </div>

    <!-- Panel derecho -->
    <div class="login-right">
        <div class="login-title">Iniciar sesión</div>
        <div class="login-sub">Ingresa tus credenciales de operador</div>

        <div class="error-msg" :class="{ show: error }" x-text="error"></div>

        <form @submit.prevent="login()">
            <div class="form-group">
                <label class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" x-model="form.email" placeholder="admin@vigilancia.com" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input type="password" class="form-control" x-model="form.password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login" :disabled="loading">
                <span x-show="!loading">🔐 Acceder</span>
                <span x-show="loading">⏳ Verificando...</span>
            </button>
        </form>

        <div class="demo-chips">
            <div style="font-size:11px; color:#475569; margin-bottom:4px; text-transform:uppercase; letter-spacing:1px;">Cuentas de prueba</div>
            <div class="demo-chip" @click="fillDemo('admin@vigilancia.com','admin123')">
                👑 <strong>Admin</strong> — admin@vigilancia.com / admin123
            </div>
            <div class="demo-chip" @click="fillDemo('operador@vigilancia.com','op123')">
                🛡️ <strong>Operador</strong> — operador@vigilancia.com / op123
            </div>
        </div>
    </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function loginApp() {
    return {
        form: { email: '', password: '' },
        loading: false,
        error: '',

        fillDemo(email, pass) {
            this.form.email = email;
            this.form.password = pass;
        },

        async login() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message || 'Credenciales incorrectas.';
                    return;
                }
                localStorage.setItem('token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));
                window.location.href = '/dashboard';
            } catch (e) {
                this.error = 'Error de conexión. Verifica el servidor.';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
</body>
</html>
