import type { APIRoute } from 'astro';
import { db } from '../../../lib/db';
import { crearTokenReset, estaBloqueadoLogin, registrarFalloLogin } from '../../../lib/auth';
import { enviarEmail, plantillaRecuperacion, emailConfigurado } from '../../../lib/email';
import { siteUrl } from '../../../lib/site';

/**
 * Solicitud de recuperación de contraseña.
 * Responde SIEMPRE con el mismo mensaje genérico: no revela si la
 * cuenta existe (anti-enumeración). El token se crea solo si la cuenta
 * existe y el envío del correo es degradación honesta: si no hay
 * proveedor configurado, el enlace queda registrado en el log del
 * servidor (útil en desarrollo) pero nunca se afirma un envío.
 */
export const POST: APIRoute = async ({ request, redirect, clientAddress }) => {
  const form = await request.formData();
  const email = (form.get('email') ?? '').toString().toLowerCase().trim();

  // Rate limiting: evita sondeo masivo de correos
  const ip = clientAddress ?? 'desconocida';
  const claveRate = `reset:${ip}`;
  if (estaBloqueadoLogin(claveRate)) {
    return redirect('/recuperar?error=' + encodeURIComponent('Demasiadas solicitudes. Espera unos minutos e inténtalo de nuevo.'));
  }

  if (!email || email.length > 254 || !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
    registrarFalloLogin(claveRate);
    return redirect('/recuperar?error=' + encodeURIComponent('Introduce un correo electrónico válido.'));
  }

  const usuario = db.prepare('SELECT id, nombre FROM usuarios WHERE email = ? AND activo = 1').get(email) as
    | { id: number; nombre: string }
    | undefined;

  // La respuesta es idéntica exista o no la cuenta
  if (usuario) {
    const token = crearTokenReset(usuario.id);
    const enlace = `${siteUrl()}/recuperar/${token}`;
    if (emailConfigurado()) {
      const r = await enviarEmail({
        to: email,
        asunto: 'Recupera tu contraseña · Chollo & Glam',
        html: plantillaRecuperacion(usuario.nombre, enlace),
        texto: `Restablece tu contraseña: ${enlace} (caduca en 1 hora, un solo uso).`,
      });
      if (!r.ok) console.error('[recuperar] no se pudo enviar el email:', r.razon);
    } else {
      // Sin proveedor de correo: registra el enlace en el log del servidor.
      // La página informa honestamente de que la entrega no está activa.
      console.log(`[recuperar] EMAIL NO CONFIGURADO — enlace de reset para ${email}: ${enlace}`);
    }
  }

  return redirect('/recuperar?enviado=1');
};
