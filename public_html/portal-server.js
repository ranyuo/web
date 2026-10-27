/* Persistencia y autorización PHP/MySQL. Los datos del navegador no conceden permisos. */
function portalURL(url) { return url.startsWith('/api/') ? '/index.php?route=' + encodeURIComponent(url.slice(1)) : url; }
const portalBootstrap=window.PORTAL_BOOTSTRAP;
for(const [key,value] of Object.entries(portalBootstrap.roles||{}))if(ROLES[key])Object.assign(ROLES[key],value);
let portalRevision=portalBootstrap.revision;
let portalBaseline=structuredClone(portalBootstrap.data);
const portalPasswords=new Map();
const portalProofs=Object.create(null);
const portalSent=new WeakMap();
const portalKnown=new Set(['mensajes','reclamos','expedientes','suscriptores'].flatMap(t=>(portalBootstrap.data[t]||[]).map(r=>t+':'+r.id)));
function portalRequest(method,url,body) {
  const xhr=new XMLHttpRequest(); xhr.open(method,portalURL(url),false);
  xhr.setRequestHeader('Content-Type','application/json');xhr.setRequestHeader('Accept','application/json');xhr.setRequestHeader('X-CSRF-TOKEN',portalBootstrap.csrf);
  xhr.send(body===undefined?null:JSON.stringify(body));let result;try{result=JSON.parse(xhr.responseText)}catch{result={}}
  if(xhr.status<200||xhr.status>=300)throw new Error(result.message||(xhr.status===409?'Otra persona guardó cambios. Copia tu trabajo y recarga la página.':'No se pudo conectar con el servidor.'));
  return result;
}
function portalMerge(target,source) {
  for(const key of Object.keys(target))if(!(key in source))delete target[key];
  for(const [key,value] of Object.entries(source)){
    if(['__proto__','prototype','constructor'].includes(key))continue;
    if(Array.isArray(value)){
      if(!Array.isArray(target[key]))target[key]=[];
      const identity=r=>!r||typeof r!=='object'||Array.isArray(r)?null:r.id!==undefined?'id:'+r.id:r.fileId?'file:'+r.fileId:r.cod!==undefined?'cod:'+r.cod:r.est!==undefined?'est:'+r.est+'|'+(r.ud??'')+'|'+(r.periodo??''):r.doc!==undefined?'doc:'+r.doc:null;
      const old=new Map(target[key].map(r=>[identity(r),r]).filter(([id])=>id!==null));
      const rows=value.map(row=>{const id=identity(row),existing=id!==null?old.get(id):null;if(existing&&row&&typeof row==='object'){portalMerge(existing,row);return existing;}return row;});
      target[key].splice(0,target[key].length,...rows);
    }
    else if(value&&typeof value==='object'){if(!target[key]||typeof target[key]!=='object'||Array.isArray(target[key]))target[key]={};portalMerge(target[key],value);}
    else target[key]=value;
  }
}
function portalLookup(type,reference,document,mode='codigo'){
  try{
    const result=portalRequest('POST','/api/consultas/'+type,{reference,document,mode});
    const records=result.records||[result.record];
    if(mode==='dni')for(const record of records)if(record)record.dni=reference;
    // Una consulta pública no reemplaza registros completos de una sesión administrativa por proyecciones sin adjuntos o datos privados.
    if(currentUser())return result.record||records;
    if(mode==='dni')DB[type]=DB[type].filter(r=>r.dni!==reference);
    for(const record of records){if(!record)continue;if(mode==='dni')record.dni=reference;Object.defineProperty(record,'__serverSaved',{value:true});
      const i=DB[type].findIndex(r=>r.id===record.id);if(i<0)DB[type].push(record);else DB[type][i]=record;}
    return result.record||records;
  }catch(error){toast(error.message,'warn');return null;}
}
loadDB=function(){DB=structuredClone(portalBootstrap.data);MEM_ONLY=false;};
currentUser=function(){return portalBootstrap.user;};
setSession=function(user){if(!user&&portalBootstrap.user){try{portalRequest('DELETE','/api/session');location.href='/#/admin';location.reload()}catch(error){toast(error.message,'err')}}};
function portalAccountFields(row){return {username:row.usuario,name:row.nombre,email:row.correo||'',role:row.rol,estado:row.estado,...Object.fromEntries(['vin','dni','personaId','programaId','area','cargo'].map(k=>[k,row[k]||'']))};}
function portalAccountOperations(){
  if(currentUser()?.rol!=='admin')return [];
  const before=new Map((portalBaseline.usuarios||[]).map(r=>[r.id,r]));const ops=[];
  for(const row of DB.usuarios||[]){const old=before.get(row.id);const fields=portalAccountFields(row);const password=portalPasswords.get(row.id);
    if(!old||password||JSON.stringify(fields)!==JSON.stringify(portalAccountFields(old))){ops.push({action:old?'update':'create',id:row.server_id,account:{...fields,...(password?{password}:{} )},...(old?{expected:portalAccountFields(old)}:{})});}
    else if(old.bloqueadoHasta&&!row.bloqueadoHasta)ops.push({action:'unlock',id:row.server_id});
  }return ops;
}
function portalNormalizeAutomaticChanges(){
  if(!DB.academico||!portalBaseline.academico||currentUser()?.rol==='admin')return;
  const role=currentUser().rol;
  const academicRole=IX_ROLE[role];const permissions=portalBaseline.academico.perms[academicRole]||{};
  const modules={estudiantes:'alumnos',sesiones:'horarios',publicados:'horarios',certs:'certificados',notas:'certificados',titulos:'titulos',empresas:'bolsa',ofertas:'bolsa',postulaciones:'bolsa',perfilesLab:'bolsa',perfilesDoc:'docentes',uds:'config',ambientes:'config',modulos:'config',areas:'mesa',tramites:'mesa',cfg:'config',perms:'usuarios',docs:'certificados',seq:'certificados'};
  const own=role==='docente'?['perfilesDoc']:['estudiante','egresado'].includes(role)?['estudiantes','perfilesLab','postulaciones','docs']:role==='empresa'?['empresas','ofertas','postulaciones']:[];
  for(const [key,module]of Object.entries(modules))if(!own.includes(key)&&(permissions[module]||0)<2&&key in portalBaseline.academico)DB.academico[key]=structuredClone(portalBaseline.academico[key]);
  if(role!=='secretaria')DB.personas=portalBaseline.personas.map(person=>role==='docente'&&person.id===currentUser().personaId?(DB.personas.find(p=>p.id===person.id)||person):structuredClone(person));
  // Los catálogos sincronizados son de lectura; las ediciones autorizadas se realizan en sus módulos.
  for(const key of ['programas','docentes','progMap'])DB.academico[key]=structuredClone(portalBaseline.academico[key]);
  if(!['admin','secretaria'].includes(currentUser()?.rol))DB.certificados=structuredClone(portalBaseline.certificados);
  for(const key of ['nombre','siglas','director','direccion','web'])DB.academico.cfg[key]=portalBaseline.academico.cfg[key];
}
function portalPatch(before,after){
 const same=(a,b)=>JSON.stringify(a)===JSON.stringify(b);const result=[];
 const key=(path,row)=>row&&row.id!==undefined?'id:'+row.id:path.endsWith('perfilesLab')?'est:'+row.est:path.endsWith('perfilesDoc')?'doc:'+row.doc:path.endsWith('estadisticas')?(row.anio||'')+'|'+(row.programaId||''):null;
 const add=(path,a,b)=>{if(same(a,b))return;const name=path.join('.');
  if(Array.isArray(a)&&Array.isArray(b)&&[...a,...b].every(r=>r&&typeof r==='object'&&key(name,r)!==null)){
    const old=new Map(a.map(r=>[key(name,r),r])),next=new Map(b.map(r=>[key(name,r),r]));const changes=[];
    for(const id of new Set([...old.keys(),...next.keys()]))if(!same(old.get(id),next.get(id)))changes.push({key:id,before:old.get(id)||null,after:next.get(id)||null});if(changes.length)result.push({path,kind:'rows',changes});
  }else if(a&&b&&!Array.isArray(a)&&!Array.isArray(b)&&typeof a==='object'&&typeof b==='object'){
    const changes=[];for(const k of new Set([...Object.keys(a),...Object.keys(b)]))if(!same(a[k],b[k]))changes.push({key:k,before:a[k]??null,after:b[k]??null,remove:!(k in b)});if(changes.length)result.push({path,kind:'object',changes});
  }else result.push({path,kind:'value',before:a??null,after:b??null});
 };
 for(const k of Object.keys(after)){if(['usuarios','auditoria','visitas','correos','__v'].includes(k))continue;if(k==='academico'){for(const sub of Object.keys(after[k]))if(!['usuarios','bitacora'].includes(sub))add([k,sub],before[k][sub],after[k][sub]);}else add([k],before[k],after[k]);}return result;
}
saveDB=function(){
  try{
    {
      for(const type of ['mensajes','reclamos','expedientes','suscriptores'])for(const record of DB[type]||[]){
        if(record.__serverSaved||portalKnown.has(type+':'+record.id))continue;
        if(!portalSent.has(record))portalSent.set(record,crypto.randomUUID());
        const result=portalRequest('POST','/api/solicitudes/'+type,{record,idempotency_key:portalSent.get(record),proof:portalProofs[type]});
        for(const key of ({mensajes:['contact'],reclamos:['lr','lr2'],expedientes:['mp','mpv']}[type]||[]))RATE[key]=Date.now();
        Object.assign(record,result.record);Object.defineProperty(record,'__serverSaved',{value:true});portalKnown.add(type+':'+record.id);portalBaseline[type].unshift(structuredClone(result.record));if(currentUser()){portalRevision=result.revision;if(result.counters){DB.config.contadores=structuredClone(result.counters);portalBaseline.config.contadores=structuredClone(result.counters);}}
      }
    }
    if(!currentUser()||!location.hash.startsWith('#/admin'))return true;
    portalNormalizeAutomaticChanges();
    const accounts=portalAccountOperations();
    if(!accounts.length&&JSON.stringify(DB)===JSON.stringify(portalBaseline))return true;
    const patch=portalPatch(portalBaseline,DB);if(!patch.length&&!accounts.length)return true;
    const result=portalRequest('PUT','/api/portal',{patch,revision:portalRevision,accounts});
    portalRevision=result.revision;
    if(result.normalized){result.normalized.usuarios=result.users||DB.usuarios;portalMerge(DB,result.normalized);}
    portalBootstrap.user=result.user;portalPasswords.clear();portalBaseline=structuredClone(DB);return true;
  }catch(error){if(ADM.work&&ADM.workKey&&SPECS[ADM.workKey]){const col=SPECS[ADM.workKey].coll;DB[col]=DB[col].map(r=>r===ADM.work?structuredClone(r):r);}portalMerge(DB,structuredClone(portalBaseline));toast(error.message,'err');return false;}
};
resetDB=function(){toast('El restablecimiento se realiza desde la copia de seguridad del servidor.','warn');};
mailOut=function(){return 'Omitido';};
const portalOriginalLogin=bindLogin;
bindLogin=function(){portalOriginalLogin();const form=document.querySelector('[data-login]');if(!form)return;
  form.onsubmit=async event=>{event.preventDefault();const button=form.querySelector('[type=submit],button:not([type=button])');button.disabled=true;
    try{const response=await fetch(portalURL('/api/session'),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':portalBootstrap.csrf},body:JSON.stringify({username:form.u.value.trim(),password:form.p.value})});
      const data=await response.json();if(!response.ok)throw new Error(data.message||'No se pudo iniciar sesión.');location.href='/#/admin/dashboard';location.reload();
    }catch(error){document.getElementById('lg-e').textContent=error.message;button.disabled=false;}};
};
// Se conserva el editor original de usuarios; las contraseñas viajan al servidor y se guardan con password_hash.
const portalUserFields=SPECS.usuarios.fields;
SPECS.usuarios.fields=function(work){const fields=portalUserFields(work);for(const f of fields)if(f.n==='pass1')f.hint='Mínimo 12 caracteres. La contraseña se protege con password_hash en PHP.';
  fields.push({n:'correo',l:'Correo electrónico',t:'email'});
  fields.push({n:'personaId',l:'Persona del docente (para su CV)',t:'select',opts:()=>DB.personas.map(p=>[p.id,fullName(p)])});return fields;};
const portalUserValidate=SPECS.usuarios.validate;
SPECS.usuarios.validate=function(work){const errors=portalUserValidate(work);delete errors.pass1;if(work.pass1||!work.has_password){if([...String(work.pass1||'')].length<12||new TextEncoder().encode(work.pass1||'').length>72)errors.pass1='La contraseña necesita al menos 12 caracteres y máximo 72 bytes.';if(work.pass1!==work.pass2)errors.pass2='Las contraseñas no coinciden.';}return errors;};
SPECS.usuarios.afterSave=function(work){if(work.pass1)portalPasswords.set(work.id,work.pass1);delete work.pass1;delete work.pass2;};
const portalOriginalAdmin=renderAdmin;
renderAdmin=function(parts,query){if(currentUser()?.rol==='admin'&&parts[1]==='respaldo')return {html:admHead('Respaldos y restauración','Crea, restaura y programa copias completas de contenido, cuentas y archivos.','','download')+`<section class="panel"><div class="pb"><a class="btn" href="/respaldos.php">Administrar respaldos y restauración</a><p>Las copias se guardan en la carpeta privada del portal.</p></div></section>`};if(currentUser()&&parts[1]==='perfil'){location.href='/accounts.php';return {html:''};}const out=portalOriginalAdmin(parts,query);if(currentUser()?.rol==='admin'&&parts[1]==='auditoria')out.html=`<p><a class="btn" href="/auditoria.php">Consultar y exportar el historial completo</a></p>`+out.html;return out;};
IXHOST.createStudentAccount=function(student){try{const result=portalRequest('POST','/api/cuentas',{action:'student',student_id:student.id});DB.usuarios.push(result.user);portalBaseline.usuarios=structuredClone(DB.usuarios);return {usuario:result.user.usuario,pass:result.temporary_password,rolLabel:ROLES[result.user.rol].label};}catch(error){return {error:error.message};}};
IXHOST.defaultPermissions=function(){return portalBootstrap.data.academico.perms;};
IXHOST.registerCompany=function(values){return portalRequest('POST','/api/empresas',{...values,password:values.pass});};
async function portalUpload(file){
  const body=new FormData();body.append('file',file);if(!currentUser()){const purpose=document.querySelector('#cap-mpv,#cap-mp')?'expedientes':'reclamos';body.append('purpose',purpose);body.append('proof',JSON.stringify(portalProofs[purpose]||{}));}
  const response=await fetch(portalURL('/api/archivos'),{method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':portalBootstrap.csrf},body});let data;
  try{data=await response.json();}catch{throw new Error('No se pudo subir el archivo. El servidor no devolvió una respuesta válida.');}
  if(!response.ok)throw new Error(data.message||'No se pudo subir el archivo.');return data.file;
}

const portalOldDocBlob=docBlob;
docBlob=function(doc){if(doc?.fileId){const xhr=new XMLHttpRequest();xhr.open('GET',doc.url,false);xhr.overrideMimeType('text/plain; charset=x-user-defined');xhr.send();if(xhr.status!==200)throw new Error('No tienes acceso al archivo.');return new Blob([Uint8Array.from(xhr.responseText,c=>c.charCodeAt(0)&255)],{type:doc.type});}return portalOldDocBlob(doc);};
const portalOldOpenDoc=openDoc;
openDoc=function(doc){if(doc?.fileId){window.open(doc.url,'_blank','noopener');return;}portalOldOpenDoc(doc);};
const portalOldDownloadDoc=downloadDoc;
downloadDoc=function(doc){if(doc?.fileId){const a=document.createElement('a');a.href=doc.url+'&download=1';a.download=doc.original||doc.nombre;a.click();return;}portalOldDownloadDoc(doc);};
// Los campos de archivo del módulo académico antes conservaban solo nombre y tamaño.
document.addEventListener('change',async event=>{
  const input=event.target;if(!input.matches('.ix input[type=file]')||input.dataset.act||input.dataset.id)return;
  const files=[...input.files];if(!files.length){input._portalFiles=[];return;}
  input._portalUploading=true;
  try{input._portalFiles=await Promise.all(files.map(portalUpload));}catch(error){input._portalFiles=null;input.value='';toast(error.message,'err');}finally{input._portalUploading=false;}
},true);
document.addEventListener('submit',event=>{if(EDITOR_UPLOADS.size||[...event.target.querySelectorAll('input[type=file]')].some(i=>i._portalUploading)){event.preventDefault();event.stopImmediatePropagation();toast('Espera a que termine la carga del archivo.','warn');}},true);
// El horario y los catálogos se generan con los datos del servidor; no se crean cifras ficticias.
seedVisits=function(){};
countVisit=function(){};
let portalLastTracked='';
function portalTrack(){const path=location.hash||'#/';if(path.startsWith('#/admin')||path===portalLastTracked)return;portalLastTracked=path;
  fetch(portalURL('/api/visitas'),{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':portalBootstrap.csrf},body:JSON.stringify({path})}).catch(()=>{});}
window.addEventListener('hashchange',portalTrack);
document.addEventListener('DOMContentLoaded',()=>{const style=document.createElement('style');style.textContent='.pz-lg-demo,.demo-users{display:none!important}';document.head.append(style);portalTrack();});

// El acceso desde Bolsa laboral usa la misma sesión PHP que el resto de la intranet.
document.addEventListener('submit',async event=>{
 const form=event.target;if(!form.matches('[data-blog]'))return;event.preventDefault();event.stopImmediatePropagation();
 try{const values=new FormData(form);if(!values.get('tipo'))throw new Error('Selecciona el tipo de usuario.');portalRequest('POST','/api/session',{username:values.get('u'),password:values.get('p'),role:values.get('tipo')});location.href='/#/admin/ix-bolsa';location.reload();}catch(error){toast(error.message,'err');}
},true);

const portalDownloadBlob=downloadBlob;
downloadBlob=function(blob,filename){if(currentUser())fetch(portalURL('/api/eventos'),{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':portalBootstrap.csrf},body:JSON.stringify({action:'Exportar',filename:String(filename||'archivo').slice(0,200),size:blob?.size||0})}).catch(()=>{});return portalDownloadBlob(blob,filename);};

const portalProgramFields=SPECS.programas.fields;
SPECS.programas.fields=function(work){const fields=typeof portalProgramFields==='function'?portalProgramFields(work):portalProgramFields;for(const f of fields)if(f.n==='codigo'){f.unique='norm';f.hint='Código único del programa, compartido con la gestión académica.';}return fields;};

// Acciones visibles y filtros específicos de cuentas.
SPECS.usuarios.sub='Administre cuentas, cambie el rol, desactive accesos o restablezca contraseñas. Los cambios de rol cierran las sesiones anteriores.';
SPECS.usuarios.extraActs=()=>'<a class="btn sec" href="/roles.php">Editar roles y permisos</a>';
SPECS.usuarios.search=['usuario','nombre','correo',r=>ROLES[r.rol]?.label||''];
SPECS.usuarios.searchPh='Usuario, nombre o correo';
SPECS.usuarios.filters=[{n:'rol',l:'Rol',opts:()=>Object.entries(ROLES).map(([k,r])=>[k,r.label])},{n:'estado',l:'Estado',opts:['Activo','Inactivo']}];
const portalUsersAfterList=SPECS.usuarios.afterList;
SPECS.usuarios.afterList=()=>{
  portalUsersAfterList();
  const table=document.querySelector('.tbl-wrap .tbl');
  if(table){table.querySelectorAll('tr').forEach(row=>{const cell=row.lastElementChild;if(cell){cell.style.position='sticky';cell.style.right='0';cell.style.background='var(--surface,#fff)';cell.style.zIndex='1';}});}
  const panel=document.querySelector('#perm-h')?.closest('.panel');
  if(panel)panel.insertAdjacentHTML('afterbegin','<div class="pb"><a class="btn" href="/roles.php">Editar permisos por rol</a><p>Seleccione los módulos habilitados para cada perfil institucional. El administrador conserva acceso completo.</p></div>');
};
