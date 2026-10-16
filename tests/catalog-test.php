<?php
if (PHP_SAPI!=='cli') exit;
if (!preg_match('/^edoc_(catalog|calendar)_check_/',getenv('EDOC_DB_NAME')?:'')) throw new RuntimeException('Usa una base independiente edoc_catalog_check_* o edoc_calendar_check_* de pruebas.');
require dirname(__DIR__).'/includes/connection.php';
require dirname(__DIR__).'/tools/demo-data.php';
function catalog_check($ok,$label) { if (!$ok) throw new RuntimeException('FAIL: '.$label); echo 'PASS: '.$label."\n"; }
function catalog_digest($db) {
    $out=[];
    foreach (['doctor'=>'docid','webuser'=>'email','schedule'=>'scheduleid','appointment'=>'appoid'] as $table=>$key) $out[$table]=hash('sha256',json_encode($db->query("SELECT * FROM `$table` ORDER BY `$key`")->fetch_all(MYSQLI_ASSOC)));
    return $out;
}
$specialties=$database->query('SELECT id,sname FROM specialties ORDER BY id')->fetch_all(MYSQLI_ASSOC);
catalog_check(count($specialties)===56,'Catálogo conserva 56 especialidades');
catalog_check(count(array_filter($specialties,fn($s)=>preg_match('/medicine|surgery|General Practice|Paediatrics|Cardiology|Dermatology|psychiatry/i',$s['sname'])))===0,'No quedan nombres originales en inglés');
$names=array_column($specialties,'sname','id');
catalog_check($names[18]==='Medicina general' && $names[38]==='Pediatría' && $names[5]==='Cardiología' && $names[32]==='Ginecología y obstetricia','Traducciones y acentos correctos');
$demo=$database->query("SELECT d.*,sp.sname FROM doctor d JOIN specialties sp ON sp.id=d.specialties WHERE docemail LIKE '%@demo.example.invalid'")->fetch_all(MYSQLI_ASSOC);
catalog_check(count($demo)===16,'Dieciséis médicos de demostración');
catalog_check(count(array_unique(array_column($demo,'specialties')))===16,'Dieciséis especialidades con médicos disponibles');
catalog_check(count(array_filter($demo,fn($d)=>!empty(password_get_info($d['docpassword'])['algo'])))===16,'Claves nuevas protegidas sin contraseñas compartidas');
catalog_check((int)$database->query("SELECT COUNT(*) FROM webuser w JOIN doctor d ON d.docemail=w.email WHERE d.docemail LIKE '%@demo.example.invalid' AND w.usertype='d'")->fetch_row()[0]===16,'Cuentas de médico vinculadas al rol correcto');
$sessions=$database->query("SELECT s.* FROM schedule s JOIN doctor d ON d.docid=s.docid WHERE d.docemail LIKE '%@demo.example.invalid'")->fetch_all(MYSQLI_ASSOC);
catalog_check(count($sessions)===96,'Noventa y seis jornadas de demostración');
foreach ($demo as $d) catalog_check(count(array_filter($sessions,fn($s)=>$s['docid']===$d['docid']))===6,'Seis jornadas para '.$d['docname']);
$now=new DateTimeImmutable('now',new DateTimeZone('America/Managua'));
catalog_check(count(array_filter($sessions,fn($s)=>new DateTimeImmutable($s['scheduledate'].' '.$s['scheduletime'],new DateTimeZone('America/Managua'))>$now))===96,'Todas las jornadas empiezan en el futuro');
catalog_check(count(array_filter($sessions,fn($s)=>(int)date('N',strtotime($s['scheduledate']))<=5))===96,'Jornadas de lunes a viernes');
catalog_check(count(array_filter($sessions,fn($s)=>$s['endtime']>$s['scheduletime'] && (int)$s['duration_minutes']===30))===96,'Rangos válidos y consultas de treinta minutos');
catalog_check((int)$database->query("SELECT COUNT(*) FROM schedule a JOIN schedule b ON a.docid=b.docid AND a.scheduledate=b.scheduledate AND a.scheduleid<b.scheduleid AND a.scheduletime<b.endtime AND a.endtime>b.scheduletime JOIN doctor d ON d.docid=a.docid WHERE d.docemail LIKE '%@demo.example.invalid'")->fetch_row()[0]===0,'Ningún médico tiene jornadas superpuestas');
$before=catalog_digest($database);
$result=seed_demo_catalog($database);
catalog_check($result['doctors']===0 && $result['schedules']===0 && $result['skipped']===96,'Segunda carga no agrega duplicados');
catalog_check($before===catalog_digest($database),'Segunda carga conserva todos los registros y claves');
$doctor=$demo[0];
$edited='Nombre editado por administración';
$stmt=$database->prepare('UPDATE doctor SET docname=? WHERE docid=?'); $stmt->bind_param('si',$edited,$doctor['docid']); $stmt->execute();
try {
    seed_demo_catalog($database);
    catalog_check($database->query('SELECT docname FROM doctor WHERE docid='.(int)$doctor['docid'])->fetch_row()[0]===$edited,'La carga respeta cambios hechos por administración');
} finally {
    $stmt=$database->prepare('UPDATE doctor SET docname=? WHERE docid=?'); $stmt->bind_param('si',$doctor['docname'],$doctor['docid']); $stmt->execute();
}
