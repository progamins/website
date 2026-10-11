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
  adapter: esVercel
    ? vercel({
        // rolldown (bundler interno de Astro 7) se usa en runtime y su
        // binding nativa de Linux no queda en el bundle por el trazado:
        // forzarla para x64 y arm64 (la función elige según plataforma)
        includeFiles: [
          'node_modules/@rolldown/binding-linux-x64-gnu/package.json',
          'node_modules/@rolldown/binding-linux-x64-gnu/rolldown-binding.linux-x64-gnu.node',
        ],
      })
    : node({ mode: 'standalone' }),
  vite: {
    plugins: [tailwindcss()],
    // better-sqlite3 es un módulo nativo: dejarlo como dependencia externa
    // del servidor (Vite no debe intentar empaquetarlo)
    ssr: { external: ['better-sqlite3'] },
  },
});
