/* Regresión: el cierre de sesión real envía DELETE sin cuerpo JSON. Solo pruebas locales. */
const {chromium} = require('playwright');
const assert = require('node:assert/strict');
const base = process.env.PISCO_BASE_URL || 'http://127.0.0.1:8086';
if (!['127.0.0.1', 'localhost'].includes(new URL(base).hostname)) throw Error('Solo pruebas locales.');
(async () => {
  const browser = await chromium.launch({executablePath: '/usr/bin/chromium', args: ['--no-sandbox']});
  try {
    const context = await browser.newContext();
    await context.route('https://**/*', route => route.abort());
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const login = async () => {
      await page.goto(base + '/#/admin');
      await page.locator('#lg-u').fill('admin');
      await page.locator('#lg-p').fill('PruebaOriginal-2026!');
      await page.locator('[data-login] button[type=submit]').click();
      await page.waitForFunction(() => window.PORTAL_BOOTSTRAP?.user?.rol === 'admin');
    };
    const loggedOut = async () => {
      await page.locator('#lg-u').waitFor();
      const state = await (await context.request.get(base + '/index.php?route=api%2Fportal')).json();
      assert.equal(state.user, null);
      assert.equal((await context.request.get(base + '/accounts.php', {maxRedirects: 0})).status(), 403);
      await page.reload();
      await page.locator('#lg-u').waitFor();
    };
    await login();
    const endpoint = base + '/index.php?route=api%2Fsession';
    assert.equal((await context.request.delete(endpoint, {headers: {'X-CSRF-TOKEN': 'incorrecto'}})).status(), 419);
    assert.ok((await (await context.request.get(base + '/index.php?route=api%2Fportal')).json()).user);
    const csrf = await page.evaluate(() => window.PORTAL_BOOTSTRAP.csrf);
    assert.equal((await context.request.delete(endpoint, {headers: {'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json'}})).status(), 200);
    await page.reload();
    await loggedOut();
    console.log('OK DELETE sin cuerpo cierra sesión; CSRF incorrecto conserva la protección.');
    for (const route of ['dashboard', 'ix-alumnos']) {
      await login();
      await page.goto(base + '/#/admin/' + route);
      if (await page.locator('[data-logout]').count()) await page.locator('[data-logout]').click();
      else {
        await page.locator('[data-act=account]').click();
        await page.locator('[data-act=logout]').click();
      }
      await loggedOut();
      console.log('OK botón de salida en ' + route + ': sesión revocada y panel inaccesible tras recargar.');
    }
    assert.deepEqual(errors, []);
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
