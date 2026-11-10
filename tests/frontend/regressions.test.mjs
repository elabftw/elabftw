import assert from 'node:assert/strict';
import { test } from 'node:test';
import { readFileSync } from 'node:fs';
import { stripTypeScriptTypes } from 'node:module';
import vm from 'node:vm';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = process.env.ELAB_TEST_SOURCE_ROOT ?? path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
// Load the actual TypeScript modules, substituting only browser/external boundaries.
async function load(entry, globals = {}, mocks = {}) {
  const context = vm.createContext({ Response, Headers, Request, Blob, FormData, URLSearchParams, console, ...globals });
  const cache = new Map();
  async function moduleFor(name) {
    if (cache.has(name)) return cache.get(name);
    const source = mocks[name] ?? stripTypeScriptTypes(readFileSync(path.join(root, 'src/ts', name + '.ts'), 'utf8'), { mode: 'transform' });
    const mod = new vm.SourceTextModule(source, { context, identifier: name });
    cache.set(name, mod);
    await mod.link(spec => moduleFor(spec.replace(/^\.\//, '')));
    return mod;
  }
  const module = await moduleFor(entry);
  await module.evaluate();
  return module.namespace;
}
const filenames = [
  [null, 'fallback.txt'],
  ['attachment; filename=plain.csv', 'plain.csv'],
  ['attachment; filename="a.csv"; size="12"', 'a.csv'],
  ['attachment; filename="a;b.csv"', 'a;b.csv'],
  ['attachment; FILENAME = "UPPER.csv"', 'UPPER.csv'],
  ["attachment; filename=ascii.txt; filename*=UTF-8''caf%C3%A9.txt", 'café.txt'],
  ["attachment; filename*=UTF-8'en'caf%C3%A9.txt", 'café.txt'],
  ["attachment; filename*=UTF-8''%ZZ; filename=safe.txt", 'safe.txt'],
  ['attachment; filename="../report.csv"', 'report.csv'],
  ['attachment; filename=".."', 'fallback.txt'],
  ['attachment; filename=""', 'fallback.txt'],
  ['attachment; filename="control\u0000.csv"', 'control.csv'],
];
for (const [header, expected] of filenames) {
  test(`download filename ${JSON.stringify(header)}`, async () => {
    const { downloadFilename } = await load('download');
    assert.equal(downloadFilename(header, 'fallback.txt'), expected);
  });
}
function browser(clickFails = false) {
  const events = [];
  const timers = [];
  return {
    events, timers,
    document: {
      querySelector: () => ({ getAttribute: () => 'synthetic-csrf' }),
      createElement: () => ({ click() { events.push('click'); if (clickFails) throw new Error('click failed'); }, remove() { events.push('remove'); } }),
      body: { appendChild() { events.push('append'); } },
    },
    window: { URL: { createObjectURL() { events.push('create'); return 'blob:test'; }, revokeObjectURL() { events.push('revoke'); } }, setTimeout(fn) { timers.push(fn); } },
  };
}
for (const failure of [false, true]) {
  test(`download releases resources after click, failure=${failure}`, async () => {
    const b = browser(failure);
    const { downloadBlob } = await load('download', b);
    if (failure) assert.throws(() => downloadBlob(new Blob(), 'x'), /click failed/);
    else downloadBlob(new Blob(), 'x');
    assert.deepEqual(b.events, ['create', 'append', 'click', 'remove']);
    b.timers[0]();
    assert.equal(b.events.at(-1), 'revoke');
  });
}
async function apiFixture(fetch) {
  const b = browser();
  const notifications = [];
  const { Api } = await load('Apiv2.class', { ...b, fetch, notifications }, {
    misc: 'export const getNewIdFromPostRequest = () => 1;',
    notify: 'export const notify = { error: (...args) => notifications.push(args), success: () => notifications.push(["success"]) };',
  });
  return { api: new Api(), ...b, notifications };
}
test('GET encodes parameters and preserves existing query', async () => {
  const f = await apiFixture(async (url) => {
    const u = new URL(url, 'https://test.invalid');
    assert.equal(u.searchParams.get('existing'), '1');
    assert.equal(u.searchParams.get('q'), 'a+b & # café');
    assert.equal(u.searchParams.has('notifOnError'), false);
    return new Response('{}');
  });
  const params = Object.freeze({ q: 'a+b & # café', notifOnError: 0 });
  await f.api.get('items?existing=1', params);
  assert.equal(params.notifOnError, 0);
});
test('POST flags do not mutate the caller and are absent from JSON body', async () => {
  const f = await apiFixture(async (_url, options) => {
    assert.deepEqual(JSON.parse(options.body), { title: 'synthetic' });
    return new Response('{}', { status: 201 });
  });
  const params = Object.freeze({ title: 'synthetic', notifOnSaved: 0, notifOnError: 0 });
  await f.api.post('items', params);
  assert.equal(f.notifications.length, 0);
});
test('FormData passes through without a JSON Content-Type', async () => {
  const data = new FormData(); data.set('file', 'synthetic'); data.set('notifOnSaved', '0');
  const f = await apiFixture(async (_url, options) => {
    assert.equal(options.body.get('file'), 'synthetic');
    assert.equal(options.body.has('notifOnSaved'), false);
    assert.equal(options.headers['Content-Type'], undefined);
    return new Response('{}', { status: 201 });
  });
  await f.api.post('items', data);
  assert.equal(data.get('notifOnSaved'), '0');
  assert.equal(f.notifications.length, 0);
});
for (const body of ['<html>proxy unavailable</html>', '', 'null', '{}', '{"message":42}', '{"message":"","description":""}']) {
  test(`preserves HTTP status for invalid error body ${JSON.stringify(body)}`, async () => {
    const f = await apiFixture(async () => new Response(body, { status: 502, statusText: 'Bad Gateway' }));
    await assert.rejects(f.api.get('items'), e => e.status === 502 && e.message === 'HTTP 502: Bad Gateway');
    assert.equal(f.notifications.length, 1);
  });
}
test('preserves API description when message is empty', async () => {
  const f = await apiFixture(async () => new Response('{"message":"","description":"Not allowed"}', { status: 403 }));
  await assert.rejects(f.api.get('items'), e => e.status === 403 && e.message === 'Not allowed');
});
test('download waits for response and click', async () => {
  let release;
  const f = await apiFixture(() => new Promise(resolve => { release = resolve; }));
  let finished = false;
  const pending = f.api.getBlob('export', 'x.csv').then(() => { finished = true; });
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(finished, false);
  release(new Response('data', { headers: { 'Content-Disposition': 'attachment; filename=x.csv' } }));
  await pending;
  assert.equal(finished, true);
  assert.ok(f.events.includes('click'));
});
test('download rejects failed requests and reports them exactly once', async () => {
  const f = await apiFixture(async () => new Response('failed', { status: 500 }));
  await assert.rejects(f.api.getBlob('export', 'x.csv'), e => e.status === 500);
  assert.equal(f.notifications.length, 1);
  assert.equal(f.events.length, 0);
});
test('network errors retain their identity', async () => {
  const error = new TypeError('network failed');
  const f = await apiFixture(async () => { throw error; });
  await assert.rejects(f.api.get('items'), e => e === error);
});
for (const [name, overrides, expected] of [
  ['normal key', {}, 1],
  ['IME composing', { isComposing: true }, 0],
  ['legacy IME', { keyCode: 229 }, 0],
  ['held key', { repeat: true }, 0],
  ['consumed event', { defaultPrevented: true }, 0],
  ['nested editor', { editable: true }, 0],
  ['input', { tag: 'INPUT' }, 0],
  ['textarea', { tag: 'TEXTAREA' }, 0],
  ['select', { tag: 'SELECT' }, 0],
]) {
  test(`keyboard ${name}`, async () => {
    class Element { constructor() { this.tagName = overrides.tag ?? 'SPAN'; this.isContentEditable = overrides.editable ?? false; } hasAttribute() { return false; } }
    const listeners = {};
    const { assignKey } = await load('keymaster', { HTMLElement: Element, document: { addEventListener: (name, fn) => { listeners[name] = fn; } }, window: { addEventListener() {} } });
    let invoked = 0;
    assignKey('x', () => { invoked++; });
    listeners.keydown({ keyCode: 88, target: new Element(), shiftKey: false, altKey: false, ctrlKey: false, metaKey: false, ...overrides });
    assert.equal(invoked, expected);
  });
}
