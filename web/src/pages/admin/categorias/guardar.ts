import type { APIRoute } from 'astro';
import fs from 'node:fs';
import path from 'node:path';
import { db, slugify } from '../../../lib/db';

// Mapa MIME → extensión. La extensión NUNCA se toma del nombre original
// (controlado por el cliente); se deriva del MIME verificado.
const MIME_EXT: Record<string, string> = {
  'image/jpeg': 'jpg',
  'image/png': 'png',
  'image/webp': 'webp',
};
const MAX_BYTES = 2 * 1024 * 1024; // 2MB

type ArchivoResultado =
  | null // no se subió nada
  | { ok: true; ruta: string }
  | { ok: false; error: string };

async function guardarArchivo(file: FormDataEntryValue | null, subdir: string, prefix: string): Promise<ArchivoResultado> {
  if (!file) return null;
  if (!(file instanceof File)) return { ok: false, error: 'Archivo inválido' };
  if (file.size === 0) return null;
  const mime = file.type.toLowerCase();
  if (!(mime in MIME_EXT)) return { ok: false, error: 'Formato no permitido: solo JPG, PNG o WEBP' };
  if (file.size > MAX_BYTES) return { ok: false, error: `La imagen ${file.name} supera 2MB` };
  const dir = path.join(process.cwd(), 'public', 'uploads', subdir);
  fs.mkdirSync(dir, { recursive: true });
  const filename = `${prefix}_${Date.now()}.${MIME_EXT[mime]}`;
  fs.writeFileSync(path.join(dir, filename), Buffer.from(await file.arrayBuffer()));
  return { ok: true, ruta: `/uploads/${subdir}/${filename}` };
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
  const imgNew = await guardarArchivo(form.get('imagen'), 'categorias', 'cat');
  if (imgNew && !imgNew.ok) return redirect('/admin/categorias?error=' + encodeURIComponent(imgNew.error));
  if (imgNew?.ok) imagen = imgNew.ruta;

  let icono = (form.get('icono_actual') ?? '').toString() || null;
  const iconNew = await guardarArchivo(form.get('icono'), 'categorias', 'ico');
  if (iconNew && !iconNew.ok) return redirect('/admin/categorias?error=' + encodeURIComponent(iconNew.error));
  if (iconNew?.ok) icono = iconNew.ruta;

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
