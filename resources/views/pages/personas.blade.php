@extends('layouts.app')

@section('title', 'Personas — VigiFacial')
@section('page-title', '👥 Personas Registradas')

@push('scripts')
<script>
(function(){ const u = JSON.parse(localStorage.getItem('user') || '{}'); if (u.rol !== 'admin') window.location.href = '/dashboard'; })();
</script>
@endpush

@section('content')
<div x-data="personasApp()" x-init="init()">

    <!-- Filtros y acciones -->
    <div style="display:flex; gap:12px; align-items:center; margin-bottom:20px; flex-wrap:wrap;">
        <input type="text" class="form-control" style="max-width:260px;"
               placeholder="🔍 Buscar por nombre, apellido o cédula..."
               x-model.debounce.400ms="filtros.search" @input="load()">
        <select class="form-control" style="max-width:160px;" x-model="filtros.tipo" @change="load()">
            <option value="">Todos los tipos</option>
            <option value="empleado">Empleados</option>
            <option value="residente">Residentes</option>
            <option value="visitante">Visitantes</option>
            <option value="restringido">Restringidos</option>
        </select>
        <select class="form-control" style="max-width:140px;" x-model="filtros.activo" @change="load()">
            <option value="">Todos</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
        </select>
        <button class="btn btn-primary" style="margin-left:auto;" @click="openModal()">
            ➕ Nueva Persona
        </button>
    </div>

    <!-- Tabla -->
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Nombre / Cédula</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Fotos IA</th>
                    <th>Registrado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading">
                    <tr><td colspan="6" style="text-align:center; color:var(--text-muted); padding:30px;">⏳ Cargando...</td></tr>
                </template>
                <template x-for="p in personas" :key="p.id">
                    <tr>
                        <td>
                            <div style="font-weight:600;" x-text="`${p.nombre} ${p.apellido}`"></div>
                            <div style="font-size:11px; color:var(--text-muted); font-family:monospace;" x-text="p.documento ?? 'Sin cédula'"></div>
                            <div x-show="p.notas" style="font-size:11px; color:var(--text-muted); margin-top:2px;" x-text="p.notas"></div>
                        </td>
                        <td>
                            <span class="badge" :class="tipoBadge(p.tipo)" x-text="tipoLabel(p.tipo)"></span>
                        </td>
                        <td>
                            <span class="badge" :class="p.activo ? 'badge-success' : 'badge-gray'"
                                  x-text="p.activo ? 'Activo' : 'Inactivo'"></span>
                        </td>
                        <td>
                            <template x-if="p.fotos_ia && p.fotos_ia.length > 0">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="display:flex; margin-right:4px;">
                                        <template x-for="(f, fi) in p.fotos_ia.slice(0, 3)" :key="fi">
                                            <img :src="`/storage/${f.ruta}`"
                                                 :title="f.descripcion"
                                                 :style="`width:28px; height:28px; border-radius:50%; object-fit:cover; border:2px solid var(--bg-card); margin-left:${fi > 0 ? '-8px' : '0'};`">
                                        </template>
                                    </div>
                                    <span class="badge badge-success" style="font-size:10px;" x-text="`${p.fotos_ia.length} foto${p.fotos_ia.length !== 1 ? 's' : ''}`"></span>
                                </div>
                            </template>
                            <template x-if="!p.fotos_ia || p.fotos_ia.length === 0">
                                <span class="badge badge-danger" style="font-size:10px;">⚠️ Sin fotos</span>
                            </template>
                        </td>
                        <td x-text="formatFecha(p.created_at)" style="font-size:12px; color:var(--text-secondary);"></td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <button class="btn btn-ghost btn-sm" @click="openModal(p)" title="Editar">✏️</button>
                                <button class="btn btn-danger btn-sm" @click="eliminar(p)" title="Eliminar">🗑️</button>
                            </div>
                        </td>
                    </tr>
                </template>
                <template x-if="!loading && personas.length === 0">
                    <tr><td colspan="6" style="text-align:center; color:var(--text-muted); padding:30px;">No se encontraron personas.</td></tr>
                </template>
            </tbody>
        </table>

        <!-- Paginación -->
        <div style="display:flex; align-items:center; gap:12px; padding:16px 0 0; border-top:1px solid var(--border); margin-top:12px;">
            <span style="font-size:13px; color:var(--text-secondary);" x-text="`${meta.total ?? 0} personas`"></span>
            <div style="margin-left:auto; display:flex; gap:6px;">
                <button class="btn btn-ghost btn-sm" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">← Ant</button>
                <span style="font-size:13px; padding:5px 10px;" x-text="`${meta.current_page ?? 1} / ${meta.last_page ?? 1}`"></span>
                <button class="btn btn-ghost btn-sm" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">Sig →</button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════ MODAL ═══════════════════ -->
    <div class="modal-overlay" :class="{ open: modalAbierto }" @click="cerrarModal()">
        <div class="modal" style="max-width:700px;" @click.stop>

            <div class="modal-title" x-text="editando ? '✏️ Editar Persona' : '➕ Registrar Nueva Persona'"></div>

            <form @submit.prevent="guardar()">

                <!-- ── Datos personales ── -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input class="form-control" x-model="form.nombre" required placeholder="Ej: Juan">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellido *</label>
                        <input class="form-control" x-model="form.apellido" required placeholder="Ej: García">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Cédula / Documento</label>
                        <input class="form-control" x-model="form.documento" placeholder="Ej: 8-123-4567">
                        <div style="font-size:11px; color:var(--text-muted); margin-top:3px;">Usado para búsqueda rápida.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo *</label>
                        <select class="form-control" x-model="form.tipo" required>
                            <option value="empleado">👷 Empleado</option>
                            <option value="residente">🏠 Residente</option>
                            <option value="visitante">🙋 Visitante</option>
                            <option value="restringido">🚫 Restringido</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" x-show="editando">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" x-model="form.activo">
                        <span class="form-label" style="margin:0;">Persona activa en el sistema</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">Notas adicionales</label>
                    <textarea class="form-control" x-model="form.notas" rows="2"
                              placeholder="Ej: Turno noche, usa casco azul, trabaja en zona A..."></textarea>
                </div>

                <!-- ── Separador fotos ── -->
                <div style="border-top:1px solid var(--border); margin:16px 0 14px;"></div>

                <!-- ── Fotos existentes (edición) ── -->
                <template x-if="editando && fotosExistentes.length > 0">
                    <div style="margin-bottom:14px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                            <span style="font-size:13px; font-weight:600;">📸 Fotos registradas en IA</span>
                            <span class="badge badge-success" x-text="`${fotosExistentes.length} foto${fotosExistentes.length !== 1 ? 's' : ''}`" style="font-size:10px;"></span>
                        </div>
                        <div style="display:flex; flex-wrap:wrap; gap:10px;">
                            <template x-for="(f, i) in fotosExistentes" :key="i">
                                <div style="position:relative; text-align:center;">
                                    <img :src="`/storage/${f.ruta}`"
                                         style="width:64px; height:64px; object-fit:cover; border-radius:8px; border:2px solid var(--border); display:block;">
                                    <div style="font-size:9px; color:var(--text-muted); max-width:64px; margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                         :title="f.descripcion" x-text="f.descripcion"></div>
                                    <button type="button"
                                            style="position:absolute; top:-5px; right:-5px; background:var(--danger); color:white; border:none; border-radius:50%; width:18px; height:18px; font-size:10px; cursor:pointer; line-height:1; display:flex; align-items:center; justify-content:center; padding:0;"
                                            @click="eliminarFotoExistente(i)" title="Eliminar esta foto">✕</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- ── Agregar nuevas fotos ── -->
                <div style="margin-bottom:4px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                        <div>
                            <span style="font-size:13px; font-weight:600;">
                                <span x-text="editando ? '➕ Agregar más fotos' : '📸 Fotos para reconocimiento facial'"></span>
                                <span x-show="!editando" style="color:var(--danger);"> *</span>
                            </span>
                            <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                                Más ángulos y variaciones = reconocimiento más preciso.
                            </div>
                        </div>
                        <div x-show="!editando">
                            <span style="font-size:12px; font-weight:700; padding:4px 10px; border-radius:20px;"
                                  :style="`background:${fotosConArchivo() >= 5 ? 'var(--success-bg)' : 'var(--danger-bg)'}; color:${fotosConArchivo() >= 5 ? 'var(--success)' : 'var(--danger)'};`"
                                  x-text="`${fotosConArchivo()} / 5 mínimas`"></span>
                        </div>
                    </div>

                    <!-- Slots de fotos -->
                    <div style="display:flex; flex-direction:column; gap:8px; max-height:320px; overflow-y:auto; padding-right:4px;">
                        <template x-for="(slot, i) in fotos" :key="i">
                            <div style="display:flex; gap:10px; align-items:center; background:var(--bg-primary); border:1px solid var(--border); border-radius:10px; padding:10px;">

                                <!-- Preview -->
                                <div style="width:52px; height:52px; border-radius:8px; overflow:hidden; background:var(--border); flex-shrink:0; display:flex; align-items:center; justify-content:center; position:relative;">
                                    <template x-if="slot.preview">
                                        <img :src="slot.preview" style="width:100%; height:100%; object-fit:cover;">
                                    </template>
                                    <template x-if="!slot.preview">
                                        <span style="font-size:22px; opacity:0.35;">📷</span>
                                    </template>
                                    <div x-show="!slot.preview" style="position:absolute; bottom:2px; left:0; right:0; text-align:center; font-size:9px; color:var(--text-muted);"
                                         x-text="`#${i + 1}`"></div>
                                </div>

                                <!-- Input archivo + descripción -->
                                <div style="flex:1; display:flex; flex-direction:column; gap:5px;">
                                    <input type="file" accept="image/jpeg,image/png,image/webp"
                                           style="font-size:12px; color:var(--text-secondary); cursor:pointer; width:100%;"
                                           @change="seleccionarFoto($event, i)">
                                    <input list="foto-descripciones"
                                           type="text"
                                           class="form-control"
                                           style="font-size:12px; padding:5px 8px;"
                                           x-model="fotos[i].descripcion"
                                           :placeholder="`Descripción foto ${i + 1}... (elige o escribe)`">
                                </div>

                                <!-- Quitar slot -->
                                <button type="button"
                                        class="btn btn-danger btn-sm"
                                        style="padding:5px 9px; flex-shrink:0;"
                                        x-show="fotos.length > (editando ? 1 : 5)"
                                        @click="quitarSlot(i)"
                                        title="Quitar este slot">✕</button>
                            </div>
                        </template>
                    </div>

                    <!-- Sugerencias de descripción (datalist) -->
                    <datalist id="foto-descripciones">
                        <option value="Frente · sin accesorios">
                        <option value="Frente · con mascarilla">
                        <option value="Frente · con casco">
                        <option value="Frente · con mascarilla y casco">
                        <option value="Perfil izquierdo">
                        <option value="Perfil derecho">
                        <option value="Ángulo 3/4 derecha">
                        <option value="Ángulo 3/4 izquierda">
                        <option value="Iluminación baja / nocturna">
                        <option value="Con lentes o gafas">
                        <option value="Con gorro o sombrero">
                        <option value="Sonriendo / boca abierta">
                    </datalist>

                    <button type="button"
                            class="btn btn-ghost btn-sm"
                            style="margin-top:8px; width:100%; border-style:dashed;"
                            @click="agregarSlot()">
                        ➕ Agregar otra foto
                    </button>

                    <!-- Guía rápida -->
                    <div x-show="!editando"
                         style="margin-top:10px; padding:10px 12px; background:rgba(59,130,246,0.06); border:1px solid rgba(59,130,246,0.15); border-radius:8px; font-size:11px; color:var(--text-secondary); line-height:1.6;">
                        <strong style="color:var(--accent);">💡 Guía para mejores resultados:</strong><br>
                        📌 Foto 1 — Frente, sin accesorios, buena luz <em>(la más importante)</em><br>
                        📌 Foto 2 — Frente con mascarilla <em>(si aplica EPP)</em><br>
                        📌 Foto 3 — Frente con casco <em>(si aplica EPP)</em><br>
                        📌 Foto 4 — Perfil izquierdo<br>
                        📌 Foto 5 — Perfil derecho o ángulo 3/4
                    </div>
                </div>

                <div class="form-actions" style="margin-top:16px;">
                    <button type="button" class="btn btn-ghost" @click="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" :disabled="guardando">
                        <span x-show="!guardando">💾 Guardar</span>
                        <span x-show="guardando">⏳ Registrando en IA...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function personasApp() {
    return {
        personas: [], meta: {}, loading: false,
        modalAbierto: false, editando: null, guardando: false,
        form: { nombre: '', apellido: '', documento: '', tipo: 'empleado', activo: true, notas: '' },
        fotos: [],
        fotosExistentes: [],
        filtros: { search: '', tipo: '', activo: '' },

        async init() { await this.load(); },

        async load(page = 1) {
            this.loading = true;
            const params = new URLSearchParams({ page, ...this.filtros });
            Object.keys(this.filtros).forEach(k => !this.filtros[k] && params.delete(k));
            try {
                const res  = await fetch(`/api/personas?${params}`, { headers: this.headers() });
                const data = await res.json();
                this.personas = data.data;
                this.meta = { total: data.total, current_page: data.current_page, last_page: data.last_page };
            } catch (e) {} finally { this.loading = false; }
        },

        openModal(p = null) {
            this.editando        = p;
            this.fotosExistentes = p?.fotos_ia ? [...p.fotos_ia] : [];
            // Para nueva persona: 5 slots vacíos; para edición: 1 slot vacío
            const n = p ? 1 : 5;
            this.fotos = Array.from({ length: n }, () => ({ file: null, descripcion: '', preview: null }));
            this.form  = p
                ? { nombre: p.nombre, apellido: p.apellido, documento: p.documento ?? '', tipo: p.tipo, activo: p.activo, notas: p.notas ?? '' }
                : { nombre: '', apellido: '', documento: '', tipo: 'empleado', activo: true, notas: '' };
            this.modalAbierto = true;
        },

        cerrarModal() { this.modalAbierto = false; this.editando = null; },

        agregarSlot() {
            this.fotos.push({ file: null, descripcion: '', preview: null });
        },

        quitarSlot(i) {
            this.fotos.splice(i, 1);
        },

        seleccionarFoto(e, i) {
            const file = e.target.files[0];
            if (!file) { this.fotos[i].file = null; this.fotos[i].preview = null; return; }
            this.fotos[i].file = file;
            const reader = new FileReader();
            reader.onload = ev => { this.fotos[i].preview = ev.target.result; };
            reader.readAsDataURL(file);
        },

        fotosConArchivo() {
            return this.fotos.filter(f => f.file).length;
        },

        async eliminarFotoExistente(i) {
            if (!confirm('¿Eliminar esta foto del sistema de reconocimiento?')) return;
            try {
                const res = await fetch(`/api/personas/${this.editando.id}/fotos/${i}`, {
                    method: 'DELETE', headers: this.headers()
                });
                if (res.ok) { this.fotosExistentes.splice(i, 1); }
            } catch (e) {}
        },

        async guardar() {
            // Validar mínimo 5 fotos para nuevas personas
            const fotosValidas = this.fotos.filter(f => f.file);
            if (!this.editando && fotosValidas.length < 5) {
                alert(`Debes subir al menos 5 fotos para registrar una nueva persona.\nActualmente tienes: ${fotosValidas.length} foto(s) seleccionada(s).`);
                return;
            }

            this.guardando = true;
            const fd = new FormData();
            Object.entries(this.form).forEach(([k, v]) => fd.append(k, v ?? ''));
            fotosValidas.forEach(f => {
                fd.append('fotos[]', f.file);
                fd.append('descripciones[]', f.descripcion);
            });
            if (this.editando) fd.append('_method', 'PUT');

            const url = this.editando ? `/api/personas/${this.editando.id}` : '/api/personas';
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || '') },
                    body: fd,
                });
                if (res.ok) {
                    this.cerrarModal();
                    await this.load();
                } else {
                    const err = await res.json();
                    alert(err.message || JSON.stringify(err.errors ?? 'Error al guardar'));
                }
            } catch (e) {
                alert('Error de conexión');
            } finally {
                this.guardando = false;
            }
        },

        async eliminar(p) {
            if (!confirm(`¿Eliminar a ${p.nombre} ${p.apellido}?\nSe eliminarán sus fotos y datos de reconocimiento.`)) return;
            await fetch(`/api/personas/${p.id}`, { method: 'DELETE', headers: this.headers() });
            await this.load();
        },

        headers() { return { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || ''), 'Accept': 'application/json' }; },
        tipoBadge(tipo) { return { empleado: 'badge-info', residente: 'badge-success', visitante: 'badge-gray', restringido: 'badge-danger' }[tipo] || 'badge-gray'; },
        tipoLabel(tipo) { return { empleado: '👷 Empleado', residente: '🏠 Residente', visitante: '🙋 Visitante', restringido: '🚫 Restringido' }[tipo] || tipo; },
        formatFecha(ts) { return ts ? new Date(ts).toLocaleDateString('es') : '–'; },
    };
}
</script>
@endpush
