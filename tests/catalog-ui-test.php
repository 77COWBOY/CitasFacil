<?php
if (PHP_SAPI!=='cli') exit;
if (!preg_match('/^edoc_(catalog|calendar)_check_/',getenv('EDOC_DB_NAME')?:'')) throw new RuntimeException('Usa una base de pruebas edoc_catalog_check_* o edoc_calendar_check_*.');
$cookie=tempnam(sys_get_temp_dir(),'edoc-catalog-ui');
require __DIR__.'/http-client.php';
try {
    foreach (['patient@edoc.com','doctor@edoc.com','admin@edoc.com'] as $email) {
        [, $html]=request('/login.php');
        check(request('/login.php',['csrf'=>csrf($html),'useremail'=>$email,'userpassword'=>'123'])[0]===302,'Acceso '.$email);
        [$code,$html]=request('/doctors.php');
        check($code===200 && str_contains($html,'17 médicos encontrados'),'Directorio completo '.$email);
        check(substr_count($html,'class="demo-label"')===16,'Médicos de demostración identificados '.$email);
        check(substr_count($html,'Próxima: ')===16,'Disponibilidad visible '.$email);
        check(str_contains($html,'Ginecología y obstetricia') && str_contains($html,'Medicina general') && !str_contains($html,'General Practice'),'Especialidades en español '.$email);
        [$code,$html]=request('/doctors.php?q=cardiologia');
        check($code===200 && str_contains($html,'Carlos Mendoza') && str_contains($html,'1 médico encontrado'),'Búsqueda sin acentos en español '.$email);
        [$code,$html]=request('/schedule.php?specialty=5');
        preg_match('~<script type="application/json" id="calendar-config">(.*?)</script>~s',$html,$match);
        $config=json_decode($match[1]??'',true);
        check($code===200 && !empty($config['data']),'Agenda accesible '.$email);
        if ($email!=='doctor@edoc.com') {
            check(count($config['data']['schedules'])===6,'Seis jornadas de cardiología '.$email);
            check(count(array_filter($config['data']['schedules'],fn($s)=>$s['specialty']!=='Cardiología'))===0,'Filtro no mezcla especialidades '.$email);
        }
        if ($email==='patient@edoc.com') {
            [, $html]=request('/doctors.php?q=dermatologia');
            check(str_contains($html,'1 médico encontrado') && str_contains($html,'Valeria López'),'Búsqueda de dermatología sin acentos');
            [, $html]=request('/schedule.php');
            check(str_contains($html,'calendar-grid') && !str_contains($html,'Cupos libres'),'Calendario sin tabla de cupos');
        }
        [, $html]=request('/dashboard.php');
        request('/logout.php',['csrf'=>csrf($html)]);
    }
    check(request('/tools/demo-data.php')[0]===404 && request('/tools/seed-demo.php')[0]===404,'Herramientas de carga privadas');
} finally { unlink($cookie); }
