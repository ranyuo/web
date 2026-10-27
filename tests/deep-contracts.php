<?php
declare(strict_types=1);
ob_start();require dirname(__DIR__).'/portal-private/bootstrap.php';$p=getenv('PORTAL_CONFIG_FILE');if(!$p||!str_ends_with($p,'config.test.php'))throw new RuntimeException('Solo configuración de pruebas.');$cfg=require$p;if(!str_ends_with($cfg['db_name'],'_test'))throw new RuntimeException('Solo base de pruebas.');$app=portal_app();$db=$app->db();$results=[];
function actor(string $name):void{global$db;$s=$db->prepare('SELECT id,auth_version FROM pisco_users WHERE username=?');$s->execute([$name]);$r=$s->fetch();if(!$r)throw new RuntimeException('Falta cuenta de prueba.');$_SESSION['user_id']=(int)$r['id'];$_SESSION['auth_version']=(int)$r['auth_version'];}
function save(array $d):array{global$app;$b=$app->bootstrap();return$app->save(['data'=>$d,'revision'=>$b['revision']]);}
function reject(string $label,string $user,callable $edit,int $status=422):void{global$app,$db,$results;actor('admin');$full=$app->bootstrap()['data'];actor($user);$before=$app->bootstrap()['data'];$next=$before;$edit($next);$raw=$db->query('SELECT data FROM pisco_state WHERE id=1')->fetchColumn();$accepted=false;$ok=false;$error='';try{save($next);$accepted=true;}catch(PortalError$e){$ok=$e->status===$status;$error=$e->getMessage();}if($accepted){actor('admin');save($full);}else$ok=$ok&&$raw===$db->query('SELECT data FROM pisco_state WHERE id=1')->fetchColumn();$results[]=['label'=>$label,'ok'=>$ok,'accepted'=>$accepted];echo($ok?'OK ':'FALLO ').$label.($accepted?' — aceptado indebidamente':($ok?'':' — '.$error))."\n";}
reject('Enlace activo de portada exige protocolo permitido','imagen',function(&$d){$d['config']['home']['accesos'][0]['enlace']='javascript:alert(1)';});
reject('Acceso rápido exige título antes de guardar','imagen',function(&$d){$d['config']['home']['accesos'][0]['titulo']='';});
reject('Carrusel rechaza enlaces ejecutables desde la API','imagen',function(&$d){$d['slides'][0]['enlace']='javascript:alert(1)';});
reject('Servicio externo rechaza una URL incompleta','imagen',function(&$d){$d['enlaces'][0]['url']='https://';});
reject('Ventana emergente rechaza fecha final anterior','admin',function(&$d){$d['config']['popup']['inicio']='2026-10-26';$d['config']['popup']['fin']='2026-10-01';});
reject('Visibilidad de bloques es una casilla booleana','imagen',function(&$d){$d['config']['home']['bloques'][0]['on']='false';});
reject('Portada respeta el máximo de ocho beneficios','imagen',function(&$d){$d['config']['home']['beneficios'][]=$d['config']['home']['beneficios'][0];});
reject('Cada cifra institucional requiere año de corte','imagen',function(&$d){$d['config']['home']['cifras'][0]['corte']='';});
reject('Licenciado exige resolución institucional','admin',function(&$d){$d['licenciamiento']['estado']='Licenciado';$d['licenciamiento']['resolucion']='';});
reject('Fecha institucional imposible rechazada','admin',function(&$d){$d['config']['inst']['fechaCreacion']='2026-02-30';});
reject('WhatsApp activo exige número de nueve a trece dígitos','admin',function(&$d){$d['config']['whatsapp']['activo']=true;$d['config']['whatsapp']['numero']='abc';});
reject('La UIT usada en TUPA debe ser positiva','admin',function(&$d){$d['config']['uit'][(string)$d['tupaDocs']['anio']]=-1;});
reject('Estadística negativa rechazada','secretaria',function(&$d){$d['estadisticas'][0]['matriculados']=-1;});
reject('Estadística fraccionaria rechazada','secretaria',function(&$d){$d['estadisticas'][0]['matriculados']=1.5;});
reject('Estadística requiere programa existente','secretaria',function(&$d){$d['estadisticas'][0]['programaId']='programa-inexistente';});
actor('admin');$id='deep-collision-'.bin2hex(random_bytes(4));$code='DEEP-'.strtoupper(bin2hex(random_bytes(4)));$d=$app->bootstrap()['data'];$d['certificados'][]=['id'=>$id,'codigo'=>strtolower($code),'dni'=>'79999123','nombres'=>'Titular previo de prueba','programaId'=>$d['programas'][0]['id'],'tipo'=>'Certificado','fecha'=>date('Y-m-d'),'estado'=>'Válido','deleted'=>false];save($d);
try{reject('Un título no sobrescribe un certificado con el mismo código','admin',function(&$d)use($code){$d['academico']['titulos'][]=['id'=>889991,'est'=>1003,'culm'=>'2026-01-01','tit'=>'2026-10-01','cod'=>$code,'resol'=>'RES-DEEP-QA'];});}
finally{actor('admin');$d=$app->bootstrap()['data'];$d['certificados']=array_values(array_filter($d['certificados'],fn($r)=>strtoupper($r['codigo']??'')!==$code));save($d);}
if(array_filter($results,fn($r)=>!$r['ok'])){ob_end_flush();throw new RuntimeException('Hay contratos de formulario o integridad sin aplicar en PHP.');}echo"\n".count($results)." contratos profundos rechazados correctamente sin cambios parciales.\n";ob_end_flush();
