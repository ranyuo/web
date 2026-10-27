<?php
declare(strict_types=1);
ob_start();
/* Solo una base nueva y desechable: verifica la demo exacta, sin los datos de parity.php. */
require dirname(__DIR__).'/portal-private/bootstrap.php';
$path=getenv('PORTAL_CONFIG_FILE');
if(!$path||!str_ends_with($path,'config.test.php'))throw new RuntimeException('Solo config.test.php.');
$config=require $path;
if(!str_ends_with($config['db_name'],'_test'))throw new RuntimeException('Solo una base _test.');
$app=portal_app();
if($app->installed())throw new RuntimeException('Utiliza una base de pruebas nueva.');
$password='PruebaDemoICA-2026!';
$app->install(['install_key'=>$config['install_key'],'username'=>'admin','name'=>'Administrador QA','email'=>'admin@example.test','password'=>$password,'password_confirmation'=>$password]);
$app->login(['username'=>'admin','password'=>$password]);
$initial=$app->bootstrap()['data'];
if($initial['config']['inst']['nombre']!=='INSTITUTO PUBLICO DE ICA')throw new RuntimeException('Identidad incorrecta.');
echo "OK instalación de la demo neutra\n";
$students=$initial['academico']['estudiantes'];$accounts=[];
foreach($students as $student){
 $result=$app->accountOperation(['action'=>'student','student_id'=>$student['id']]);
 $expected=in_array($student['estado'],['Titulado','Egresado'],true)?'egresado':'estudiante';
 if($result['user']['rol']!==$expected||strlen($result['temporary_password'])<12)throw new RuntimeException('Acceso académico incorrecto.');
 $accounts[]=['username'=>$student['dni'],'password'=>$result['temporary_password'],'role'=>$expected];
}
foreach($accounts as $account){
 $app->logout();$app->login($account);
 if($app->user()['rol']!==$account['role'])throw new RuntimeException('Perfil incorrecto.');
 if(count($app->bootstrap()['data']['academico']['estudiantes'])!==1)throw new RuntimeException('Alumno accede a datos ajenos.');
}
echo 'OK creación de acceso e ingreso de los '.count($accounts)." alumnos/egresados de ejemplo, con aislamiento de datos\n";
$app->logout();
foreach($initial['certificados'] as $certificate){
 $lookup=$app->lookup('certificados',['reference'=>$certificate['codigo']]);
 if($lookup['record']['codigo']!==$certificate['codigo']||$lookup['record']['estado']!=='Válido')throw new RuntimeException('Certificado de ejemplo no verificable.');
}
echo "OK consulta pública de los tres certificados/títulos de demostración\n";
$app->login(['username'=>'admin','password'=>$password]);
$before=$app->bootstrap();$data=$before['data'];$data['noticias'][0]['titulo']='Noticia editada desde la instalación nueva';
$app->save(['revision'=>$before['revision'],'data'=>$data]);
$app->logout();
if($app->bootstrap()['data']['noticias'][0]['titulo']!==$data['noticias'][0]['titulo'])throw new RuntimeException('No se conserva la edición.');
echo "OK edición persistente y visible en sesión pública\n";
ob_end_flush();
