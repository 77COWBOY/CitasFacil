<?php
if (PHP_SAPI!=='cli') exit;
if (!str_starts_with(getenv('EDOC_DB_NAME')?:'','edoc_calendar_check_') || !getenv('EDOC_TEST_URL')) throw new RuntimeException('Usa una base edoc_calendar_check_* y su EDOC_TEST_URL de pruebas.');
require __DIR__.'/http-client.php';
$cookie=tempnam(sys_get_temp_dir(),'edoc-times');
$suffix=bin2hex(random_bytes(5)); $patients=[]; $doctors=[]; $emails=[];
function fixture($sql,$args=[]) {
    global $database;
    $s=$database->prepare($sql); if ($args) $s->bind_param(str_repeat('s',count($args)),...$args); $s->execute(); return $s;
}
function action($data,$path='/schedule.php') {
    [, $html]=request($path);
    return request($path,['csrf'=>csrf($html),...$data]);
}
function calendar_get($doc,$day,$extra=[]) {
    $to=date('Y-m-d',strtotime($day.' +1 day'));
    return request('/calendar-data.php?'.http_build_query(['from'=>$day,'to'=>$to,'doctor'=>$doc,...$extra]));
}
try {
    foreach (['d','d','p','p','p'] as $i=>$type) {
        $email='calendar-'.$suffix.'-'.$i.'@example.invalid'; $emails[]=$email;
        fixture('INSERT INTO webuser(email,usertype) VALUES (?,?)',[$email,$type]);
        $hash=password_hash('123',PASSWORD_DEFAULT);
        if ($type==='d') { fixture('INSERT INTO doctor(docname,docemail,docpassword,specialties) VALUES (?,?,?,18)',['QA Agenda '.$i,$email,$hash]); $doctors[]=$database->insert_id; }
        else { fixture('INSERT INTO patient(pname,pemail,ppassword) VALUES (?,?,?)',['QA Reserva '.$i,$email,$hash]); $patients[]=$database->insert_id; }
    }
    login($emails[0]);
    $day=date('Y-m-d',strtotime('+3 days'));
    $base=['action'=>'schedule','title'=>'QA Agenda '.$suffix,'date'=>$day,'time'=>'09:00','endtime'=>'11:00','duration'=>30];
    check(action($base)[0]===302,'Publicar disponibilidad sin cupos');
    $sid=(int)fixture('SELECT scheduleid FROM schedule WHERE title=?',[$base['title']])->get_result()->fetch_assoc()['scheduleid'];
    foreach ([['time'=>'10:00','endtime'=>'09:00'],['duration'=>0],['duration'=>17],['endtime'=>'09:15'],['date'=>'2026-02-30']] as $invalid) check(action([...$base,...$invalid])[0]===200,'Rango o duración inválida rechazada '.json_encode($invalid));
    [$code,$html]=action([...$base,'time'=>'10:00','endtime'=>'12:00']);
    check($code===200 && str_contains($html,'superpone'),'Superposición del médico rechazada');
    check(action([...$base,'title'=>$base['title'].' segundo','time'=>'11:00','endtime'=>'12:00','duration'=>20])[0]===302,'Horario contiguo con duración distinta');
    $sid2=(int)fixture('SELECT scheduleid FROM schedule WHERE title=?',[$base['title'].' segundo'])->get_result()->fetch_assoc()['scheduleid'];
    logout(); login($emails[2]);
    [$code,$json]=calendar_get($doctors[0],$day); $data=json_decode($json,true);
    check($code===200 && count($data['schedules'])===2,'API devuelve los horarios del médico elegido');
    check(count($data['schedules'][0]['slots'])===4 && count($data['schedules'][1]['slots'])===3,'Horas generadas según duración y límites');
    check($data['schedules'][0]['slots'][0]['label']==='09:00' && end($data['schedules'][0]['slots'])['label']==='10:30','Primera y última hora correctas');
    [, $html]=request('/schedule.php?doctor='.$doctors[0].'&date='.$day);
    check(str_contains($html,'calendar-grid') && str_contains($html,'name="slot"') && !str_contains($html,'Cupos'),'Calendario funcional sin JavaScript y sin cupos');
    check(!str_contains($html,'name="capacity"'),'No existe formulario de capacidad');
    foreach (['08:30','09:10','10:45','11:00','25:00'] as $time) check(action(['action'=>'book','id'=>$sid,'start'=>$day.' '.$time])[0]===200,'No reserva fuera del rango o fuera de cuadrícula '.$time);
    check(action(['action'=>'book','id'=>$sid,'start'=>'2020-01-01 09:00'])[0]===200,'Fecha pasada rechazada');
    check(request('/schedule.php',['csrf'=>'','action'=>'book','id'=>$sid,'start'=>$day.' 09:00'])[0]===403,'CSRF de reserva validado');
    fixture('UPDATE schedule SET nop=0 WHERE scheduleid=?',[$sid]);
    check(action(['action'=>'book','slot'=>$sid.'|'.$day.' 09:00'])[0]===302,'Reserva por hora ignora el antiguo dato de cupos');
    $a=fixture('SELECT * FROM appointment WHERE pid=?',[$patients[0]])->get_result()->fetch_assoc();
    $aid=(int)$a['appoid'];
    check($a['starts_at']===$day.' 09:00:00' && $a['ends_at']===$day.' 09:30:00' && $a['apponum']===null,'Reserva guarda inicio y fin sin número de turno');
    [, $html]=request('/appointments.php');
    check(str_contains($html,'09:00 – 09:30') && str_contains($html,'Cambiar hora'),'Cita muestra hora exacta y cambio de hora');
    [$code,$html]=action(['action'=>'book','id'=>$sid,'start'=>$day.' 09:30']);
    check($code===200 && str_contains($html,'Ya tienes una cita activa'),'Una cita activa incluso si otra hora está libre');
    logout(); login($emails[3]);
    [$code,$html]=action(['action'=>'book','id'=>$sid,'start'=>$day.' 09:00']);
    check($code===200 && str_contains($html,'acaba de ocuparse'),'Dos pacientes no reservan la misma hora');
    [$code,$json]=calendar_get($doctors[0],$day); $data=json_decode($json,true);
    check(!$data['schedules'][0]['slots'][0]['available'] && $data['schedules'][0]['slots'][1]['available'],'La API marca ocupación exacta y deja horas adyacentes');
    check($data['appointments']===[] && !str_contains($json,'QA Reserva 2'),'La API no revela citas ni nombres de otro paciente');
    check(action(['action'=>'book','id'=>$sid,'start'=>$day.' 09:30'])[0]===302,'Otro paciente reserva la siguiente media hora');
    logout(); login($emails[2]);
    [$code,$html]=action(['action'=>'reschedule','appointment_id'=>$aid,'id'=>$sid,'start'=>$day.' 09:30']);
    check($code===200 && str_contains($html,'acaba de ocuparse'),'Cambio a hora ocupada rechazado');
    check(fixture('SELECT starts_at FROM appointment WHERE appoid=?',[$aid])->get_result()->fetch_assoc()['starts_at']===$day.' 09:00:00','Cambio fallido conserva reserva original');
    check(action(['action'=>'reschedule','appointment_id'=>$aid,'id'=>$sid2,'start'=>$day.' 11:20'])[0]===302,'Cambio de día u horario mantiene la misma cita');
    $a=fixture('SELECT * FROM appointment WHERE appoid=?',[$aid])->get_result()->fetch_assoc();
    check($a['starts_at']===$day.' 11:20:00' && $a['ends_at']===$day.' 11:40:00','Cambio aplica la duración del nuevo horario');
    [, $json]=calendar_get($doctors[0],$day);
    check(json_decode($json,true)['schedules'][0]['slots'][0]['available'],'Cambiar cita libera la hora anterior');
    logout(); login($emails[4]);
    check(action(['action'=>'reschedule','appointment_id'=>$aid,'id'=>$sid,'start'=>$day.' 10:00'])[0]===200,'Paciente no cambia citas ajenas');
    check(action(['action'=>'cancel','id'=>$aid],'/appointments.php')[0]===200,'Paciente no cancela citas ajenas');
    check(calendar_get($doctors[0],$day,['reschedule'=>$aid])[0]===400,'API rechaza cambio de cita ajena');
    check(request('/calendar-data.php?from=2026-01-01&to=2027-01-01')[0]===400,'API limita período de consulta');
    check(request('/calendar-data.php?from=invalido&to=2026-01-01')[0]===400,'API valida fechas');
    logout(); login($emails[0]);
    check(action([...$base,'id'=>$sid,'duration'=>60])[0]===200,'No cambia duración de un horario reservado');
    check(action([...$base,'id'=>$sid2,'endtime'=>'11:30','time'=>'11:00','duration'=>20])[0]===200,'No acorta un horario dejando una cita fuera');
    check(action(['action'=>'delete_schedule','id'=>$sid])[0]===200,'No elimina un horario con citas');
    logout(); login($emails[1]);
    [$code,$json]=calendar_get($doctors[0],$day);
    check($code===200 && json_decode($json,true)['schedules']===[],'Médico solo consulta su agenda aunque cambie doctor en URL');
    check(action([...$base,'id'=>$sid])[0]===200,'Médico no edita disponibilidad ajena');
    check(action(['action'=>'cancel','id'=>$aid],'/appointments.php')[0]===200,'Médico no cancela cita ajena');
    logout(); login($emails[2]);
    check(action(['action'=>'cancel','id'=>$aid],'/appointments.php')[0]===302,'Paciente cancela su cita');
    check(action(['action'=>'book','id'=>$sid,'start'=>$day.' 10:00'])[0]===302,'Cancelar permite reservar otra hora');
    $a=fixture('SELECT appoid FROM appointment WHERE pid=?',[$patients[0]])->get_result()->fetch_assoc(); $aid=(int)$a['appoid'];
    fixture('UPDATE appointment SET starts_at=?,ends_at=? WHERE appoid=?',[date('Y-m-d H:i:s',time()-600),date('Y-m-d H:i:s',time()-60),$aid]);
    check(action(['action'=>'cancel','id'=>$aid],'/appointments.php')[0]===200,'No permite borrar una cita finalizada');
    check(action(['action'=>'book','id'=>$sid,'start'=>$day.' 10:30'])[0]===302,'La hora final de cada cita determina si sigue activa');
    [, $html]=request('/appointments.php?history=1'); check(str_contains($html,'Finalizada'),'Historial usa la hora final concreta');
    // Legacy: ninguna reserva anterior se convierte a una hora inventada.
    fixture('DELETE FROM appointment WHERE pid=?',[$patients[0]]);
    fixture('INSERT INTO appointment(pid,scheduleid,appodate) VALUES (?,?,?)',[$patients[0],$sid2,$day]);
    [, $html]=request('/appointments.php'); check(str_contains($html,'Hora por confirmar'),'Cita antigua se conserva sin inventar una hora');
    [, $json]=calendar_get($doctors[0],$day);
    check(count(array_filter(json_decode($json,true)['schedules'][1]['slots'],fn($slot)=>$slot['available']))===0,'Cita antigua pendiente protege la disponibilidad del día');
    fixture('DELETE FROM appointment WHERE pid=?',[$patients[0]]);
    foreach ($patients as $pid) fixture('DELETE FROM appointment WHERE pid=?',[$pid]);
    if ($second=getenv('EDOC_TEST_URL_SECOND')) {
        $originalCookie=$cookie; $raceCookies=[]; $tokens=[];
        try {
            foreach ([$emails[2],$emails[2],$emails[3]] as $email) {
                $cookie=tempnam(sys_get_temp_dir(),'edoc-time-race'); $raceCookies[]=$cookie; login($email);
                [, $html]=request('/schedule.php'); $tokens[]=csrf($html);
            }
            $race=function($people,$times) use ($raceCookies,$tokens,$sid,$day,$second) {
                $multi=curl_multi_init(); $handles=[];
                foreach ($people as $i=>$person) {
                    $ch=curl_init(($i? $second:getenv('EDOC_TEST_URL')).'/schedule.php');
                    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$raceCookies[$person],CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query(['csrf'=>$tokens[$person],'action'=>'book','id'=>$sid,'start'=>$day.' '.$times[$i]]),CURLOPT_TIMEOUT=>15]);
                    curl_multi_add_handle($multi,$ch); $handles[]=$ch;
                }
                do { curl_multi_exec($multi,$running); if ($running) curl_multi_select($multi,0.1); } while ($running);
                $codes=[];
                foreach ($handles as $ch) { $codes[]=curl_getinfo($ch,CURLINFO_RESPONSE_CODE); curl_multi_remove_handle($multi,$ch); curl_close($ch); }
                curl_multi_close($multi); sort($codes); return $codes;
            };
            check($race([0,1],['09:00','09:30'])===[200,302],'Dos sesiones del mismo paciente solo crean una cita');
            foreach ($patients as $pid) fixture('DELETE FROM appointment WHERE pid=?',[$pid]);
            check($race([0,2],['10:00','10:00'])===[200,302],'Dos pacientes simultáneos no comparten hora');
            check((int)fixture('SELECT COUNT(*) AS n FROM appointment WHERE scheduleid=?',[$sid])->get_result()->fetch_assoc()['n']===1,'Solo se guarda una reserva en la hora disputada');
            foreach ($patients as $pid) fixture('DELETE FROM appointment WHERE pid=?',[$pid]);
            check($race([0,2],['10:00','10:30'])===[302,302],'Pacientes distintos sí reservan horas consecutivas simultáneamente');
        } finally { $cookie=$originalCookie; foreach ($raceCookies as $file) unlink($file); }
    }
} finally {
    foreach ($patients as $pid) { fixture('DELETE FROM appointment WHERE pid=?',[$pid]); fixture('DELETE FROM patient WHERE pid=?',[$pid]); }
    foreach ($doctors as $docid) { fixture('DELETE FROM schedule WHERE docid=?',[$docid]); fixture('DELETE FROM doctor WHERE docid=?',[$docid]); }
    foreach ($emails as $email) fixture('DELETE FROM webuser WHERE email=?',[$email]);
    unlink($cookie);
}
