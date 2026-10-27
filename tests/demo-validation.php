<?php
require dirname(__DIR__).'/portal-private/bootstrap.php';
$d=json_decode(file_get_contents(dirname(__DIR__).'/portal-private/data/portal.json'),true,flags:JSON_THROW_ON_ERROR);$u=['rol'=>'admin','usuario'=>'qa','nombre'=>'QA'];$errors=[];$count=0;
foreach(PortalPolicy::definitions()['specs'] as $spec){$key=$spec['collection'];if($key==='usuarios'||!isset($d[$key]))continue;foreach($d[$key] as $i=>$row){$prior=$d;array_splice($prior[$key],$i,1);$next=$d;try{PortalRules::apply($prior,$next,$u);PortalDomain::validate($prior,$next,$u);$count++;}catch(Throwable $e){$errors[]=['collection'=>$key,'id'=>$row['id'],'error'=>$e->getMessage()];}}}
$academic=json_decode(file_get_contents(dirname(__DIR__).'/portal-private/contracts/academic.json'),true,flags:JSON_THROW_ON_ERROR);
foreach($academic as $key=>$spec){if($key==='usuarios')continue;foreach($d['academico'][$key] as $i=>$row){$prior=$d;array_splice($prior['academico'][$key],$i,1);$next=$d;try{PortalAcademic::validate($prior,$next);PortalDomain::validate($prior,$next,$u);$count++;}catch(Throwable $e){$errors[]=['collection'=>'academico.'.$key,'id'=>$row['id'],'error'=>$e->getMessage()];}}}
echo json_encode(['checked'=>$count,'failures'=>$errors],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),"\n";exit($errors?1:0);
