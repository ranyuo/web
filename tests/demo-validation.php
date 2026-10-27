<?php
require dirname(__DIR__).'/portal-private/bootstrap.php';
$d=json_decode(file_get_contents(dirname(__DIR__).'/portal-private/data/portal.json'),true,flags:JSON_THROW_ON_ERROR);$u=['rol'=>'admin','usuario'=>'qa','nombre'=>'QA'];$errors=[];$count=0;
foreach(PortalPolicy::definitions()['specs'] as $spec){$key=$spec['collection'];if($key==='usuarios'||!isset($d[$key]))continue;foreach($d[$key] as $i=>$row){$next=$d;$next[$key][$i]['modEn']='2026-10-27T12:00:00Z';try{PortalRules::apply($d,$next,$u);PortalDomain::validate($d,$next,$u);$count++;}catch(Throwable $e){$errors[]=['collection'=>$key,'id'=>$row['id'],'error'=>$e->getMessage()];}}}
echo json_encode(['checked'=>$count,'failures'=>$errors],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),"\n";exit($errors?1:0);
