import type { APIRoute } from 'astro';
import fs from 'node:fs';
import path from 'node:path';
import { db, slugify } from '../../../lib/db';

async function guardarArchivo(file: File, subdir: string, prefix: string): Promise<string | null> {
  if (!file || file.size === 0) return null;
  const dir = path.join(process.cwd(), 'public', 'uploads', subdir);
  fs.mkdirSync(dir, { recursive: true });
  const ext = (path.extname(file.name) || '.png').toLowerCase().replace(/[^\w.]/g, '');
  const filename = `${prefix}_${Date.now()}${ext}`;
  fs.writeFileSync(path.join(dir, filename), Buffer.from(await file.arrayBuffer()));
  return `/uploads/${subdir}/${filename}`;
}

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const id = form.get('id') ? Number(form.get('id')) : null;
  const nombre = (form.get('nombre') ?? '').toString().trim();
  if (!nombre) return redirect(id ? `/admin/categorias/editar/${id}` : '/admin/categorias');
  const descripcion = (form.get('descripcion') ?? '').toString();

  let imagen = (form.get('imagen_actual') ?? '').toString() || null;
  const imgFile = form.get('imagen') as File | null;
  const imgNew = await guardarArchivo(imgFile, 'categorias', 'cat');
  if (imgNew) imagen = imgNew;

  let icono = (form.get('icono_actual') ?? '').toString() || null;
  const iconFile = form.get('icono') as File | null;
  const iconNew = await guardarArchivo(iconFile, 'categorias', 'ico');
  if (iconNew) icono = iconNew;

  if (id) {
    db.prepare('UPDATE categorias SET nombre=?, descripcion=?, imagen=?, icono=? WHERE id=?')
      .run(nombre, descripcion, imagen, icono, id);
  } else {
    let slug = slugify(nombre);
    const existe = db.prepare('SELECT id FROM categorias WHERE slug = ?').get(slug);
    if (existe) slug = slug + '-' + Date.now().toString().slice(-4);
    db.prepare('INSERT INTO categorias (nombre, slug, descripcion, imagen, icono) VALUES (?,?,?,?,?)')
      .run(nombre, slug, descripcion, imagen, icono);
  }
  return redirect('/admin/categorias');
};