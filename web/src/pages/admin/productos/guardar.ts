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
    const uploadDir = path.join(process.cwd(), 'public', 'uploads', 'productos');
    fs.mkdirSync(uploadDir, { recursive: true });
    const safe = file.name.replace(/[^\w.\-]+/g, '_');
    const filename = Date.now() + '_' + safe;
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