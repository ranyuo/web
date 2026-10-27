<?php
declare(strict_types=1);
ob_start();require dirname(__DIR__).'/portal-private/bootstrap.php';
$path=getenv('PORTAL_CONFIG_FILE');if(!$path||!str_ends_with($path,'config.test.php'))throw new RuntimeException('Se exige config.test.php.');$config=require $path;if(!str_ends_with($config['db_name'],'_test'))throw new RuntimeException('Se exige una base _test.');
$app=portal_app();$db=$app->db();foreach(['pisco_audit','pisco_settings','pisco_files','pisco_visits','pisco_schema','pisco_users','pisco_rate_limits','pisco_state'] as $table)$db->exec('DROP TABLE IF EXISTS '.$table);
$count=0;
function ok(bool $x,string $label):void{global $count;if(!$x)throw new RuntimeException('FALLO: '.$label);$count++;echo "OK $label\n";}
function denied(int $status,callable $f,string $label):void{try{$f();}catch(PortalError $e){if($e->status!==$status)throw new RuntimeException($label.': '.$e->status.' '.$e->getMessage());ok(true,$label);return;}throw new RuntimeException('FALLO sin rechazo: '.$label);}
function actor(string $username):void{global $db;$r=$db->prepare('SELECT id,auth_version FROM pisco_users WHERE username=?');$r->execute([$username]);$u=$r->fetch();if(!$u)throw new RuntimeException('Cuenta no encontrada '.$username);$_SESSION['user_id']=(int)$u['id'];$_SESSION['auth_version']=(int)$u['auth_version'];}
function saveData(array $data):array{global $app;$b=$app->bootstrap();return $app->save(['revision'=>$b['revision'],'data'=>$data]);}
function change(callable $f):array{global $app;$b=$app->bootstrap();$d=$b['data'];$f($d);return $app->save(['revision'=>$b['revision'],'data'=>$d]);}
function ruc(string $base):string{$w=[5,4,3,2,7,6,5,4,3,2];$sum=0;foreach($w as $i=>$v)$sum+=(int)$base[$i]*$v;$d=11-$sum%11;return $base.($d===10?0:($d===11?1:$d));}
const PASS='PruebaOriginal-2026!';
$app->install(['install_key'=>$config['install_key'],'username'=>'admin','name'=>'Administrador','email'=>'admin@example.test','password'=>PASS,'password_confirmation'=>PASS]);
$app->login(['username'=>'admin','password'=>PASS]);
change(function(&$d){$a=&$d['academico'];
 // Las pruebas crean su propio padrón; la demo ya incluye alumnos y documentos.
 foreach(['estudiantes','certs','notas','titulos'] as $collection)$a[$collection]=[];
 $d['certificados']=[];
 foreach([[1001,'70001001',1,'Regular'],[1002,'70001002',2,'Regular'],[1003,'70001003',1,'Egresado']] as [$id,$dni,$prog,$state])$a['estudiantes'][]=['id'=>$id,'dni'=>$dni,'nom'=>'Alumno de prueba '.$id,'prog'=>$prog,'periodo'=>$a['cfg']['periodo'],'ciclo'=>'VI','sec'=>'B','turno'=>'Tarde','estado'=>$state,'correo'=>'alumno'.$id.'@example.test','tel'=>'999111222'];
 $a['uds'][]=['id'=>9001,'cod'=>'UD-TEST','nombre'=>'Unidad de prueba','prog'=>1,'ciclo'=>'VI','horas'=>1,'cred'=>1];
 $a['docentes'][]=['id'=>9001,'per'=>'d4','nom'=>'Docente de prueba','dni'=>'70009999','tel'=>'999111222','correo'=>'docente@example.test','cond'=>'Contratado','esp'=>'Prueba','uds'=>[9001],'dias'=>['Lun','Mar','Mié','Jue','Vie'],'desde'=>'07:00','hasta'=>'22:00'];
 $a['ambientes'][]=['id'=>9001,'cod'=>'AULA-TEST','nombre'=>'Aula de prueba','ubic'=>'Pabellón de prueba','tipo'=>'Aula','cap'=>30,'prog'=>1];
 $a['sesiones'][]=['id'=>9001,'per'=>$a['cfg']['periodo'],'prog'=>1,'ciclo'=>'VI','sec'=>'B','turno'=>'Tarde','ud'=>9001,'doc'=>9001,'amb'=>9001,'dia'=>'Vie','ini'=>'16:00','fin'=>'17:00'];
 $a['modulos'][]=['id'=>9001,'prog'=>1,'cod'=>'MOD-TEST','nombre'=>'Módulo de prueba','ciclos'=>'VI'];
});
ok(true,'Maestro académico completo guardado en MySQL');
$student=$app->accountOperation(['action'=>'student','student_id'=>1001]);ok($student['user']['rol']==='estudiante'&&strlen($student['temporary_password'])>=12,'Crear acceso desde el maestro de alumnos');
$app->accountOperation(['action'=>'update','id'=>$student['user']['server_id'],'account'=>['username'=>'70001001','name'=>'Alumno 1001','email'=>'alumno1001@example.test','password'=>PASS,'role'=>'estudiante','vin'=>'est:1001']]);
foreach(['imagen','secretaria','coordinador','transparencia','atencion','directivo','docente','egresado'] as $role){$profile=match($role){'coordinador'=>['programaId'=>'apsti'],'docente'=>['vin'=>'doc:9001','personaId'=>'d4'],'egresado'=>['vin'=>'est:1003'],default=>[]};$app->createAccount(['username'=>$role,'name'=>'Rol '.$role,'email'=>$role.'@example.test','password'=>PASS,'role'=>$role,...$profile]);}
$app->createAccount(['username'=>'70001002','name'=>'Otro alumno','email'=>'alumno1002@example.test','password'=>PASS,'role'=>'estudiante','vin'=>'est:1002']);
$app->logout();$companyInput=['declaration'=>true,'ruc'=>ruc('2012345678'),'nom'=>'Empresa de prueba','sector'=>'Servicios','ubic'=>'Pisco','dir'=>'Calle de prueba 123','contacto'=>'Contacto de prueba','cargoC'=>'Responsable','correo'=>'empresa@example.test','tel'=>'999111222','web'=>'','password'=>PASS,'role'=>'admin'];
$registered=$app->registerCompany($companyInput);$companyId=$registered['company']['id'];$companyUsername=$registered['username'];
$app->login(['username'=>$companyUsername,'password'=>PASS]);ok($app->user()['rol']==='empresa','Registro público no permite escalar a administrador');
$app->logout();$otherCompany=$app->registerCompany(['declaration'=>true,...$companyInput,'ruc'=>ruc('2012345679'),'correo'=>'otraempresa@example.test']);
foreach(['admin','imagen','secretaria','coordinador','transparencia','atencion','directivo','docente','70001001','egresado',$companyUsername] as $username){$app->logout();$app->login(['username'=>$username,'password'=>PASS]);ok($app->user()['usuario']===$username,'Inicio de sesión del perfil '.$username);}
actor('70001001');$b=$app->bootstrap();ok(array_column($b['data']['academico']['estudiantes'],'id')===[1001],'Alumno solo recibe su registro académico');
denied(403,fn()=>change(function(&$d){$d['academico']['estudiantes'][0]['estado']='Titulado';}),'Alumno no puede cambiar su estado académico');
denied(403,fn()=>change(function(&$d){$d['config']['inst']['nombre']='Alterado';}),'Alumno no puede editar configuración institucional');
$pdf="%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF";
$cv=PortalFiles::store($app,'curriculo.pdf',$pdf);
change(function(&$d)use($cv){$d['academico']['perfilesLab'][]=['est'=>1001,'resumen'=>'Perfil de prueba','competencias'=>['Comunicación'],'exp'=>[],'cv'=>$cv];$d['academico']['estudiantes'][0]['tel']='999222333';});ok(true,'Alumno guarda su perfil, teléfono y CV real');
actor('70001002');denied(403,fn()=>PortalFiles::metadata($app,['fileId'=>$cv['fileId']]),'Otro alumno no puede descargar el CV');
actor('atencion');change(function(&$d)use($companyId){foreach($d['academico']['empresas'] as &$c)if($c['id']===$companyId){$c['validada']=true;$c['estadoVal']='Aprobada';$c['valPor']='atencion';$c['fechaVal']=date('Y-m-d');}});ok(true,'Atención valida una empresa');
actor($companyUsername);change(function(&$d)use($companyId){$d['academico']['ofertas'][]=['id'=>9001,'emp'=>$companyId,'puesto'=>'Puesto de prueba','desc'=>'Descripción del puesto','req'=>'Requisitos del puesto','modal'=>'Presencial','prog'=>1,'vac'=>2,'limite'=>date('Y-m-d',time()+86400*30),'estado'=>'Por validar','fecha'=>date('Y-m-d')];});ok(true,'Empresa validada propone una oferta');
denied(403,fn()=>change(function(&$d){foreach($d['academico']['ofertas'] as &$o)if($o['id']===9001)$o['estado']='Publicada';}),'Empresa no puede aprobar su propia oferta');
actor('atencion');change(function(&$d){foreach($d['academico']['ofertas'] as &$o)if($o['id']===9001)$o['estado']='Publicada';});ok(true,'Atención aprueba la publicación de la oferta');
actor('70001001');change(function(&$d){$d['academico']['postulaciones'][]=['id'=>9001,'of'=>9001,'est'=>1001,'fecha'=>date('Y-m-d'),'estado'=>'Recibida'];});ok(true,'Alumno postula con su CV');
denied(422,fn()=>change(function(&$d){$d['academico']['postulaciones'][]=['id'=>9002,'of'=>9001,'est'=>1001,'fecha'=>date('Y-m-d'),'estado'=>'Recibida'];}),'No se permiten postulaciones duplicadas');
actor($companyUsername);ok(PortalFiles::metadata($app,['fileId'=>$cv['fileId']])['sha256']===hash('sha256',$pdf),'Empresa destinataria descarga el CV del postulante');
change(function(&$d){foreach($d['academico']['postulaciones'] as &$p)if($p['id']===9001)$p['estado']='Seleccionado';});ok(true,'Empresa actualiza el resultado de la postulación');
actor($otherCompany['username']);denied(403,fn()=>PortalFiles::metadata($app,['fileId'=>$cv['fileId']]),'Otra empresa no puede ver un CV ajeno');
actor('secretaria');change(function(&$d){$d['academico']['notas'][]=['id'=>9001,'est'=>1001,'ud'=>'Unidad de prueba','periodo'=>$d['academico']['cfg']['periodo'],'nota'=>17];$d['academico']['certs'][]=['id'=>9001,'est'=>1001,'mod'=>9001,'fecha'=>date('Y-m-d'),'num'=>'CM-TEST-2026'];});ok(true,'Secretaría guarda notas y certificación modular');
denied(422,fn()=>change(function(&$d){$d['academico']['notas'][0]['nota']=21;}),'Nota fuera de rango rechazada');
$app->logout();$lookup=$app->lookup('certificados',['reference'=>'CM-TEST-2026']);ok($lookup['record']['nombres']==='Alumno de prueba 1001','Certificación académica verificable desde el portal público');
$lookup=$app->lookup('certificados',['reference'=>'70001001','mode'=>'dni']);ok(count($lookup['records'])===1&&!isset($lookup['records'][0]['dni']),'Búsqueda por DNI devuelve datos mínimos del documento');
actor('70001001');ok(count($app->bootstrap()['data']['academico']['notas'])===1,'Alumno consulta su nota');
denied(403,fn()=>change(function(&$d){$d['academico']['notas'][0]['nota']=20;}),'Alumno no puede modificar notas');
actor('coordinador');ok(!in_array(1002,array_column($app->bootstrap()['data']['academico']['estudiantes'],'id'),true),'Coordinador no recibe alumnos de otro programa');
denied(403,fn()=>change(function(&$d){foreach($d['programas'] as &$p)if($p['id']!=='apsti'){$p['nombre']='Alterado';break;}}),'Coordinador no modifica otros programas');
actor('docente');change(function(&$d){foreach($d['personas'] as &$p)if($p['id']==='d4')$p['cv']['perfil']='Perfil docente actualizado desde PHP.';});ok(true,'Docente actualiza su propio CV');
denied(403,fn()=>change(function(&$d){foreach($d['personas'] as &$p)if($p['id']!=='d4'){$p['cv']['perfil']='Ajeno';break;}}),'Docente no modifica el CV de otra persona');
actor('imagen');change(function(&$d){$n=$d['noticias'][0];$n['id']='noticia-paridad';$n['titulo']='Noticia de prueba PHP';$n['slug']='noticia-de-prueba-php';$n['fecha']=date(DATE_ATOM);$n['estado']='Borrador';$d['noticias'][]=$n;});ok(true,'Imagen crea una noticia');
change(function(&$d){foreach($d['noticias'] as &$n)if($n['id']==='noticia-paridad')$n['estado']='Publicada';});ok(true,'Imagen publica noticias autorizadas');
denied(403,fn()=>change(function(&$d){$d['academico']['perms']['doc']['config']=2;}),'Imagen no puede cambiar permisos académicos');
actor('transparencia');$doc=PortalFiles::store($app,'plan.pdf',$pdf);
change(function(&$d)use($doc){$n=$d['documentos'][0];$n['id']='documento-paridad';$n['nombre']='Plan de prueba';$n['estado']='En revisión';$n['archivo']=$doc;$n['refModulo']='';$d['documentos'][]=$n;});ok(true,'Transparencia envía documento a revisión');
denied(403,fn()=>change(function(&$d){foreach($d['documentos'] as &$r)if($r['id']==='documento-paridad')$r['estado']='Publicado';}),'Transparencia no se autoaprueba');
actor('admin');change(function(&$d){foreach($d['documentos'] as &$r)if($r['id']==='documento-paridad')$r['estado']='Publicado';});$app->logout();ok(PortalFiles::metadata($app,['fileId'=>$doc['fileId']])['size']===strlen($pdf),'Documento aprobado descargable por un visitante');
actor('secretaria');$group='1|VI|B|Tarde|'.$app->bootstrap()['data']['academico']['cfg']['periodo'];
denied(422,fn()=>change(function(&$d)use($group){$r=PortalPolicy::find($d['academico']['sesiones'],'id',9001);$r['id']=9002;$d['academico']['sesiones'][]=$r;$d['academico']['publicados'][$group]=['fecha'=>date('Y-m-d H:i'),'por'=>'Secretaría'];}),'Horario con cruce no se publica');
change(function(&$d)use($group){$d['academico']['publicados'][$group]=['fecha'=>date('Y-m-d H:i'),'por'=>'Secretaría'];});ok(true,'Secretaría publica un horario sin cruces');
actor('70001001');ok(array_column($app->bootstrap()['data']['academico']['sesiones'],'id')===[9001],'Alumno solo recibe el horario publicado de su sección');
actor('directivo');denied(403,fn()=>change(function(&$d){$d['academico']['notas'][0]['nota']=18;}),'Directivo respeta permiso académico de consulta');
$app->logout();$before=$app->bootstrap()['revision'];$app->track(['path'=>'#/programas']);$app->track(['path'=>'#/programas']);ok((int)array_sum($app->bootstrap()['data']['visitas'])===1,'Contador real sin inflar recargas inmediatas');ok($app->bootstrap()['revision']===$before,'Visitas no provocan conflictos al guardar contenido');
actor('admin');$n=$db->query('SELECT COUNT(*) FROM pisco_users')->fetchColumn();PortalSchema::apply($db);ok($db->query('SELECT COUNT(*) FROM pisco_users')->fetchColumn()===$n,'Actualización repetible conserva usuarios y datos');
echo "\n$count comprobaciones de paridad aprobadas.\n";ob_end_flush();
