import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(path.dirname(process.argv[1]), '..');
const pub = path.join(root, 'public', 'uploads');

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

function svg({ w, h, body }) {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">${body}</svg>`;
}
function grad(id, from, to, deg = 135) {
  return `<linearGradient id="${id}" gradientTransform="rotate(${deg} .5 .5)"><stop offset="0%" stop-color="${from}"/><stop offset="100%" stop-color="${to}"/></linearGradient>`;
}
// Motivo "gema" común para dar coherencia de marca
function gem(cx, cy, s, op = 0.28) {
  return `<g transform="translate(${cx},${cy}) scale(${s})" stroke="#ffffff" stroke-opacity="${op}" stroke-width="2" fill="none"><path d="M50 20 L86 56 L50 120 L14 56 Z"/><path d="M14 56 L50 56 L86 56"/><path d="M50 20 L50 56 L50 120"/></g>`;
}

// ===== 20 categorías =====
const cats = [
  { slug: 'collares', name: 'Collares', c1: '#8a6a2a', c2: '#d4af6a' },
  { slug: 'aretes', name: 'Aretes', c1: '#a54a26', c2: '#e2a06e' },
  { slug: 'pulseras', name: 'Pulseras', c1: '#23615a', c2: '#7fc0b6' },
  { slug: 'anillos', name: 'Anillos', c1: '#4a3b63', c2: '#a795c9' },
  { slug: 'relojes', name: 'Relojes', c1: '#274b63', c2: '#6fa8c9' },
  { slug: 'accesorios', name: 'Accesorios', c1: '#7a2e52', c2: '#c984a8' },
  { slug: 'ropa-hombre', name: 'Ropa Hombre', c1: '#1f2a44', c2: '#5a6b8a' },
  { slug: 'ropa-mujer', name: 'Ropa Mujer', c1: '#7a2256', c2: '#c2659c' },
  { slug: 'calzado', name: 'Calzado', c1: '#5c3a22', c2: '#a97c52' },
  { slug: 'bolsos', name: 'Bolsos y Carteras', c1: '#4a4a22', c2: '#8a9a5a' },
  { slug: 'tecnologia', name: 'Tecnología', c1: '#11384a', c2: '#4aa3c0' },
  { slug: 'mandos', name: 'Mandos y Gaming', c1: '#2a2f66', c2: '#7a7fd4' },
  { slug: 'hogar', name: 'Hogar y Decoración', c1: '#4a4238', c2: '#a8947c' },
  { slug: 'mascotas', name: 'Mascotas', c1: '#1d5c32', c2: '#5fbf7c' },
  { slug: 'belleza', name: 'Belleza y Cuidado', c1: '#8a2a4a', c2: '#d97a9c' },
  { slug: 'deportes', name: 'Deportes', c1: '#8a4a1f', c2: '#e0924a' },
  { slug: 'bebes', name: 'Bebés y Niños', c1: '#234a7a', c2: '#7aa8d4' },
  { slug: 'gafas', name: 'Gafas y Lentes', c1: '#3a2a66', c2: '#a48fd4' },
  { slug: 'papeleria', name: 'Papelería y Oficina', c1: '#23334a', c2: '#6a84b8' },
  { slug: 'ofertas', name: 'Ofertas', c1: '#9e3a1f', c2: '#e0724e' },
];

fs.mkdirSync(path.join(pub, 'categorias'), { recursive: true });
fs.mkdirSync(path.join(pub, 'productos'), { recursive: true });

for (const c of cats) {
  // Tarjeta de categoría (portrait)
  const catBody = `
    ${grad('g', c.c1, c.c2)}
    <rect width="600" height="800" fill="url(#g)"/>
    <circle cx="500" cy="120" r="180" fill="#000000" opacity="0.06"/>
    <circle cx="90" cy="700" r="160" fill="#ffffff" opacity="0.05"/>
    ${gem(0, 40, 0.9, 0.30)}
    <text x="300" y="580" font-family="'Segoe UI',system-ui,sans-serif" font-size="46" font-weight="600" fill="#ffffff" text-anchor="middle">${esc(c.name)}</text>
    <text x="300" y="112" font-family="'Segoe UI',system-ui,sans-serif" font-size="15" letter-spacing="4" fill="#ffffff" fill-opacity="0.7" text-anchor="middle">CHOLLO &amp; GLAM</text>`;
  fs.writeFileSync(path.join(pub, 'categorias', c.slug + '.svg'), svg({ w: 600, h: 800, body: catBody }));

  // Imagen de producto (square) para categorías sin foto real
  const prodBody = `
    ${grad('g', c.c1, c.c2, 150)}
    <rect width="600" height="600" fill="url(#g)"/>
    <circle cx="520" cy="100" r="150" fill="#ffffff" opacity="0.07"/>
    <circle cx="80" cy="540" r="130" fill="#000000" opacity="0.08"/>
    ${gem(0, 0, 0.9, 0.32)}
    <text x="300" y="460" font-family="'Segoe UI',system-ui,sans-serif" font-size="34" font-weight="600" fill="#ffffff" text-anchor="middle">${esc(c.name)}</text>
    <text x="300" y="500" font-family="'Segoe UI',system-ui,sans-serif" font-size="14" letter-spacing="4" fill="#ffffff" fill-opacity="0.75" text-anchor="middle">CHOLLO &amp; GLAM</text>`;
  fs.writeFileSync(path.join(pub, 'productos', c.slug + '.svg'), svg({ w: 600, h: 600, body: prodBody }));

  // Icono circular (square, motivo centrado)
  const icoBody = `
    ${grad('g', c.c1, c.c2, 140)}
    <rect width="600" height="600" fill="url(#g)"/>
    <circle cx="300" cy="300" r="230" fill="#ffffff" opacity="0.08"/>
    ${gem(0, 50, 1.1, 0.34)}
    <text x="300" y="520" font-family="'Segoe UI',system-ui,sans-serif" font-size="30" font-weight="600" fill="#ffffff" text-anchor="middle">${esc(c.name)}</text>`;
  fs.writeFileSync(path.join(pub, 'categorias', c.slug + '-icon.svg'), svg({ w: 600, h: 600, body: icoBody }));
}

// ===== Colecciones (3) =====
const cols = [
  { slug: 'andes-dorados', name: 'Andes Dorados', c1: '#8a6a2a', c2: '#d4af6a' },
  { slug: 'amazonia', name: 'Amazonía Mística', c1: '#14532d', c2: '#5bb98a' },
  { slug: 'costa', name: 'Costa Brillante', c1: '#0f4c5c', c2: '#6bb3c4' },
];
fs.mkdirSync(path.join(pub, 'colecciones'), { recursive: true });
for (const c of cols) {
  const body = `
    ${grad('g', c.c1, c.c2, 150)}
    <rect width="800" height="1000" fill="url(#g)"/>
    <circle cx="680" cy="200" r="240" fill="#ffffff" opacity="0.08"/>
    <circle cx="120" cy="840" r="200" fill="#000000" opacity="0.08"/>
    ${gem(0, 0, 0.7, 0.30)}
    <text x="400" y="480" font-family="'Segoe UI',system-ui,sans-serif" font-size="54" font-weight="600" fill="#ffffff" text-anchor="middle">${esc(c.name)}</text>
    <text x="400" y="540" font-family="'Segoe UI',system-ui,sans-serif" font-size="17" letter-spacing="5" fill="#ffffff" fill-opacity="0.75" text-anchor="middle">COLECCIÓN CHOLLO &amp; GLAM</text>`;
  fs.writeFileSync(path.join(pub, 'colecciones', c.slug + '.svg'), svg({ w: 800, h: 1000, body }));
}

// ===== Banners (1600x900) =====
const banners = [
  { f: 'banner1.svg', c1: '#15120c', c2: '#4a3a1e', t1: 'Joyas que cuentan', t2: 'la historia de los Andes' },
  { f: 'banner2.svg', c1: '#0f2a26', c2: '#1f5b54', t1: 'Ediciones limitadas', t2: 'artesanía peruana' },
  { f: 'banner3.svg', c1: '#3a1a10', c2: '#a54a26', t1: 'Ofertas de temporada', t2: 'hasta -35%' },
];
fs.mkdirSync(path.join(pub, 'banners'), { recursive: true });
for (const b of banners) {
  const body = `
    ${grad('g', b.c1, b.c2, 120)}
    <rect width="1600" height="900" fill="url(#g)"/>
    <circle cx="1360" cy="180" r="340" fill="#ffffff" opacity="0.06"/>
    <circle cx="180" cy="760" r="260" fill="#000000" opacity="0.10"/>
    <rect x="120" y="120" width="1360" height="660" rx="24" fill="#ffffff" opacity="0.04"/>
    <text x="140" y="120" font-family="'Segoe UI',system-ui,sans-serif" font-size="26" letter-spacing="6" fill="#e6c27a" text-anchor="start">CHOLLO &amp; GLAM</text>
    <text x="140" y="420" font-family="'Segoe UI',system-ui,sans-serif" font-size="88" font-weight="700" fill="#ffffff" text-anchor="start">${esc(b.t1)}</text>
    <text x="140" y="540" font-family="'Segoe UI',system-ui,sans-serif" font-size="56" font-weight="300" fill="#e6c27a" text-anchor="start">${esc(b.t2)}</text>
    <g transform="translate(1250,560)"><circle cx="0" cy="0" r="120" fill="#e6c27a" opacity="0.25"/><circle cx="0" cy="0" r="84" fill="#ffffff" opacity="0.3"/><circle cx="0" cy="0" r="50" fill="#e6c27a"/></g>`;
  fs.writeFileSync(path.join(pub, 'banners', b.f), svg({ w: 1600, h: 900, body }));
}

// ===== Placeholder genérico =====
const ph = `
  ${grad('g', '#2a2018', '#4a3a1e', 140)}
  <rect width="600" height="600" fill="url(#g)"/>
  <circle cx="300" cy="300" r="120" fill="#ffffff" opacity="0.06"/>
  <text x="300" y="300" font-family="'Segoe UI',system-ui,sans-serif" font-size="120" font-weight="700" fill="#e6c27a" text-anchor="middle">C</text>
  <text x="300" y="380" font-family="'Segoe UI',system-ui,sans-serif" font-size="20" letter-spacing="4" fill="#ffffff" fill-opacity="0.8" text-anchor="middle">CHOLLO &amp; GLAM</text>`;
fs.mkdirSync(path.join(root, 'public', 'assets'), { recursive: true });
fs.writeFileSync(path.join(root, 'public', 'assets', 'placeholder.svg'), svg({ w: 600, h: 600, body: ph }));

console.log(`Assets generados: ${cats.length} categorías, ${cols.length} colecciones, ${banners.length} banners, placeholder.`);