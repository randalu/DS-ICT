'use strict';
const fs = require('node:fs');
const path = require('node:path');
const zlib = require('node:zlib');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const css = fs.readFileSync(path.join(root, 'public/assets/site.css'), 'utf8');
const js = fs.readFileSync(path.join(root, 'public/assets/site.js'), 'utf8');
function luminance(hex) {
  const [r, g, b] = hex.match(/[a-f\d]{2}/gi).map((part) => parseInt(part, 16) / 255).map((value) => value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4);
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}
const contrast = [];
for (const [foreground, background] of [['white', 'lagoon'], ['white', 'palm'], ['ink', 'white'], ['ink', 'mist'], ['ink', 'sun'], ['muted', 'white'], ['muted', 'mist'], ['sun', 'lagoon']]) {
  const a = luminance(css.match(new RegExp(`--${foreground}:(#[a-f0-9]{6})`, 'i'))[1]);
  const b = luminance(css.match(new RegExp(`--${background}:(#[a-f0-9]{6})`, 'i'))[1]);
  const ratio = (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
  assert.ok(ratio >= 4.5, `${foreground} on ${background} fails normal text contrast`);
  contrast.push({ foreground, background, ratio: Number(ratio.toFixed(2)) });
}
const assets = {
  cssCompressed: zlib.gzipSync(css).length,
  jsCompressed: zlib.gzipSync(js).length,
  fonts: fs.statSync(path.join(root, 'public/assets/fonts/fraunces-latin.woff2')).size + fs.statSync(path.join(root, 'public/assets/fonts/manrope-latin.woff2')).size,
};
assert.ok(assets.cssCompressed + assets.jsCompressed <= 100 * 1024, 'Initial custom CSS/JS budget');
assert.ok(assets.fonts <= 150 * 1024, 'Font budget');
assert.ok(!css.includes('@import') && !css.includes('https://'), 'No runtime external CSS/font imports');
assert.ok(!/fetch\(|XMLHttpRequest|sendBeacon|localStorage|sessionStorage|document\.cookie/.test(js), 'No services or original-site browser state');
assert.ok(css.includes('prefers-reduced-motion:reduce'), 'Reduced motion CSS');
assert.ok(css.includes(':focus-visible'), 'Visible focus CSS');
fs.mkdirSync(path.join(root, 'output'), { recursive: true });
fs.writeFileSync(path.join(root, 'output/design-checks.json'), JSON.stringify({ result: 'pass', contrast, assets }, null, 2) + '\n');
console.log(`PASS: ${contrast.length} text-colour pairs; CSS/JS ${assets.cssCompressed + assets.jsCompressed} bytes gzip; fonts ${assets.fonts} bytes.`);
