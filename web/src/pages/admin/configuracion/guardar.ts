import type { APIRoute } from 'astro';
import fs from 'node:fs';
import path from 'node:path';
import { setConfig, type Banner } from '../../../lib/db';

// Mapas MIME → extensión. La extensión NUNCA se toma del nombre original
// (controlado por el cliente); se deriva del MIME verificado.
const MIME_IMG: Record<string, string> = {
  'image/jpeg': 'jpg',
  'image/png': 'png',
  'image/webp': 'webp',
};
const MIME_VIDEO: Record<string, string> = {
  'video/mp4': 'mp4',
  'video/webm': 'webm',
};
const MAX_IMG_BYTES = 2 * 1024 * 1024;   // 2MB
const MAX_VIDEO_BYTES = 10 * 1024 * 1024; // 10MB

async function subirArchivo(file: File, i: number | null, prefix: string): Promise<{ ok: true; ruta: string; tipo: 'imagen' | 'video' } | { ok: false; error: string }> {
  const mime = file.type.toLowerCase();
  if (mime in MIME_IMG) {
    if (file.size > MAX_IMG_BYTES) return { ok: false, error: `La imagen ${file.name} supera 2MB` };
    const ext = MIME_IMG[mime];
    const filename = i === null ? `${prefix}_${Date.now()}.${ext}` : `${prefix}_${Date.now()}_${i}.${ext}`;
    const dir = path.join(process.cwd(), 'public', 'uploads', 'config');
    fs.mkdirSync(dir, { recursive: true });
    fs.writeFileSync(path.join(dir, filename), Buffer.from(await file.arrayBuffer()));
    return { ok: true, ruta: `/uploads/config/${filename}`, tipo: 'imagen' };
  }
  if (mime in MIME_VIDEO) {
    if (file.size > MAX_VIDEO_BYTES) return { ok: false, error: `El video ${file.name} supera 10MB` };
    const ext = MIME_VIDEO[mime];
    const filename = i === null ? `${prefix}_${Date.now()}.${ext}` : `${prefix}_${Date.now()}_${i}.${ext}`;
    const dir = path.join(process.cwd(), 'public', 'uploads', 'config');
    fs.mkdirSync(dir, { recursive: true });
    fs.writeFileSync(path.join(dir, filename), Buffer.from(await file.arrayBuffer()));
    return { ok: true, ruta: `/uploads/config/${filename}`, tipo: 'video' };
  }
  return { ok: false, error: `Formato no permitido: ${file.name} (solo JPG, PNG, WEBP, MP4 o WEBM)` };
}

/** Solo enlaces internos (/ruta) o https:// absolutos — bloquea javascript:, data:, etc. */
function enlaceSeguro(valor: string): string | null {
  const v = valor.trim();
  if (!v) return null;
  if (v.startsWith('/') && !v.startsWith('//')) return v;
  try {
    const u = new URL(v);
    if (u.protocol === 'https:') return u.toString();
  } catch { /* no es URL válida */ }
  return null;
}

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user || user.rol === 'cliente') return redirect('/login');
  const form = await request.formData();

  // ===== Logo =====
  const logoArchivo = form.get('logo_archivo') as File | null;
  const quitarLogo = form.get('quitar_logo') === 'on';
  if (logoArchivo && logoArchivo.size > 0) {
    const r = await subirArchivo(logoArchivo, null, 'logo');
    if (!r.ok) return redirect('/admin/configuracion?error=' + encodeURIComponent(r.error));
    setConfig('logo', r.ruta);
  } else if (quitarLogo) {
    setConfig('logo', null);
  }

  // ===== Banners (carrusel) =====
  const ids = form.getAll('banner_id').map(String);
  const tipos = form.getAll('banner_tipo').map(String);
  const urls = form.getAll('banner_url').map(String);
  const enlaces = form.getAll('banner_enlace').map(String);
  const quitar = new Set(form.getAll('banner_quitar').map(String));
  const archivos = form.getAll('banner_fondo') as File[];

  const banners: Banner[] = [];

  for (let i = 0; i < ids.length; i++) {
    if (quitar.has(String(i))) continue;
    const tipo = (tipos[i] ?? 'imagen') as Banner['tipo'];
    const enlace = enlaceSeguro(enlaces[i] ?? '');
    let fondo: string | null = (urls[i] ?? '').trim() || null;
    const file = archivos[i];

    if (file && file.size > 0) {
      const r = await subirArchivo(file, i, 'banner');
      if (!r.ok) return redirect('/admin/configuracion?error=' + encodeURIComponent(r.error));
      banners.push({ tipo: r.tipo, fondo: r.ruta, enlace });
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
