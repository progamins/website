/**
 * Integración de correo desacoplada.
 *
 * Proveedor soportado: Resend (https://resend.com) mediante la variable
 * de entorno RESEND_API_KEY. Opcionalmente EMAIL_FROM para el remitente
 * (p. ej. "Chollo & Glam <no-reply@cholloyglam.com>").
 *
 * Si no hay proveedor configurado, enviarEmail() devuelve { ok: false,
 * razon: 'no-configurado' } y NUNCA se afirma que el mensaje se envió.
 * El resto del sistema degrada su comportamiento de forma honesta:
 * el token de recuperación se genera igualmente, pero el enlace solo
 * se registra en el log del servidor (visibe en desarrollo) hasta
 * que se configure el proveedor en producción.
 */

export interface ResultadoEmail {
  ok: boolean;
  razon?: 'no-configurado' | 'fallo-envio';
  detalle?: string;
}

const RESEND_API_KEY = (import.meta.env.RESEND_API_KEY ?? '').toString();
const EMAIL_FROM = (import.meta.env.EMAIL_FROM ?? 'Chollo & Glam <onboarding@resend.dev>').toString();

export function emailConfigurado(): boolean {
  return RESEND_API_KEY.length > 0;
}

export async function enviarEmail(opts: {
  to: string;
  asunto: string;
  html: string;
  texto?: string;
}): Promise<ResultadoEmail> {
  if (!emailConfigurado()) return { ok: false, razon: 'no-configurado' };

  try {
    const res = await fetch('https://api.resend.com/emails', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${RESEND_API_KEY}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        from: EMAIL_FROM,
        to: [opts.to],
        subject: opts.asunto,
        html: opts.html,
        text: opts.texto,
      }),
    });
    if (!res.ok) {
      console.error('[email] fallo de Resend:', res.status, await res.text().catch(() => ''));
      return { ok: false, razon: 'fallo-envio' };
    }
    return { ok: true };
  } catch (e) {
    console.error('[email] error de red:', e instanceof Error ? e.message : e);
    return { ok: false, razon: 'fallo-envio' };
  }
}

/** Plantilla de recuperación de contraseña con la identidad de la marca. */
export function plantillaRecuperacion(nombre: string, enlace: string): string {
  const saludo = nombre ? `Hola, ${nombre}` : 'Hola';
  return `<!doctype html>
<html lang="es"><body style="margin:0;background:#F5F0E8;font-family:Georgia,serif;color:#29231F;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#FFFFFF;border:1px solid #E8E0D6;border-radius:16px;">
        <tr><td style="padding:32px 32px 8px;">
          <p style="margin:0;font-size:11px;letter-spacing:4px;text-transform:uppercase;color:#7E5E28;">Chollo & Glam</p>
          <h1 style="margin:12px 0 0;font-size:26px;color:#4A2029;">Recupera tu contraseña</h1>
        </td></tr>
        <tr><td style="padding:8px 32px 0;">
          <p style="margin:0;font-size:15px;line-height:1.6;color:#766B60;">${saludo}. Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. Este enlace caduca en 1 hora y solo puede usarse una vez.</p>
        </td></tr>
        <tr><td style="padding:24px 32px;">
          <a href="${enlace}" style="display:inline-block;background:#4A2029;color:#F5F0E8;text-decoration:none;font-family:Arial,sans-serif;font-size:14px;font-weight:600;padding:14px 32px;border-radius:999px;">Crear nueva contraseña</a>
        </td></tr>
        <tr><td style="padding:0 32px 32px;">
          <p style="margin:0;font-size:13px;line-height:1.6;color:#766B60;">Si no solicitaste este cambio, puedes ignorar este mensaje: tu contraseña seguirá siendo la misma.</p>
          <p style="margin:16px 0 0;font-size:12px;color:#B0A79B;">© Chollo & Glam · Joyería y artesanía peruana</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>`;
}
