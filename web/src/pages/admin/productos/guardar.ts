import type { APIRoute } from 'astro';
import fs from 'node:fs';
import path from 'node:path';
import { db, slugify } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const id = form.get('id') ? Number(form.get('id')) : null;
  const nombre = (form.get('nombre') ?? '').toString().trim();
  if (!nombre) return redirect('/admin/productos');

  let slug = slugify(nombre);
  const conflict = id
    ? db.prepare('SELECT id FROM productos WHERE slug = ? AND id != ?').get(slug, id)
    : db.prepare('SELECT id FROM productos WHERE slug = ?').get(slug);
  if (conflict) slug = slug + '-' + Date.now().toString().slice(-4);

  let imagen = (form.get('imagen_actual') ?? '').toString() || null;
  const file = form.get('imagen') as File | null;
  if (file && file.size > 0) {
    // Validación de subida: MIME real del archivo, tamaño máximo y extensión
    // derivada del MIME (nunca del nombre original, que controla el cliente).
    const MIME_EXT: Record<string, string> = {
      'image/jpeg': 'jpg',
      'image/png': 'png',
      'image/webp': 'webp',
    };
    const mime = file.type.toLowerCase();
    const MAX_BYTES = 2 * 1024 * 1024; // 2MB
    if (!(mime in MIME_EXT)) {
      return redirect('/admin/productos?error=' + encodeURIComponent('Formato no permitido: solo JPG, PNG o WEBP'));
    }
    if (file.size > MAX_BYTES) {
      return redirect('/admin/productos?error=' + encodeURIComponent('La imagen supera el máximo de 2MB'));
    }
    const uploadDir = path.join(process.cwd(), 'public', 'uploads', 'productos');
    fs.mkdirSync(uploadDir, { recursive: true });
    const filename = `${Date.now()}.${MIME_EXT[mime]}`;
    const buffer = Buffer.from(await file.arrayBuffer());
    fs.writeFileSync(path.join(uploadDir, filename), buffer);
    imagen = '/uploads/productos/' + filename;
  }

  const data = {
    nombre, slug,
    categoria_id: form.get('categoria_id') ? Number(form.get('categoria_id')) : null,
    coleccion_id: form.get('coleccion_id') ? Number(form.get('coleccion_id')) : null,
    precio_actual: Number(form.get('precio_actual')) || 0,
    precio_original: form.get('precio_original') ? Number(form.get('precio_original')) || null : null,
    descripcion: (form.get('descripcion') ?? '').toString(),
    etiqueta: (form.get('etiqueta') ?? '').toString() || null,
    tipo: (form.get('tipo') ?? '').toString() || null,
    stock: Number(form.get('stock')) || 0,
    destacado: form.get('destacado') ? 1 : 0,
    activo: form.get('activo') ? 1 : 0,
    imagen,
  };

  if (id) {
    db.prepare(`UPDATE productos SET nombre=?, slug=?, categoria_id=?, coleccion_id=?, precio_actual=?, precio_original=?, descripcion=?, etiqueta=?, tipo=?, stock=?, destacado=?, activo=?, imagen=? WHERE id=?`)
      .run(data.nombre, data.slug, data.categoria_id, data.coleccion_id, data.precio_actual, data.precio_original, data.descripcion, data.etiqueta, data.tipo, data.stock, data.destacado, data.activo, data.imagen, id);
  } else {
    db.prepare(`INSERT INTO productos (nombre, slug, categoria_id, coleccion_id, precio_actual, precio_original, descripcion, etiqueta, tipo, stock, destacado, activo, imagen) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)`)
      .run(data.nombre, data.slug, data.categoria_id, data.coleccion_id, data.precio_actual, data.precio_original, data.descripcion, data.etiqueta, data.tipo, data.stock, data.destacado, data.activo, data.imagen);
  }
  return redirect('/admin/productos');
};