/* Guardado durante carga lenta, fallo de carga y recuperación sin perder los campos editados. */
const {chromium}=require('playwright');const assert=require('node:assert/strict');
const base=process.env.PISCO_BASE_URL||'http://127.0.0.1:8086';if(!['127.0.0.1','localhost'].includes(new URL(base).hostname))throw Error('Solo pruebas locales.');
(async()=>{const b=await chromium.launch({executablePath:'/usr/bin/chromium',args:['--no-sandbox']});let release=()=>{};try{
 const c=await b.newContext();await c.route('https://**/*',r=>r.abort());const p=await c.newPage(),errors=[];p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/#/admin');await p.locator('#lg-u').fill('admin');await p.locator('#lg-p').fill('PruebaOriginal-2026!');await p.locator('[data-login] button[type=submit]').click();await p.waitForFunction(()=>window.PORTAL_BOOTSTRAP?.user&&typeof DB!=='undefined'&&DB?.config&&typeof IX!=='undefined'&&IX);
 await p.goto(base+'/#/admin/inversiones/inv2');const original=await p.evaluate(()=>structuredClone(DB.inversiones.find(x=>x.id==='inv2')));
 const title='Inversión con adjunto verificado '+Date.now();await p.locator('[name=titulo]').fill(title);
 const file={name:'informe-qa.pdf',mimeType:'application/pdf',buffer:Buffer.from('%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF')};
 let requested;const started=new Promise(r=>requested=r);const gate=new Promise(r=>release=r);
 await p.route('**/index.php?route=api%2Farchivos',async r=>{requested();await gate;await r.continue().catch(()=>{});});
 await p.locator('[data-ff=documento]').setInputFiles(file);await started;
 await p.getByRole('button',{name:'Guardar cambios',exact:true}).click();assert.equal(await p.locator('[data-cf]').count(),1);assert.ok((await p.locator('.toast').allTextContents()).some(x=>x.includes('Espera a que termine')));
 assert.equal(await p.evaluate(async()=>{const j=await(await fetch(portalURL('/api/portal'))).json();return j.data.inversiones.find(x=>x.id==='inv2').titulo;}),original.titulo);
 release();await p.waitForFunction(()=>EDITOR_UPLOADS.size===0&&ADM.work?.documento?.fileId);await p.unroute('**/index.php?route=api%2Farchivos');
 assert.equal(await p.locator('[name=titulo]').inputValue(),title);await p.getByRole('button',{name:'Guardar cambios',exact:true}).click();await p.waitForURL('**/#/admin/inversiones');await p.reload();
 let stored=await p.evaluate(()=>DB.inversiones.find(x=>x.id==='inv2'));assert.equal(stored.titulo,title);assert.ok(stored.documento.fileId);assert.equal((await c.request.get(base+stored.documento.url)).status(),200);
 console.log('OK guardado bloqueado durante la carga; después persisten título y PDF real.');
 await p.evaluate(original=>{DB.inversiones=DB.inversiones.map(x=>x.id===original.id?original:x);if(!saveDB())throw Error('No se restituyó la inversión');},original);
 await p.goto(base+'/#/admin/inversiones/inv2');await p.locator('[name=titulo]').fill(title+' recuperado');
 await p.route('**/index.php?route=api%2Farchivos',r=>r.fulfill({status:503,contentType:'text/html',body:'Servicio temporalmente no disponible'}));
 await p.locator('[data-ff=documento]').setInputFiles(file);await p.waitForFunction(()=>EDITOR_UPLOADS.size===0);await p.locator('.toast').filter({hasText:'El servidor no devolvió una respuesta válida'}).waitFor();
 assert.equal(await p.locator('[name=titulo]').inputValue(),title+' recuperado');assert.equal(await p.evaluate(()=>ADM.work.documento),null);
 await p.unroute('**/index.php?route=api%2Farchivos');await p.locator('[data-ff=documento]').setInputFiles(file);await p.waitForFunction(()=>EDITOR_UPLOADS.size===0&&ADM.work?.documento?.fileId);
 await p.getByRole('button',{name:'Guardar cambios',exact:true}).click();await p.waitForURL('**/#/admin/inversiones');await p.reload();stored=await p.evaluate(()=>DB.inversiones.find(x=>x.id==='inv2'));assert.equal(stored.titulo,title+' recuperado');assert.ok(stored.documento.fileId);
 await p.evaluate(original=>{DB.inversiones=DB.inversiones.map(x=>x.id===original.id?original:x);if(!saveDB())throw Error('No se restituyó la inversión');},original);
 assert.deepEqual(errors,[]);console.log('OK fallo HTML de carga sin error JavaScript; se conserva el formulario y el reintento guarda el archivo.');
 }finally{release();await b.close();}})().catch(e=>{console.error(e);process.exitCode=1});
