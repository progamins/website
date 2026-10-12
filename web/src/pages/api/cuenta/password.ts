import type { APIRoute } from 'astro';
import { COOKIE_NAME } from '../../../lib/auth';
import { verificarPasswordActual, cambiarPasswordMantenerSesion } from '../../../lib/auth';
import { addNotificacionUsuario } from '../../../lib/notificaciones';

/**
 * Cambia la contraseña del usuario autenticado.
 * Requiere la contraseña actual. Se invalidan las demás sesiones activas
 * (la sesión en uso se conserva) y se emite un aviso de seguridad.
 */
export const POST: APIRoute = async ({ request, locals, cookies, redirect }) => {
  const user = locals.user;
  if (!user) return redirect('/login?next=/cuenta');

  const form = await request.formData();
  const actual = (form.get('actual') ?? '').toString();
  const nueva = (form.get('nueva') ?? '').toString();
  const nueva2 = (form.get('nueva2') ?? '').toString();

  const error = (msg: string) => redirect('/cuenta?error=' + encodeURIComponent(msg));

  if (!verificarPasswordActual(user.id, actual)) {
    return error('La contraseña actual no es correcta.');
  }
  if (nueva.length < 8) return error('La nueva contraseña debe tener al menos 8 caracteres.');
  if (nueva !== nueva2) return error('Las contraseñas nuevas no coinciden.');
  if (nueva === actual) return error('La nueva contraseña debe ser diferente de la actual.');

  const tokenActual = cookies.get(COOKIE_NAME)?.value;
  if (!tokenActual) return error('Tu sesión ha expirado. Inicia sesión de nuevo.');

  const r = cambiarPasswordMantenerSesion(user.id, nueva, tokenActual);
  if (!r.ok) return error(r.error ?? 'No se pudo cambiar la contraseña.');

  addNotificacionUsuario({
    usuario_id: user.id,
    tipo: 'seguridad',
    titulo: 'Contraseña actualizada',
    mensaje: 'Tu contraseña se cambió desde tu perfil. Se cerraron las demás sesiones. Si no fuiste tú, contacta con soporte.',
    enlace: '/cuenta',
  });

  return redirect('/cuenta?ok=' + encodeURIComponent('Contraseña actualizada.'));
};
