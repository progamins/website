import type { APIRoute } from 'astro';
import fs from 'node:fs';
import path from 'node:path';
import { setConfig, getConfig, type Banner } from '../../../lib/db';

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();

  // ===== Logo =====
  const logoArchivo = form.get('logo_archivo') as File | null;
  const quitarLogo = form.get('quitar_logo') === 'on';
  if (logoArchivo && logoArchivo.size > 0) {
    const dir = path.join(process.cwd(), 'public', 'uploads', 'config');
    fs.mkdirSync(dir, { recursive: true });
    const ext = (path.extname(logoArchivo.name) || '.png').toLowerCase().replace(/[^\w.]/g, '');
    const filename = `logo_${Date.now()}${ext}`;
    fs.writeFileSync(path.join(dir, filename), Buffer.from(await logoArchivo.arrayBuffer()));
    setConfig('logo', `/uploads/config/${filename}`);
  } else if (quitarLogo) {
    setConfig('logo', null);
  }

  // ===== Banners (carrusel) =====
  const actuales = Array.isArray(getConfig('banners')) ? getConfig('banners') as Banner[] : [];
  const ids = form.getAll('banner_id').map(String);
  const tipos = form.getAll('banner_tipo').map(String);
  const urls = form.getAll('banner_url').map(String);
  const enlaces = form.getAll('banner_enlace').map(String);
  const quitar = new Set(form.getAll('banner_quitar').map(String));
  const archivos = form.getAll('banner_fondo') as File[];

  const dir = path.join(process.cwd(), 'public', 'uploads', 'config');
  const banners: Banner[] = [];

  for (let i = 0; i < ids.length; i++) {
    if (quitar.has(String(i))) continue;
    const tipo = (tipos[i] ?? 'imagen') as Banner['tipo'];
    const enlace = (enlaces[i] ?? '').trim() || null;
    let fondo: string | null = (urls[i] ?? '').trim() || null;
    const file = archivos[i];

    if (file && file.size > 0) {
      const ext = path.extname(file.name).toLowerCase();
      const tipoAuto: Banner['tipo'] = ['.mp4', '.webm', '.ogg', '.mov'].includes(ext) ? 'video' : 'imagen';
      fs.mkdirSync(dir, { recursive: true });
      const filename = `banner_${Date.now()}_${i}${ext || '.png'}`;
      fs.writeFileSync(path.join(dir, filename), Buffer.from(await file.arrayBuffer()));
      banners.push({ tipo: tipoAuto, fondo: `/uploads/config/${filename}`, enlace });
    } else if (tipo === 'color' || !fondo) {
      banners.push({ tipo: 'color', fondo: null, enlace });
    } else {
      banners.push({ tipo, fondo, enlace });
    }
  }

  // Si no queda ninguno, se mantiene el banner por defecto
  if (!banners.length) {
    banners.push({ tipo: 'imagen', fondo: '/uploads/banners/banner1.svg', enlace: '/novedades' });
  }

  setConfig('banners', banners);
  return redirect('/admin/configuracion?ok=1');
};