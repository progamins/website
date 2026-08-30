import type { APIRoute } from 'astro';
import fs from 'node:fs';
import path from 'node:path';
import { db, slugify } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();
  const nombre = (form.get('nombre') ?? '').toString().trim();
  if (!nombre) return redirect('/admin/categorias');
  const descripcion = (form.get('descripcion') ?? '').toString();
  let imagen: string | null = null;
  const file = form.get('imagen') as File | null;
  if (file && file.size > 0) {
    const dir = path.join(process.cwd(), 'public', 'uploads', 'categorias');
    fs.mkdirSync(dir, { recursive: true });
    const filename = Date.now() + '_' + file.name.replace(/[^\w.\-]+/g, '_');
    fs.writeFileSync(path.join(dir, filename), Buffer.from(await file.arrayBuffer()));
    imagen = '/uploads/categorias/' + filename;
  }
  db.prepare('INSERT INTO categorias (nombre, slug, descripcion, imagen) VALUES (?,?,?,?)').run(nombre, slugify(nombre), descripcion, imagen);
  return redirect('/admin/categorias');
};