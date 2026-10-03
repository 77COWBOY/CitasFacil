<?php
if ($_SERVER['REQUEST_METHOD']==='POST') { require __DIR__.'/schedule.php'; exit; }
require __DIR__.'/includes/auth.php';
$now=date('Y-m-d H:i:s');
$name=$account['pname']??$account['docname']??'Administración';
$first=explode(' ',$name)[0];
$hour=(int)date('G');
$greeting=$hour<12?'Buenos días':($hour<18?'Buenas tardes':'Buenas noches');
$homeTitle=$role==='a'?'Resumen de la clínica':$greeting.', '.$first;
$homeSubtitle=['p'=>'Gestiona tus citas y encuentra especialistas disponibles.','d'=>'Tu agenda, tus pacientes y tus próximas consultas.','a'=>'Supervisa médicos, pacientes y actividad de la clínica.'][$role];
$join=' FROM appointment a JOIN schedule s ON s.scheduleid=a.scheduleid JOIN doctor d ON d.docid=s.docid JOIN patient p ON p.pid=a.pid';
$where=' WHERE 1=1'; $params=[];
if ($role==='p') { $where.=' AND a.pid=?'; $params[]=$account['pid']; }
if ($role==='d') { $where.=' AND s.docid=?'; $params[]=$account['docid']; }
$appointments=query('SELECT a.appoid,a.apponum,s.title,s.scheduledate,s.scheduletime,d.docname,p.pname'.$join.$where.' AND TIMESTAMP(s.scheduledate,s.scheduletime)>=? ORDER BY s.scheduledate,s.scheduletime,a.apponum LIMIT 5',[...$params,$now])->get_result()->fetch_all(MYSQLI_ASSOC);
$active=(int)query('SELECT COUNT(*) AS n'.$join.$where.' AND TIMESTAMP(s.scheduledate,s.scheduletime)>=?',[...$params,$now])->get_result()->fetch_assoc()['n'];
$past=(int)query('SELECT COUNT(*) AS n'.$join.$where.' AND TIMESTAMP(s.scheduledate,s.scheduletime)<?',[...$params,$now])->get_result()->fetch_assoc()['n'];
$today=(int)query('SELECT COUNT(*) AS n'.$join.$where.' AND s.scheduledate=?',[...$params,date('Y-m-d')])->get_result()->fetch_assoc()['n'];
$doctorCount=(int)query('SELECT COUNT(*) AS n FROM doctor')->get_result()->fetch_assoc()['n'];
$patientCount=$role==='d'
    ? (int)query('SELECT COUNT(DISTINCT a.pid) AS n FROM appointment a JOIN schedule s ON s.scheduleid=a.scheduleid WHERE s.docid=?',[$account['docid']])->get_result()->fetch_assoc()['n']
    : ($role==='a'?(int)query('SELECT COUNT(*) AS n FROM patient')->get_result()->fetch_assoc()['n']:0);
$scheduleWhere=' WHERE TIMESTAMP(scheduledate,scheduletime)>=?'; $scheduleArgs=[$now];
if ($role==='d') { $scheduleWhere.=' AND docid=?'; $scheduleArgs[]=$account['docid']; }
$scheduleCount=(int)query('SELECT COUNT(*) AS n FROM schedule'.$scheduleWhere,$scheduleArgs)->get_result()->fetch_assoc()['n'];
$next=$appointments[0]??null;
function short_date($date) {
    $months=['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
    return date('d',strtotime($date)).' '.$months[(int)date('n',strtotime($date))-1];
}
function stat_card($tone,$symbol,$label,$value,$subtitle,$url,$link) {
    echo '<article class="stat-card '.h($tone).'"><span class="stat-icon" aria-hidden="true">'.icon($symbol).'</span><div><p>'.h($label).'</p><strong>'.h($value).'</strong><small>'.h($subtitle).'</small></div><a href="'.h($url).'">'.h($link).' <span aria-hidden="true">›</span></a></article>';
}
function next_appointment($next,$role) {
    echo '<section class="surface next-appointment"><div class="section-heading"><h2>'.($role==='p'?'Tu próxima cita':'Próxima consulta').'</h2><a href="/appointments.php">Ver todas las citas <span aria-hidden="true">›</span></a></div>';
    if (!$next) { echo '<div class="empty-state">'.icon('calendar').'<h3>No hay citas próximas</h3><p>'.($role==='p'?'Reserva tu primera consulta con un médico disponible.':'Las próximas reservas aparecerán aquí.').'</p><a class="button" href="/schedule.php">'.($role==='p'?'Explorar horarios':'Ver horarios').'</a></div>'; }
    else {
        $person=$role==='p'?$next['docname']:$next['pname'];
        echo '<div class="appointment-summary"><div class="person"><span class="avatar">'.h(mb_substr($person,0,1)).'</span><div><h3>'.h($person).'</h3><p>'.h($next['title']).'</p><small>'.($role==='p'?'Consulta médica':'Médico: '.h($next['docname'])).'</small></div></div><div class="appointment-info"><small>'.icon('calendar').' Fecha</small><strong>'.date('d/m/Y',strtotime($next['scheduledate'])).'</strong></div><div class="appointment-info"><small>'.icon('clock').' Hora</small><strong>'.date('h:i a',strtotime($next['scheduletime'])).'</strong></div><div class="appointment-info"><small>'.icon('ticket').' Turno</small><strong>'.h($next['apponum']).'</strong></div><a class="button" href="/appointments.php#appointment-'.(int)$next['appoid'].'">Ver detalles</a></div>';
    }
    echo '</section>';
}
function agenda_preview($appointments,$role) {
    echo '<section class="surface"><div class="section-heading"><h2>Próximas reservas</h2><a href="/appointments.php">Ver agenda ›</a></div>';
    if (!$appointments) echo '<p class="empty-copy">Todavía no hay reservas próximas.</p>';
    else {
        echo '<div class="table-scroll"><table><tr><th>Paciente</th><th>Consulta</th><th>Fecha</th><th>Hora</th><th>Turno</th></tr>';
        foreach($appointments as $a) echo '<tr><td>'.h($a['pname']).'</td><td>'.h($a['title']).'</td><td>'.date('d/m/Y',strtotime($a['scheduledate'])).'</td><td>'.substr($a['scheduletime'],0,5).'</td><td>'.h($a['apponum']).'</td></tr>';
        echo '</table></div>';
    }
    echo '</section>';
}
page($homeTitle); message();
require __DIR__.'/views/dashboards/'.['p'=>'patient','d'=>'doctor','a'=>'admin'][$role].'.php';
endpage();
