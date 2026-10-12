// Browser verification with synthetic data only; never connects to the LMS databases.
import { createServer } from 'node:http';
import { readFile, mkdir, mkdtemp, writeFile } from 'node:fs/promises';
import { resolve, extname, join } from 'node:path';
import { tmpdir } from 'node:os';
import { spawn } from 'node:child_process';
import assert from 'node:assert/strict';

const root = process.cwd(), publicRoot = resolve(root, 'public');
const manifest = JSON.parse(await readFile(join(publicRoot, 'build/manifest.json'), 'utf8'));
const entry = manifest['resources/js/app.js'];
const employee = { user: 'fixture-admin', nama: 'Admin Pengujian', role: 'admin', idgrup: 'JBT-032', status: 'login' };
const students = Array.from({ length: 19 }, (_, i) => ({ id: i + 1, username: `karyawan-${i + 1}`, name: `Karyawan Contoh ${i + 1}`, role: 'student' }));
const questions = Array.from({ length: 13 }, (_, i) => ({ id: i + 1, bank_id: 1, question: `Bagaimana prosedur pemeriksaan barang jaminan pada langkah ${i + 1}?`, type: 'multiple_choice', weight: 10, difficulty: 'easy', is_used: i % 2 === 0, options: [{ id: 1, text: 'Periksa dokumen', is_correct: true }, { id: 2, text: 'Lewati pemeriksaan', is_correct: false }], category: 'Operasional' }));
const batches = Array.from({ length: 8 }, (_, i) => ({ id: i + 1, name: i ? `Pelatihan Cabang ${i + 1}` : 'Onboarding Karyawan Oktober', description: 'Pengenalan prosedur gadai dan layanan nasabah.', starts_on: '2026-10-01', ends_on: '2026-10-31', status: ['active', 'draft', 'completed'][i % 3], version: 1, member_count: 12, course_count: 2 }));
const courses = [{ id: 1, name: 'Dasar Operasional Gadai' }, { id: 2, name: 'Pelayanan Nasabah' }];
const historyRow = { id: 1, title: 'Evaluasi Dasar Gadai', name: 'Karyawan Contoh 1', username: 'karyawan-1', batch_name: batches[0].name, batch_id: 1, recorded_version: 1, attempt_number: 1, started_at: '2026-10-08 09:00:00', submitted_at: '2026-10-08 09:18:00', status: 'SUBMITTED', score: 85, score_visible: true, pending_review: false };
const pages = { '/quiz/manage': ['quiz/QuizManage', 'quizManage'], '/batch-pelatihan': ['training-batches/index', 'trainingBatches'], '/riwayat-belajar': ['learning-history/index', 'learningHistory'] };
let role = 'admin';
const server = createServer(async (req, res) => {
    try {
        const url = new URL(req.url, 'http://localhost');
        if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/assets/')) {
            const file = resolve(publicRoot, '.' + url.pathname);
            if (!file.startsWith(publicRoot + '\\') && !file.startsWith(publicRoot + '/')) { res.writeHead(403).end(); return; }
            res.setHeader('Content-Type', ({ '.js': 'text/javascript', '.css': 'text/css', '.svg': 'image/svg+xml', '.png': 'image/png', '.woff2': 'font/woff2' })[extname(file)] || 'application/octet-stream');
            res.end(await readFile(file)); return;
        }
        const auth = { ...employee, role, idgrup: role === 'admin' ? 'JBT-032' : 'STAFF' };
        let data;
        if (url.pathname === '/session') data = { auth };
        else if (url.pathname === '/api/quiz/metadata') data = { user: { role }, students, courses, classes: [], banks: [{ id: 1, name: 'Operasional Gadai' }, { id: 2, name: 'Pelayanan Nasabah' }] };
        else if (url.pathname === '/api/quiz/questions') data = questions;
        else if (url.pathname === '/api/training-batches') data = { batches, can_manage: role === 'admin' };
        else if (url.pathname === '/api/training-batches/options') data = { courses, employees: students.slice(0, 4) };
        else if (url.pathname === '/api/training-batches/my-learning') data = { batches: [{ ...batches[0], state: 'ready', courses: courses.map(c => ({ ...c, total: 5, completed: 2, can_open: true })) }] };
        else if (/^\/api\/training-batches\/\d+$/.test(url.pathname)) data = { batch: batches[0], members: students.slice(0, 2), courses, accessible_course_ids: [1, 2], progress: students.slice(0, 2).map(s => ({ ...s, course_name: courses[0].name, course_id: 1, total: 5, completed: 2, percent: 40 })) };
        else if (url.pathname === '/api/learning-history') data = { data: [historyRow], current_page: 1, last_page: 1 };
        else if (url.pathname === '/api/learning-history/1') data = { ...historyRow, student: historyRow.name, history_notice: 'Soal dan materi direkam saat percobaan dimulai.', questions: [{ id: 1, question: questions[0].question, type: 'multiple_choice', options: questions[0].options, selected_options: [1], points: 10, weight: 10, explanation: 'Dokumen perlu diverifikasi terlebih dahulu.' }], materials: [], grade_history: [] };
        else if (url.pathname.startsWith('/api/')) data = {};
        if (data !== undefined) { res.setHeader('Content-Type', 'application/json'); res.end(JSON.stringify(data)); return; }
        const [component, name] = pages[url.pathname] || pages['/quiz/manage'];
        const page = { component, props: { auth, title: 'LMS Gadai Mulia', csrfToken: 'fixture', flash: {}, fullscreen: false, routeContext: { name, params: {}, query: Object.fromEntries(url.searchParams), meta: {} } }, url: url.pathname + url.search, version: 'fixture' };
        if (req.headers['x-inertia']) { res.setHeader('X-Inertia', 'true'); res.setHeader('Content-Type', 'application/json'); res.end(JSON.stringify(page)); return; }
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        res.end(`<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="fixture"><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">${(entry.css || []).map(css => `<link rel="stylesheet" href="/build/${css}">`).join('')}</head><body><div id="app" data-page="${JSON.stringify(page).replaceAll('&', '&amp;').replaceAll('"', '&quot;')}"></div><script type="module" src="/build/${entry.file}"></script></body></html>`);
    } catch { res.writeHead(404).end(); }
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
const profile = await mkdtemp(join(tmpdir(), 'lms-history-browser-'));
const browser = spawn(process.env.EDGE_PATH || 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank'], { windowsHide: true, stdio: 'ignore' });
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
let ws;
const errors = [];
try {
    let port;
    for (let n = 0; n < 100; n++) { try { port = (await readFile(join(profile, 'DevToolsActivePort'), 'utf8')).split('\n')[0]; break; } catch { await sleep(100); } }
    assert.ok(port, 'Headless Edge must start');
    const target = (await (await fetch(`http://127.0.0.1:${port}/json/list`)).json()).find(t => t.type === 'page');
    ws = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => { ws.onopen = resolve; ws.onerror = reject; });
    let nextId = 0; const pending = new Map();
    ws.onmessage = event => { const data = JSON.parse(event.data); if (data.id) { const p = pending.get(data.id); pending.delete(data.id); data.error ? p.reject(data.error) : p.resolve(data.result); } if (data.method === 'Runtime.exceptionThrown') errors.push(data.params.exceptionDetails.exception?.description || data.params.exceptionDetails.text); };
    const send = (method, params = {}) => new Promise((resolve, reject) => { const id = ++nextId; pending.set(id, { resolve, reject }); ws.send(JSON.stringify({ id, method, params })); });
    const evaluate = async expression => { const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true }); if (result.exceptionDetails) throw Error(result.exceptionDetails.text); return result.result.value; };
    const waitFor = async expression => { for (let n = 0; n < 120; n++) { if (await evaluate(expression)) return; await sleep(100); } throw Error('Timed out: ' + expression); };
    const click = text => evaluate(`(() => { const el = [...document.querySelectorAll('button')].find(el => el.textContent.trim() === ${JSON.stringify(text)}); if (!el) throw Error('Missing button'); el.click(); })()`);
    await send('Runtime.enable'); await send('Page.enable');
    const artifactDir = resolve(root, 'storage/app/ui-checks'); await mkdir(artifactDir, { recursive: true });
    const screenshot = async name => { await evaluate('Promise.race([document.fonts.ready.then(() => true), new Promise(resolve => setTimeout(resolve, 3000))])'); const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false }); await writeFile(join(artifactDir, name + '.png'), Buffer.from(shot.data, 'base64')); };
    for (const width of [1440, 390]) {
        await send('Emulation.setDeviceMetricsOverride', { width, height: 960, deviceScaleFactor: 1, mobile: width < 600 });
        await send('Page.navigate', { url: origin + '/quiz/manage' });
        await waitFor(`document.querySelectorAll('.question-card').length > 0`);
        assert.equal(await evaluate('document.documentElement.scrollWidth > window.innerWidth'), false, 'Bank page overflow');
        await screenshot(`bank-${width}`);
        await click('Peserta'); await waitFor(`document.querySelectorAll('.participant-card').length === 12`);
        await click('Berikutnya'); assert.equal(await evaluate(`document.querySelectorAll('.participant-card').length`), 7);
        await click('Riwayat'); await waitFor(`document.querySelector('.history-row') !== null`);
        await click('Lihat detail'); await waitFor(`document.querySelector('dialog[open] .review-question') !== null`);
        await screenshot(`history-${width}`); await click('Tutup ✕');
        await send('Page.navigate', { url: origin + '/batch-pelatihan' });
        await waitFor(`document.querySelectorAll('.cards .card').length === 6`);
        assert.equal(await evaluate('document.documentElement.scrollWidth > window.innerWidth'), false, 'Batch page overflow');
        await screenshot(`batch-${width}`);
        await evaluate(`document.querySelector('.cards .card').click()`); await waitFor(`document.querySelector('.detail-panel table') !== null`);
        await click('Riwayat kuis'); await waitFor(`document.querySelector('.history-row') !== null`);
        await click('Edit batch'); await waitFor(`document.querySelector('[role="dialog"]') !== null`);
        await screenshot(`batch-form-${width}`); await click('Batal');
    }
    role = 'student'; await send('Page.navigate', { url: origin + '/batch-pelatihan' });
    await waitFor(`document.querySelector('.learning .start') !== null`);
    assert.equal(await evaluate(`document.querySelector('.learning .start').getAttribute('href')`), '/student/kelas/1?batch_id=1');
    assert.equal(await evaluate('document.documentElement.scrollWidth > window.innerWidth'), false, 'Student page overflow');
    await screenshot('batch-student-390');
    assert.deepEqual(errors, [], 'Browser runtime exceptions');
    console.log('Browser checks passed: bank/peserta/history, batch detail/edit, mobile/desktop overflow, student batch context. Synthetic data only.');
    console.log('Screenshots: storage/app/ui-checks');
    await send('Browser.close');
} finally {
    ws?.close(); browser.kill(); server.close();
}
