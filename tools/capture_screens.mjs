// Takes the app screenshots the site's "Ko'rinishi" section shows, in all three languages.
//
//   node tools/capture_screens.mjs
//
// Needs the app served against the invented workshop in mebel_crm_shots (see
// seed_screenshot_db.php) on http://localhost:8770 — the `app-screenshots` entry in
// D:\Mebel_CRM\.claude\launch.json. Drives a headless Edge over the DevTools protocol, so
// nothing beyond Node itself is installed. Writes media/screens/<screen>-<lang>.webp.

import { spawn } from 'node:child_process';
import { mkdirSync, readFileSync, writeFileSync, mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const OUT = join(HERE, '..', 'media', 'screens');
const APP = 'http://localhost:8770/';
const EDGE = 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe';
const PORT = 9333;
const SCREENS = ['director', 'board', 'analytics', 'finance'];
const LANGS = ['uz', 'ru', 'en'];
const W = 1440, H = 900, SCALE = 1.4;

const creds = readFileSync(join(HERE, 'screenshot-db.local'), 'utf8');
const username = /username: (\S+)/.exec(creds)[1];
const password = /password: (\S+)/.exec(creds)[1];

const profile = mkdtempSync(join(tmpdir(), 'ustaxona-shots-'));
const edge = spawn(EDGE, ['--headless=new', `--remote-debugging-port=${PORT}`, `--user-data-dir=${profile}`,
  '--no-first-run', '--hide-scrollbars', `--window-size=${W},${H}`, 'about:blank'], { stdio: 'ignore' });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

let target;
for (let i = 0; i < 50 && !target; i++) {
  await sleep(200);
  try { target = (await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json()).find((t) => t.type === 'page'); } catch {}
}
if (!target) { edge.kill(); throw new Error('Edge did not start'); }

const ws = new WebSocket(target.webSocketDebuggerUrl);
await new Promise((r) => ws.addEventListener('open', r, { once: true }));
let seq = 0;
const pending = new Map();
const waiters = [];
ws.addEventListener('message', (e) => {
  const m = JSON.parse(e.data);
  if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); }
  if (m.method) for (const w of waiters.splice(0)) w(m) || waiters.push(w);
});
const send = (method, params = {}) => new Promise((resolve, reject) => {
  const id = ++seq;
  pending.set(id, (m) => (m.error ? reject(new Error(`${method}: ${m.error.message}`)) : resolve(m.result)));
  ws.send(JSON.stringify({ id, method, params }));
});
const loaded = () => new Promise((r) => waiters.push((m) => m.method === 'Page.loadEventFired' && (r(), true)));
const evaluate = async (expression) => (await send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true })).result.value;
const go = async (url) => { const l = loaded(); await send('Page.navigate', { url }); await l; };

await send('Page.enable');
await send('Runtime.enable');
await send('Emulation.setDeviceMetricsOverride', { width: W, height: H, deviceScaleFactor: SCALE, mobile: false });
// The first-run tour would sit over every screen, and the sidebar's "prototype · …" label is a
// leftover the product still ships; neither belongs in a picture of the product at work.
await send('Page.addScriptToEvaluateOnNewDocument', { source: `
  document.addEventListener('DOMContentLoaded', function () {
    var s = document.createElement('style');
    s.textContent = '.tour-veil,.tour-spot,.tour-card{display:none!important} .brand small{display:none!important}';
    document.head.appendChild(s);
  });` });

await go(APP + 'login.html');
await evaluate(`(async () => {
  localStorage.setItem('crm_currency', 'UZS');
  localStorage.setItem('crm_theme', 'dark');
  const u = document.querySelector('input[type=text]'), p = document.querySelector('input[type=password]');
  u.value = ${JSON.stringify(username)}; p.value = ${JSON.stringify(password)};
  u.form.requestSubmit();
})()`);
await sleep(2500);
if (!(await evaluate('location.pathname')).includes('director')) throw new Error('sign-in failed');

mkdirSync(OUT, { recursive: true });
for (const lang of LANGS) {
  await evaluate(`localStorage.setItem('crm_lang', '${lang}')`);
  for (const screen of SCREENS) {
    await go(APP + screen + '.html');
    await sleep(3000); // data requests, then the count-up and chart entrances
    const { data } = await send('Page.captureScreenshot', { format: 'webp', quality: 84 });
    writeFileSync(join(OUT, `${screen}-${lang}.webp`), Buffer.from(data, 'base64'));
    console.log(`media/screens/${screen}-${lang}.webp`);
  }
}

ws.close();
edge.kill();
await sleep(500);
try { rmSync(profile, { recursive: true, force: true }); } catch {}
