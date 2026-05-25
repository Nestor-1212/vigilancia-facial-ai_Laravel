@extends('layouts.app')

@section('title', 'Personas — VigiFacial')
@section('page-title', '👥 Personas Registradas')

@section('content')
<div x-data="personasApp()" x-init="init()">

    <!-- Filtros y acciones -->
    <div style="display:flex; gap:12px; align-items:center; margin-bottom:20px; flex-wrap:wrap;">
        <input type="text" class="form-control" style="max-width:260px;" placeholder="🔍 Buscar por nombre o documento..." x-model.debounce.400ms="filtros.search" @input="load()">
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
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Foto Ref.</th>
                    <th>Registrado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading">
                    <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:30px;">⏳ Cargando...</td></tr>
                </template>
                <template x-for="p in personas" :key="p.id">
                    <tr>
                        <td>
                            <div style="font-weight:600;" x-text="`${p.nombre} ${p.apellido}`"></div>
                            <div style="font-size:11px; color:var(--text-muted);" x-text="p.notas ?? ''"></div>
                        </td>
                        <td x-text="p.documento ?? '—'" style="font-family:monospace; font-size:12px;"></td>
                        <td>
                            <span class="badge" :class="tipoBadge(p.tipo)" x-text="p.tipo"></span>
                        </td>
                        <td>
                            <span class="badge" :class="p.activo ? 'badge-success' : 'badge-gray'" x-text="p.activo ? 'Activo' : 'Inactivo'"></span>
                        </td>
                        <td>
                            <template x-if="p.foto_referencia">
                                <img :src="`/storage/${p.foto_referencia}`" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid var(--border);" alt="Foto">
                            </template>
                            <template x-if="!p.foto_referencia">
                                <div style="width:36px; height:36px; border-radius:50%; background:var(--bg-primary); border:2px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:16px;">👤</div>
                            </template>
                        </td>
                        <td x-text="formatFecha(p.created_at)" style="font-size:12px; color:var(--text-secondary);"></td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <button class="btn btn-ghost btn-sm" @click="openModal(p)">✏️</button>
                                <button class="btn btn-danger btn-sm" @click="eliminar(p)">🗑️</button>
                            </div>
                        </td>
                    </tr>
                </template>
                <template x-if="!loading && personas.length === 0">
                    <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:30px;">No se encontraron personas.</td></tr>
                </template>
            </tbody>
        </table>

        <!-- Paginación -->
        <div style="display:flex; align-items:center; gap:12px; padding:16px 0 0; border-top:1px solid var(--border); margin-top:12px;">
            <span style="font-size:13px; color:var(--text-secondary);" x-text="`${meta.total ?? 0} personas`"></span>
            <div style="margin-left:auto; display:flex; gap:6px;">
                <button class="btn btn-ghost btn-sm" :disabled="meta.current_page <= 1" @click="pagina(meta.current_page - 1)">← Ant</button>
                <span style="font-size:13px; padding:5px 10px;" x-text="`${meta.current_page ?? 1} / ${meta.last_page ?? 1}`"></span>
                <button class="btn btn-ghost btn-sm" :disabled="meta.current_page >= meta.last_page" @click="pagina(meta.current_page + 1)">Sig →</button>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" :class="{ open: modalAbierto }">
        <div class="modal" @click.stop>
            <div class="modal-title" x-text="editando ? '✏️ Editar Persona' : '➕ Nueva Persona'"></div>

            <form @submit.prevent="guardar()">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input class="form-control" x-model="form.nombre" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellido *</label>
                        <input class="form-control" x-model="form.apellido" required>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Documento</label>
                        <input class="form-control" x-model="form.documento" placeholder="8-123-456">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo *</label>
                        <select class="form-control" x-model="form.tipo" required>
                            <option value="empleado">Empleado</option>
                            <option value="residente">Residente</option>
                            <option value="visitante">Visitante</option>
                            <option value="restringido">Restringido 🚫</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Foto de referencia</label>
                    <input type="file" class="form-control" accept="image/*" @change="seleccionarFoto($event)">
                    <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">Esta foto se usará para el reconocimiento facial.</div>
                </div>
                <div class="form-group" x-show="editando">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" x-model="form.activo">
                        <span class="form-label" style="margin:0;">Persona activa</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea class="form-control" x-model="form.notas" rows="2" placeholder="Información adicional..."></textarea>
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
function personasApp() {
    return {
        personas: [], meta: {}, loading: false,
        modalAbierto: false, editando: null, guardando: false,
        form: { nombre: '', apellido: '', documento: '', tipo: 'empleado', activo: true, notas: '' },
        fotoFile: null,
        filtros: { search: '', tipo: '', activo: '' },

        async init() { await this.load(); },

        async load(page = 1) {
            this.loading = true;
            const params = new URLSearchParams({ page, ...this.filtros });
            Object.keys(this.filtros).forEach(k => !this.filtros[k] && params.delete(k));
            try {
                const res = await fetch(`/api/personas?${params}`, { headers: this.headers() });
                const data = await res.json();
                this.personas = data.data;
                this.meta = { total: data.total, current_page: data.current_page, last_page: data.last_page };
            } catch (e) {} finally { this.loading = false; }
        },

        pagina(p) { this.load(p); },

        openModal(p = null) {
            this.editando = p;
            this.fotoFile = null;
            this.form = p ? { nombre: p.nombre, apellido: p.apellido, documento: p.documento ?? '', tipo: p.tipo, activo: p.activo, notas: p.notas ?? '' } : { nombre: '', apellido: '', documento: '', tipo: 'empleado', activo: true, notas: '' };
            this.modalAbierto = true;
        },
        cerrarModal() { this.modalAbierto = false; this.editando = null; },

        seleccionarFoto(e) { this.fotoFile = e.target.files[0]; },

        async guardar() {
            this.guardando = true;
            const fd = new FormData();
            Object.entries(this.form).forEach(([k, v]) => fd.append(k, v ?? ''));
            if (this.fotoFile) fd.append('foto', this.fotoFile);
            if (this.editando) fd.append('_method', 'PUT');

            const url = this.editando ? `/api/personas/${this.editando.id}` : '/api/personas';
            try {
                const res = await fetch(url, { method: this.editando ? 'POST' : 'POST', headers: { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || '') }, body: fd });
                if (res.ok) { this.cerrarModal(); await this.load(); }
            } catch (e) {} finally { this.guardando = false; }
        },

        async eliminar(p) {
            if (!confirm(`¿Eliminar a ${p.nombre} ${p.apellido}?`)) return;
            await fetch(`/api/personas/${p.id}`, { method: 'DELETE', headers: this.headers() });
            await this.load();
        },

        headers() { return { 'Authorization': 'Bearer ' + (localStorage.getItem('token') || ''), 'Accept': 'application/json' }; },
        tipoBadge(tipo) { return { empleado: 'badge-info', residente: 'badge-success', visitante: 'badge-gray', restringido: 'badge-danger' }[tipo] || 'badge-gray'; },
        formatFecha(ts) { return ts ? new Date(ts).toLocaleDateString('es') : '–'; },
    };
}
</script>
@endpush
