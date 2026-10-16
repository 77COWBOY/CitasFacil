<?php
// Reglas compartidas por la agenda, el panel y las reservas.
function appointment_end_sql() {
    return "COALESCE(a.ends_at,TIMESTAMP(s.scheduledate,COALESCE(s.endtime,'23:59:59')))";
}
function appointment_start_sql() { return 'COALESCE(a.starts_at,TIMESTAMP(s.scheduledate,s.scheduletime))'; }
function appointment_join_sql() {
    return ' FROM appointment a JOIN schedule s ON s.scheduleid=a.scheduleid JOIN doctor d ON d.docid=s.docid JOIN patient p ON p.pid=a.pid';
}
function appointment_select_sql() {
    return 'SELECT a.*,s.docid,s.title,s.scheduledate,s.scheduletime,s.endtime,d.docname,d.docemail,p.pname,'.appointment_end_sql().' AS effective_end,'.appointment_start_sql().' AS effective_start'.appointment_join_sql();
}
function role_appointment_filter($role,$profile) {
    if ($role==='p') return ['a.pid=?',[$profile['pid']]];
    if ($role==='d') return ['s.docid=?',[$profile['docid']]];
    return ['1=1',[]];
}
function active_appointment($pid,$except=0) {
    return query(appointment_select_sql().' WHERE a.pid=? AND a.appoid<>? AND '.appointment_end_sql().'>? ORDER BY '.appointment_start_sql().' LIMIT 1',[$pid,$except,date('Y-m-d H:i:s')])->get_result()->fetch_assoc();
}
function appointment_time($a) {
    return !empty($a['starts_at'])?date('H:i',strtotime($a['starts_at'])).' – '.date('H:i',strtotime($a['ends_at'])):'Hora por confirmar';
}
function appointment_list($role,$profile,$history=false,$date='',$limit=0) {
    [$where,$args]=role_appointment_filter($role,$profile);
    $where.=' AND '.appointment_end_sql().($history?'<=?':'>?'); $args[]=date('Y-m-d H:i:s');
    if ($date!=='') { $where.=' AND s.scheduledate=?'; $args[]=$date; }
    return query(appointment_select_sql().' WHERE '.$where.' ORDER BY '.appointment_start_sql().($history?' DESC':' ASC').($limit?' LIMIT '.(int)$limit:''),$args)->get_result()->fetch_all(MYSQLI_ASSOC);
}
function valid_local_datetime($text) {
    $dt=DateTimeImmutable::createFromFormat('!Y-m-d H:i',$text);
    return $dt && $dt->format('Y-m-d H:i')===$text?$dt:null;
}
function schedule_slots($s,$bookings,$now=null,$except=0) {
    $now=$now??date('Y-m-d H:i:s'); $result=[];
    $start=valid_local_datetime($s['scheduledate'].' '.substr($s['scheduletime'],0,5));
    $end=valid_local_datetime($s['scheduledate'].' '.substr($s['endtime']??'',0,5));
    $duration=(int)($s['duration_minutes']??30);
    if (!$start || !$end || !in_array($duration,[15,20,30,45,60],true) || $end<=$start) return [];
    for ($from=$start;$from->modify('+'.$duration.' minutes')<=$end;$from=$to) {
        $to=$from->modify('+'.$duration.' minutes');
        if ($from->format('Y-m-d H:i:s')<=$now) continue;
        $available=true;
        foreach ($bookings as $a) {
            if ((int)$a['appoid']===$except) continue;
            if (empty($a['starts_at'])) { $available=false; break; }
            if ($a['starts_at']<$to->format('Y-m-d H:i:s') && $a['ends_at']>$from->format('Y-m-d H:i:s')) { $available=false; break; }
        }
        $result[]=['start'=>$from->format('Y-m-d H:i'),'end'=>$to->format('Y-m-d H:i'),'label'=>$from->format('H:i'),'available'=>$available];
    }
    return $result;
}
function book_appointment($pid,$scheduleId,$startText,$appointmentId=0) {
    global $database;
    $database->begin_transaction();
    try {
        if (!query('SELECT pid FROM patient WHERE pid=? FOR UPDATE',[$pid])->get_result()->num_rows) throw new RuntimeException('Paciente no encontrado.');
        $target=query('SELECT docid FROM schedule WHERE scheduleid=?',[$scheduleId])->get_result()->fetch_assoc();
        if (!$target) throw new RuntimeException('El horario ya no está disponible.');
        $old=$appointmentId?query(appointment_select_sql().' WHERE a.appoid=? AND a.pid=?',[$appointmentId,$pid])->get_result()->fetch_assoc():null;
        if ($appointmentId && (!$old || $old['effective_end']<=date('Y-m-d H:i:s'))) throw new RuntimeException('No puedes cambiar esta cita.');
        $doctors=array_unique([(int)$target['docid'],(int)($old['docid']??$target['docid'])]); sort($doctors);
        foreach ($doctors as $docid) query('SELECT docid FROM doctor WHERE docid=? FOR UPDATE',[$docid]);
        $s=query('SELECT * FROM schedule WHERE scheduleid=? FOR UPDATE',[$scheduleId])->get_result()->fetch_assoc();
        if (!$s || $s['docid']!=$target['docid']) throw new RuntimeException('El horario cambió. Actualiza la agenda.');
        // La fila del paciente ya está bloqueada desde antes de abrir el snapshot.
        if (active_appointment($pid,$appointmentId)) throw new RuntimeException('Ya tienes una cita activa. Puedes cambiarla desde Mis citas.');
        $start=valid_local_datetime($startText);
        if (!$start || $start<=new DateTimeImmutable()) throw new RuntimeException('Selecciona una hora futura válida.');
        $duration=(int)$s['duration_minutes'];
        $end=$start->modify('+'.$duration.' minutes');
        $window=valid_local_datetime($s['scheduledate'].' '.substr($s['scheduletime'],0,5));
        if (!in_array($duration,[15,20,30,45,60],true) || !$window || empty($s['endtime']) || $start<$window || $end->format('Y-m-d H:i:s')>$s['scheduledate'].' '.$s['endtime'] || ($start->getTimestamp()-$window->getTimestamp())%($duration*60)!==0) throw new RuntimeException('La hora seleccionada no pertenece al horario del médico.');
        $busy=query('SELECT a.appoid FROM appointment a JOIN schedule s ON s.scheduleid=a.scheduleid WHERE s.docid=? AND a.appoid<>? AND ((a.starts_at<? AND a.ends_at>?) OR (a.starts_at IS NULL AND s.scheduledate=?)) FOR UPDATE',[$s['docid'],$appointmentId,$end->format('Y-m-d H:i:s'),$start->format('Y-m-d H:i:s'),$s['scheduledate']])->get_result()->num_rows;
        if ($busy) throw new RuntimeException('Esa hora acaba de ocuparse. Elige otra hora.');
        if ($appointmentId) query('UPDATE appointment SET scheduleid=?,appodate=?,starts_at=?,ends_at=? WHERE appoid=? AND pid=?',[$scheduleId,$s['scheduledate'],$start->format('Y-m-d H:i:s'),$end->format('Y-m-d H:i:s'),$appointmentId,$pid]);
        else query('INSERT INTO appointment(pid,scheduleid,appodate,starts_at,ends_at) VALUES (?,?,?,?,?)',[$pid,$scheduleId,$s['scheduledate'],$start->format('Y-m-d H:i:s'),$end->format('Y-m-d H:i:s')]);
        $database->commit();
    } catch (Throwable $e) { $database->rollback(); throw $e; }
}
function cancel_appointment($role,$profile,$id) {
    global $database;
    $database->begin_transaction();
    try {
        $a=query(appointment_select_sql().' WHERE a.appoid=?',[$id])->get_result()->fetch_assoc();
        if (!$a || ($role==='p' && $a['pid']!=$profile['pid']) || ($role==='d' && $a['docid']!=$profile['docid'])) throw new RuntimeException('La cita no existe o no tienes permiso para cancelarla.');
        query('SELECT pid FROM patient WHERE pid=? FOR UPDATE',[$a['pid']]);
        $a=query(appointment_select_sql().' WHERE a.appoid=? FOR UPDATE',[$id])->get_result()->fetch_assoc();
        if (!$a || $a['effective_end']<=date('Y-m-d H:i:s')) throw new RuntimeException('La cita ya finalizó o fue cancelada.');
        if (($role==='p' && $a['pid']!=$profile['pid']) || ($role==='d' && $a['docid']!=$profile['docid'])) throw new RuntimeException('La cita cambió y ya no tienes permiso para cancelarla.');
        query('SELECT docid FROM doctor WHERE docid=? FOR UPDATE',[$a['docid']]);
        query('DELETE FROM appointment WHERE appoid=?',[$id]);
        $database->commit();
    } catch (Throwable $e) { $database->rollback(); throw $e; }
}
function save_schedule($role,$profile,$data) {
    global $database;
    if (!in_array($role,['a','d'],true)) throw new RuntimeException('Acción no permitida.');
    $docid=$role==='d'?$profile['docid']:(int)($data['docid']??0);
    $id=(int)($data['id']??0); $date=$data['date']??''; $time=$data['time']??''; $end=$data['endtime']??'';
    $duration=filter_var($data['duration']??'30',FILTER_VALIDATE_INT);
    $from=valid_local_datetime($date.' '.$time); $to=valid_local_datetime($date.' '.$end);
    if (!$from || !$to || $from<=new DateTimeImmutable() || $to<=$from) throw new RuntimeException('Revisa la fecha y las horas de inicio y salida.');
    if (!in_array($duration,[15,20,30,45,60],true) || $to->getTimestamp()-$from->getTimestamp()<$duration*60) throw new RuntimeException('El horario debe permitir al menos una consulta de la duración elegida.');
    $database->begin_transaction();
    try {
        $doc=query('SELECT d.docid,sp.sname FROM doctor d LEFT JOIN specialties sp ON sp.id=d.specialties WHERE d.docid=? FOR UPDATE',[$docid])->get_result()->fetch_assoc();
        if (!$doc) throw new RuntimeException('Selecciona un médico válido.');
        $old=$id?query('SELECT * FROM schedule WHERE scheduleid=? FOR UPDATE',[$id])->get_result()->fetch_assoc():null;
        if ($id && (!$old || $old['docid']!=$docid)) throw new RuntimeException('Horario no autorizado. Conserva el médico al editar.');
        $bookings=$id?query('SELECT * FROM appointment WHERE scheduleid=? FOR UPDATE',[$id])->get_result()->fetch_all(MYSQLI_ASSOC):[];
        if ($bookings && ($old['scheduledate']!==$date || substr($old['scheduletime'],0,5)!==$time || (int)$old['duration_minutes']!==$duration)) throw new RuntimeException('Un horario con citas conserva la fecha, el inicio y la duración.');
        foreach ($bookings as $a) if (!empty($a['ends_at']) && $a['ends_at']>$to->format('Y-m-d H:i:s')) throw new RuntimeException('La salida dejaría una cita fuera del horario.');
        if (query('SELECT scheduleid FROM schedule WHERE docid=? AND scheduledate=? AND scheduleid<>? AND scheduletime<? AND COALESCE(endtime,ADDTIME(scheduletime,\'00:00:01\'))>? LIMIT 1 FOR UPDATE',[$docid,$date,$id,$end,$time])->get_result()->num_rows) throw new RuntimeException('El horario se superpone con otro del mismo médico.');
        $title=trim($data['title']??($old['title']??'Consulta de '.mb_strtolower($doc['sname']??'medicina general')));
        if (mb_strlen($title)>255) throw new RuntimeException('El nombre de la consulta es demasiado largo.');
        if ($id) query('UPDATE schedule SET title=?,scheduledate=?,scheduletime=?,endtime=?,duration_minutes=? WHERE scheduleid=?',[$title,$date,$time,$end,$duration,$id]);
        else query('INSERT INTO schedule(docid,title,scheduledate,scheduletime,endtime,duration_minutes) VALUES (?,?,?,?,?,?)',[$docid,$title,$date,$time,$end,$duration]);
        $database->commit();
    } catch (Throwable $e) { $database->rollback(); throw $e; }
}
function delete_schedule($role,$profile,$id) {
    global $database;
    if (!in_array($role,['a','d'],true)) throw new RuntimeException('Acción no permitida.');
    $database->begin_transaction();
    try {
        $s=query('SELECT docid FROM schedule WHERE scheduleid=?',[$id])->get_result()->fetch_assoc();
        if (!$s || ($role==='d' && $s['docid']!=$profile['docid'])) throw new RuntimeException('Horario no autorizado.');
        query('SELECT docid FROM doctor WHERE docid=? FOR UPDATE',[$s['docid']]);
        $s=query('SELECT * FROM schedule WHERE scheduleid=? FOR UPDATE',[$id])->get_result()->fetch_assoc();
        if (!$s) throw new RuntimeException('El horario ya no existe.');
        if (query('SELECT appoid FROM appointment WHERE scheduleid=? FOR UPDATE',[$id])->get_result()->num_rows) throw new RuntimeException('Este horario tiene citas. Reprográmalas o cancélalas antes de eliminarlo.');
        query('DELETE FROM schedule WHERE scheduleid=?',[$id]); $database->commit();
    } catch (Throwable $e) { $database->rollback(); throw $e; }
}
