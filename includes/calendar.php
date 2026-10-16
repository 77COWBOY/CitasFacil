<?php
function calendar_doctors($role,$profile) {
    return query('SELECT d.docid,d.docname,d.docemail,d.specialties,sp.sname FROM doctor d LEFT JOIN specialties sp ON sp.id=d.specialties'.($role==='d'?' WHERE d.docid=?':'').' ORDER BY d.docname',$role==='d'?[$profile['docid']]:[])->get_result()->fetch_all(MYSQLI_ASSOC);
}
function doctor_availability($ids) {
    if (!$ids) return [];
    $placeholders=implode(',',array_fill(0,count($ids),'?'));
    $windows=query('SELECT * FROM schedule WHERE docid IN ('.$placeholders.') AND scheduledate>=? AND endtime IS NOT NULL AND TIMESTAMP(scheduledate,endtime)>? ORDER BY scheduledate,scheduletime',[...$ids,date('Y-m-d'),date('Y-m-d H:i:s')])->get_result()->fetch_all(MYSQLI_ASSOC);
    $bookings=query('SELECT a.appoid,a.starts_at,a.ends_at,s.docid,s.scheduledate FROM appointment a JOIN schedule s ON s.scheduleid=a.scheduleid WHERE s.docid IN ('.$placeholders.') AND '.appointment_end_sql().'>?',[...$ids,date('Y-m-d H:i:s')])->get_result()->fetch_all(MYSQLI_ASSOC);
    $byDay=[]; $result=[];
    foreach ($bookings as $a) $byDay[$a['docid'].':'.$a['scheduledate']][]=$a;
    foreach ($windows as $s) {
        $free=array_values(array_filter(schedule_slots($s,$byDay[$s['docid'].':'.$s['scheduledate']]??[]),fn($slot)=>$slot['available']));
        if (!$free) continue;
        if (!isset($result[$s['docid']])) $result[$s['docid']]=['sessions'=>0,'next_start'=>$free[0]['start']];
        $result[$s['docid']]['sessions']++;
    }
    return $result;
}
function calendar_data($role,$profile,$from,$to,$doctor=0,$specialty=0,$except=0) {
    $start=valid_local_datetime($from.' 00:00'); $end=valid_local_datetime($to.' 00:00');
    if (!$start || !$end || $end<=$start || $end->getTimestamp()-$start->getTimestamp()>42*86400) throw new RuntimeException('Selecciona un período de hasta seis semanas.');
    if ($role==='d') $doctor=(int)$profile['docid'];
    if ($except) {
        $allowed=query('SELECT pid FROM appointment WHERE appoid=?',[$except])->get_result()->fetch_assoc();
        if ($role!=='p' || !$allowed || $allowed['pid']!=$profile['pid']) throw new RuntimeException('Cita no autorizada.');
    }
    $sql='SELECT s.*,d.docname,d.docemail,sp.sname FROM schedule s JOIN doctor d ON d.docid=s.docid LEFT JOIN specialties sp ON sp.id=d.specialties WHERE s.scheduledate>=? AND s.scheduledate<?';
    $args=[$from,$to];
    if ($doctor) { $sql.=' AND s.docid=?'; $args[]=$doctor; }
    if ($specialty) { $sql.=' AND d.specialties=?'; $args[]=$specialty; }
    $schedules=query($sql.' ORDER BY s.scheduledate,s.scheduletime',$args)->get_result()->fetch_all(MYSQLI_ASSOC);
    // La ocupación se consulta en bloque. No se envían datos de otros pacientes.
    $busy=query('SELECT a.appoid,a.starts_at,a.ends_at,s.docid,s.scheduledate FROM appointment a JOIN schedule s ON s.scheduleid=a.scheduleid WHERE s.scheduledate>=? AND s.scheduledate<?'.($doctor?' AND s.docid=?':''),$doctor?[$from,$to,$doctor]:[$from,$to])->get_result()->fetch_all(MYSQLI_ASSOC);
    $grouped=[];
    foreach ($busy as $a) $grouped[$a['docid'].':'.$a['scheduledate']][]=$a;
    $windows=[];
    foreach ($schedules as $s) {
        $windows[]=['id'=>(int)$s['scheduleid'],'doctor_id'=>(int)$s['docid'],'doctor'=>$s['docname'],'specialty'=>$s['sname'],'date'=>$s['scheduledate'],'start'=>substr($s['scheduletime'],0,5),'end'=>substr($s['endtime']??'',0,5),'duration'=>(int)$s['duration_minutes'],'slots'=>schedule_slots($s,$grouped[$s['docid'].':'.$s['scheduledate']]??[],null,$except)];
    }
    [$where,$params]=role_appointment_filter($role,$profile);
    $where.=' AND s.scheduledate>=? AND s.scheduledate<?'; array_push($params,$from,$to);
    if ($doctor) { $where.=' AND s.docid=?'; $params[]=$doctor; }
    $appointments=query(appointment_select_sql().' WHERE '.$where.' ORDER BY '.appointment_start_sql(),$params)->get_result()->fetch_all(MYSQLI_ASSOC);
    $events=array_map(fn($a)=>['id'=>(int)$a['appoid'],'doctor_id'=>(int)$a['docid'],'doctor'=>$a['docname'],'patient'=>$role==='p'?null:$a['pname'],'title'=>$a['title'],'start'=>$a['starts_at']??$a['scheduledate'].' '.substr($a['scheduletime'],0,5),'end'=>$a['ends_at']??$a['effective_end'],'pending_time'=>empty($a['starts_at']),'active'=>$a['effective_end']>date('Y-m-d H:i:s')],$appointments);
    return ['today'=>date('Y-m-d'),'role'=>$role,'schedules'=>$windows,'appointments'=>$events,'blocked'=>$role==='p'?(bool)active_appointment($profile['pid'],$except):false];
}
