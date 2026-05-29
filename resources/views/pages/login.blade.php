<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — VigiFacial</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #eef2fb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Decoración de fondo sutil */
        body::before {
            content: '';
            position: fixed;
            top: -30%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(59,130,246,0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -20%;
            left: -5%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99,102,241,0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-card {
            position: relative;
            z-index: 1;
            display: flex;
            width: 100%;
            max-width: 860px;
            min-height: 520px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 8px 48px rgba(30, 64, 175, 0.18), 0 2px 12px rgba(0,0,0,0.08);
        }

        /* ── Panel izquierdo — branding ── */
        .panel-left {
            flex: 1;
            background: linear-gradient(160deg, #1e40af 0%, #2563eb 55%, #3b82f6 100%);
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        /* Círculos decorativos sobre el panel izquierdo */
        .panel-left::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 240px;
            height: 240px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        .panel-left::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: -40px;
            width: 180px;
            height: 180px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }

        .brand { display: flex; align-items: center; gap: 14px; position: relative; z-index: 1; }
        .brand-icon {
            width: 46px; height: 46px;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            backdrop-filter: blur(4px);
        }
        .brand-name { font-size: 22px; font-weight: 800; letter-spacing: -0.3px; }
        .brand-sub  { font-size: 12px; color: rgba(255,255,255,0.65); margin-top: 2px; }

        .features { list-style: none; display: flex; flex-direction: column; gap: 12px; position: relative; z-index: 1; }
        .feature { display: flex; align-items: center; gap: 12px; font-size: 13.5px; color: rgba(255,255,255,0.85); }
        .f-icon {
            width: 30px; height: 30px; flex-shrink: 0;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
        }

        .powered { font-size: 11.5px; color: rgba(255,255,255,0.45); position: relative; z-index: 1; }
        .powered span { color: rgba(255,255,255,0.75); }

        /* ── Panel derecho — formulario ── */
        .panel-right {
            width: 380px;
            background: #ffffff;
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
            letter-spacing: -0.4px;
        }
        .login-sub {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 32px;
        }

        .form-group { margin-bottom: 18px; }
        .form-label {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 7px;
            display: block;
            letter-spacing: 0.2px;
        }
        .form-control {
            width: 100%;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 11px 14px;
            color: #0f172a;
            font-size: 14px;
            font-family: inherit;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .form-control::placeholder { color: #94a3b8; }
        .form-control:focus {
            outline: none;
            background: #fff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear { display: none !important; }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            margin-top: 6px;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 14px rgba(37,99,235,0.35);
        }
        .btn-login:hover { opacity: 0.92; box-shadow: 0 6px 20px rgba(37,99,235,0.4); }
        .btn-login:active { transform: scale(0.99); }
        .btn-login:disabled { opacity: 0.6; cursor: not-allowed; box-shadow: none; }

        .error-msg {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #dc2626;
            margin-bottom: 16px;
            display: none;
        }
        .error-msg.show { display: block; }

        .divider {
            display: flex; align-items: center; gap: 10px;
            margin: 20px 0 14px;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px; background: #e2e8f0;
        }
        .divider span { font-size: 11px; color: #94a3b8; white-space: nowrap; }

        .demo-chips { display: flex; flex-direction: column; gap: 8px; }
        .demo-chip {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            padding: 9px 12px;
            font-size: 12px;
            color: #475569;
            cursor: pointer;
            transition: all .18s;
            text-align: left;
            font-family: inherit;
            width: 100%;
        }
        .demo-chip:hover {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1e40af;
        }
        .demo-chip strong { color: #2563eb; }

        @media (max-width: 680px) {
            .panel-left { display: none; }
            .panel-right { width: 100%; padding: 40px 28px; }
        }
    </style>
</head>
<body>
<div class="login-card" x-data="loginApp()">

    <!-- Panel izquierdo — branding -->
    <div class="panel-left">
        <div class="brand">
            <div class="brand-icon"></div>
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
                <div class="f-icon">🦺</div>
                EPP: detección de tapaboca, casco y celular
            </li>
            <li class="feature">
                <div class="f-icon">📊</div>
                Reportes de accesos, alertas y sesiones de celular
            </li>
        </ul>

        <div class="powered">Powered by <span>Laravel 11</span> · <span>FastAPI</span> · <span>DeepFace</span></div>
    </div>

    <!-- Panel derecho — formulario -->
    <div class="panel-right">
        <div class="login-title">Iniciar sesión</div>
        <div class="login-sub">Ingresa tus credenciales de operador</div>

        <div class="error-msg" :class="{ show: error }" x-text="error"></div>

        <form @submit.prevent="login()">
            <div class="form-group">
                <label class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" x-model="form.email"
                       placeholder="admin@vigilancia.com" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input type="password" class="form-control" x-model="form.password"
                       placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login" :disabled="loading">
                <span x-show="!loading">🔐 Acceder al sistema</span>
                <span x-show="loading">⏳ Verificando...</span>
            </button>
        </form>

        <div class="divider"><span>Cuentas de prueba</span></div>

        <div class="demo-chips">
            <button class="demo-chip" @click="fillDemo('admin@vigilancia.com','admin123')">
                👑 <strong>Admin</strong> — admin@vigilancia.com / admin123
            </button>
            <button class="demo-chip" @click="fillDemo('operador@vigilancia.com','op123')">
                🛡️ <strong>Operador</strong> — operador@vigilancia.com / op123
            </button>
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
            this.form.email    = email;
            this.form.password = pass;
        },

        async login() {
            this.loading = true;
            this.error   = '';
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
