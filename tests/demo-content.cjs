/* Verifica la identidad de demostración y las relaciones de sus datos iniciales. */
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const data = JSON.parse(fs.readFileSync(path.join(root, 'portal-private/data/portal.json'), 'utf8'));
const view = fs.readFileSync(path.join(root, 'portal-private/views/portal.php'), 'utf8');
assert.equal(data.config.inst.nombre, 'INSTITUTO PUBLICO DE ICA');
assert.equal(data.academico.cfg.nombre, data.config.inst.nombre);
assert.equal(data.config.inst.logo, data.academico.cfg.docs.logoIzq.src);
function inspect(value) {
  if (Array.isArray(value)) return value.forEach(inspect);
  if (value && typeof value === 'object') return Object.values(value).forEach(inspect);
  if (typeof value !== 'string' || value === 'pisco') return; // Identificador interno del tema.
  assert.ok(!/pisco|tasayco|arones|laurente/i.test(value), value.slice(0, 120));
  for (const email of value.matchAll(/[\w.+-]+@([\w.-]+\.[a-z]{2,})/gi))
    assert.equal(email[1], 'instituto-ica.example');
}
inspect(data);
const exists = (rows, id) => rows.some(row => row.id === id);
for (const row of data.jerarquica) assert.ok(exists(data.personas, row.personaId));
for (const row of data.programas) assert.ok(exists(data.personas, row.coordinadorId));
for (const row of data.certificados) assert.equal(row.estado, 'Válido');
assert.equal(data.config.home.accesos.length, 4);
assert.ok(data.config.home.accesos.every(x => x.titulo && x.enlace.startsWith('#/servicios/')));
assert.ok(data.config.home.beneficios.length <= 8);
assert.equal(new Set(data.personas.map(x => x.dni)).size, data.personas.length);
assert.ok(data.personas.every(x => /^[0-9]{8}$/.test(x.dni) && ['Nombrado', 'Contratado'].includes(x.condicion)));
const a = data.academico;
for (const row of a.estudiantes) assert.ok(exists(a.programas, row.prog));
for (const row of a.certs) {
  const student = a.estudiantes.find(x => x.id === row.est);
  const module = a.modulos.find(x => x.id === row.mod);
  assert.ok(student && module && student.prog === module.prog);
  assert.ok(data.certificados.some(x => x.codigo === row.num && x.dni === student.dni));
}
for (const row of a.notas) {
  assert.ok(exists(a.estudiantes, row.est));
  assert.ok(row.nota >= 0 && row.nota <= 20);
}
for (const row of a.titulos) {
  assert.ok(a.estudiantes.some(x => x.id === row.est && x.estado === 'Titulado'));
  assert.ok(row.culm <= row.tit);
  assert.ok(data.certificados.some(x => x.codigo === row.cod));
}
for (const row of a.ofertas) assert.ok(exists(a.empresas, row.emp));
assert.equal(data.config.whatsapp.activo, false);
assert.ok(Object.values(data.config.redes).every(x => x === ''));
for (const script of view.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g))
  if (!script[1].includes('<?')) new vm.Script(script[1]);
console.log('Identidad, contactos, referencias académicas y sintaxis JavaScript: OK');
