// @ts-check
import { defineConfig } from 'astro/config';
import node from '@astrojs/node';
import vercel from '@astrojs/vercel';
import tailwindcss from '@tailwindcss/vite';

// Adaptador dual: Vercel detecta su entorno y usa su adapter nativo;
// en local/hosting tradicional se mantiene el servidor Node standalone.
const esVercel = process.env.VERCEL === '1' || !!process.env.VERCEL_REGION;

export default defineConfig({
  output: 'server',
  adapter: esVercel ? vercel() : node({ mode: 'standalone' }),
  vite: {
    plugins: [tailwindcss()],
    // better-sqlite3 es un módulo nativo: dejarlo como dependencia externa
    // del servidor (Vite no debe intentar empaquetarlo)
    ssr: { external: ['better-sqlite3'] },
  },
});
