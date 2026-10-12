import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';

/**
 * Actualiza los datos del perfil del usuario autenticado.
 * Solo nombre, apellidos y teléfono: el email y el rol no se cambian aquí.
 */
export const POST: APIRoute = async ({ request, locals, redirect }) => {
  const user = locals.user;
  if (!user) return redirect('/login?next=/cuenta');

  const form = await request.formData();
  const nombre = (form.get('nombre') ?? '').toString().trim();
  const apellidos = (form.get('apellidos') ?? '').toString().trim();
  const telefono = (form.get('telefono') ?? '').toString().trim();

  if (!nombre || nombre.length > 50) {
    return redirect('/cuenta?error=' + encodeURIComponent('El nombre es obligatorio (máx. 50 caracteres).'));
  }
  if (apellidos.length > 50) {
    return redirect('/cuenta?error=' + encodeURIComponent('Los apellidos no pueden superar 50 caracteres.'));
  }
  if (telefono && (telefono.length > 30 || !/^[+\d\s().-]+$/.test(telefono))) {
    return redirect('/cuenta?error=' + encodeURIComponent('El teléfono no es válido.'));
  }

  db.prepare('UPDATE usuarios SET nombre = ?, apellidos = ?, telefono = ? WHERE id = ?')
    .run(nombre, apellidos || null, telefono || null, user.id);

  return redirect('/cuenta?ok=' + encodeURIComponent('Perfil actualizado.'));
};
