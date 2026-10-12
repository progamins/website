import type { APIRoute } from 'astro';
import { consumirTokenReset, cambiarPassword } from '../../../../lib/auth';
import { addNotificacionUsuario } from '../../../../lib/notificaciones';

/**
 * Establece la nueva contraseña con un token de recuperación válido.
 * El token es de un solo uso: al consumirlo queda invalidado y se
 * cierran todas las sesiones activas del usuario (seguridad de cuenta).
 */
export const POST: APIRoute = async ({ request, redirect }) => {
  const form = await request.formData();
  const token = (form.get('token') ?? '').toString();
  const password = (form.get('password') ?? '').toString();
  const password2 = (form.get('password2') ?? '').toString();

  const volverConError = (msg: string) =>
    redirect(`/recuperar?error=${encodeURIComponent(msg)}`);

  if (!token || !/^[a-f0-9]{64}$/.test(token)) return volverConError('El enlace de recuperación no es válido.');
  if (password.length < 8) return volverConError('La nueva contraseña debe tener al menos 8 caracteres.');
  if (password !== password2) return volverConError('Las contraseñas no coinciden.');

  const usuarioId = consumirTokenReset(token);
  if (!usuarioId) return volverConError('El enlace ha caducado o ya se utilizó. Solicita uno nuevo.');

  const r = cambiarPassword(usuarioId, password);
  if (!r.ok) return volverConError(r.error ?? 'No se pudo actualizar la contraseña.');

  // Aviso de seguridad: el usuario debe saber que su contraseña cambió
  addNotificacionUsuario({
    usuario_id: usuarioId,
    tipo: 'seguridad',
    titulo: 'Contraseña actualizada',
    mensaje: 'Tu contraseña se cambió correctamente y se cerraron todas tus sesiones. Si no fuiste tú, contacta con soporte.',
    enlace: '/login',
  });

  return redirect('/login?ok=' + encodeURIComponent('Contraseña actualizada. Inicia sesión con tu nueva contraseña.'));
};
