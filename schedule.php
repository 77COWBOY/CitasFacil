<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/calendar.php';
require __DIR__.'/includes/appointment-actions.php';
$error=handle_appointment_action($role,$account);
$edit=null; $reschedule=null;
if ($role!=='p' && !empty($_GET['edit'])) {
    $edit=query('SELECT * FROM schedule WHERE scheduleid=?'.($role==='d'?' AND docid=?':''),$role==='d'?[(int)$_GET['edit'],$account['docid']]:[(int)$_GET['edit']])->get_result()->fetch_assoc();
    if (!$edit) $error='Horario no encontrado o no autorizado.';
}
if ($role==='p' && !empty($_GET['reschedule'])) {
    $reschedule=query(appointment_select_sql().' WHERE a.appoid=? AND a.pid=? AND '.appointment_end_sql().'>?',[(int)$_GET['reschedule'],$account['pid'],date('Y-m-d H:i:s')])->get_result()->fetch_assoc();
    if (!$reschedule) $error='No puedes cambiar esa cita.';
}
$active=$role==='p'?active_appointment($account['pid']):null;
$doctors=calendar_doctors($role,$account);
$specialty=$role==='d'?0:(int)($_GET['specialty']??0);
$doctorId=(int)($_GET['doctor']??($reschedule['docid']??($edit['docid']??0)));
if ($role==='d') $doctorId=(int)$account['docid'];
$matching=array_values(array_filter($doctors,fn($d)=>!$specialty || (int)$d['specialties']===$specialty));
if ($doctorId && !in_array($doctorId,array_map('intval',array_column($matching,'docid')),true)) $doctorId=0;
if ($role==='p' && !$doctorId && $matching) {
    $next=query('SELECT s.docid FROM schedule s JOIN doctor d ON d.docid=s.docid WHERE s.endtime IS NOT NULL AND TIMESTAMP(s.scheduledate,s.endtime)>? AND (?=0 OR d.specialties=?) ORDER BY s.scheduledate,s.scheduletime LIMIT 1',[date('Y-m-d H:i:s'),$specialty,$specialty])->get_result()->fetch_assoc();
    $doctorId=(int)($next['docid']??$matching[0]['docid']);
}
$selected=$_GET['date']??date('Y-m-d');
if (!valid_local_datetime($selected.' 00:00')) $selected=date('Y-m-d');
$month=$_GET['month']??substr($selected,0,7);
$monthDate=valid_local_datetime($month.'-01 00:00')?:new DateTimeImmutable('first day of this month midnight');
$gridStart=$monthDate->modify('monday this week');
$data=calendar_data($role,$account,$gridStart->format('Y-m-d'),$gridStart->modify('+42 days')->format('Y-m-d'),$doctorId,$specialty,(int)($reschedule['appoid']??0));
if (empty($_GET['date'])) {
    foreach ($data['schedules'] as $s) if (array_filter($s['slots'],fn($slot)=>$slot['available'])) { $selected=$s['date']; break; }
}
$homeSubtitle=$role==='p'?($reschedule?'Elige una nueva hora para tu cita.':'Tu próxima consulta, a la hora que te viene bien.'):'Tu disponibilidad y tus consultas en una sola agenda.';
page($role==='p'?($reschedule?'Cambiar mi cita':'Hacer cita'):'Mi agenda');
message($error);
require __DIR__.'/views/calendar.php';
if ($role!=='p') require __DIR__.'/views/schedule-editor.php';
endpage();
