<?php
require __DIR__.'/includes/auth.php';
$profile=$account;
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    try {
        $action=$_POST['action']??'';
        if ($action==='book' && $role==='p') {
            $database->begin_transaction();
            $s=query('SELECT * FROM schedule WHERE scheduleid=? FOR UPDATE',[(int)($_POST['id']??0)])->get_result()->fetch_assoc();
            if (!$s || $s['scheduledate'].' '.$s['scheduletime']<=date('Y-m-d H:i:s')) throw new RuntimeException('El horario ya no está disponible.');
            $stats=query('SELECT COUNT(*) AS total, COALESCE(MAX(apponum),0) AS lastnum FROM appointment WHERE scheduleid=?',[$s['scheduleid']])->get_result()->fetch_assoc();
            if ($stats['total'] >= $s['nop']) throw new RuntimeException('No quedan cupos.');
            if (query('SELECT appoid FROM appointment WHERE pid=? AND scheduleid=?',[$profile['pid'],$s['scheduleid']])->get_result()->num_rows) throw new RuntimeException('Ya tienes una cita en este horario.');
            query('INSERT INTO appointment(pid,apponum,scheduleid,appodate) VALUES (?,?,?,?)',[$profile['pid'],$stats['lastnum']+1,$s['scheduleid'],$s['scheduledate']]);
            $database->commit();
        } elseif ($action==='cancel') {
            $id=(int)($_POST['id']??0);
            if ($role==='p') $deleted=query('DELETE FROM appointment WHERE appoid=? AND pid=?',[$id,$profile['pid']]);
            elseif ($role==='d') $deleted=query('DELETE a FROM appointment a JOIN schedule s ON s.scheduleid=a.scheduleid WHERE a.appoid=? AND s.docid=?',[$id,$profile['docid']]);
            else $deleted=query('DELETE FROM appointment WHERE appoid=?',[$id]);
            if ($deleted->affected_rows!==1) throw new RuntimeException('La cita no existe o no tienes permiso para cancelarla.');
        } elseif ($action==='delete_schedule' && in_array($role,['a','d'],true)) {
            $database->begin_transaction();
            $s=query('SELECT * FROM schedule WHERE scheduleid=? FOR UPDATE',[(int)($_POST['id']??0)])->get_result()->fetch_assoc();
            if (!$s || ($role==='d' && $s['docid']!=$profile['docid'])) throw new RuntimeException('Horario no autorizado.');
            if (query('SELECT appoid FROM appointment WHERE scheduleid=?',[$s['scheduleid']])->get_result()->num_rows) throw new RuntimeException('Cancela las citas de este horario antes de eliminarlo.');
            query('DELETE FROM schedule WHERE scheduleid=?',[$s['scheduleid']]);
            $database->commit();
        } elseif ($action==='schedule' && in_array($role,['a','d'],true)) {
            $docid=$role==='d' ? $profile['docid'] : (int)($_POST['docid']??0);
            $title=trim($_POST['title']??''); $date=$_POST['date']??''; $time=$_POST['time']??''; $capacity=(int)($_POST['capacity']??0);
            $dt=DateTime::createFromFormat('!Y-m-d H:i',$date.' '.$time);
            if (!$dt || $dt->format('Y-m-d H:i')!==$date.' '.$time || $dt<=new DateTime() || !$title || strlen($title)>255 || $capacity<1 || $capacity>9999) throw new RuntimeException('Revisa el título, fecha futura y cupos.');
            if (!query('SELECT docid FROM doctor WHERE docid=?',[$docid])->get_result()->num_rows) throw new RuntimeException('Selecciona un médico válido.');
            query('INSERT INTO schedule(docid,title,scheduledate,scheduletime,nop) VALUES (?,?,?,?,?)',[$docid,$title,$date,$time,$capacity]);
        } else { throw new RuntimeException('Acción no permitida.'); }
        $_SESSION['flash']='Operación realizada correctamente.'; go(($action==='cancel'?'/appointments.php':'/schedule.php'));
    } catch (Throwable $e) {
        $database->rollback();
        $error=$e instanceof RuntimeException && !($e instanceof mysqli_sql_exception) ? $e->getMessage() : 'No se pudo guardar la operación.';
        error_log($e->getMessage());
    }
}
$view=isset($appointmentView) ? 'appointments' : 'schedule';
page($view==='appointments' ? ($role==='p'?'Mis citas':'Citas registradas') : ($role==='p'?'Citas disponibles':'Horarios de consulta'));
if ($error) echo '<p class="error">'.h($error).'</p>';
if (isset($_SESSION['flash'])) { echo '<p class="notice">'.h($_SESSION['flash']).'</p>'; unset($_SESSION['flash']); }
if ($role!=='p' && $view==='schedule') {
    echo '<section class="panel" id="publish"><h2>Publicar horario de consulta</h2><form method="post">'.token().'<input type="hidden" name="action" value="schedule">';
    if ($role==='a') {
        echo '<label>Médico<select name="docid" required>';
        foreach(query('SELECT docid,docname FROM doctor ORDER BY docname')->get_result() as $doc) echo '<option value="'.h($doc['docid']).'">'.h($doc['docname']).'</option>';
        echo '</select></label>';
    }
    field('title','Título','text','maxlength="255"'); field('date','Fecha','date','min="'.date('Y-m-d').'"'); field('time','Hora','time'); field('capacity','Cupos','number','min="1" max="9999"');
    echo '<button>Publicar horario</button></form></section>';
}
if ($view==='schedule') {
echo '<section><h2>'.($role==='p'?'Horarios disponibles':'Horarios de consulta').'</h2>';
echo '<form method="get" class="filters"><label>Buscar consulta o médico<input name="q" value="'.h($_GET['q']??'').'"></label><label>Fecha<input type="date" name="date" value="'.h($_GET['date']??'').'"></label>';
if ($role!=='p') echo '<label>Mostrar<select name="history"><option value="0">Próximos horarios</option><option value="1" '.(!empty($_GET['history'])?'selected':'').'>Todos, incluidos anteriores</option></select></label>';
if ($role!=='d' && !empty($_GET['doctor'])) echo '<input type="hidden" name="doctor" value="'.h($_GET['doctor']).'">';
echo '<button>Filtrar</button></form><table><tr><th>Consulta</th><th>Médico</th><th>Fecha y hora</th><th>Cupos libres</th><th></th></tr>';
$sql='SELECT s.*, d.docname, (s.nop-(SELECT COUNT(*) FROM appointment a WHERE a.scheduleid=s.scheduleid)) AS available FROM schedule s JOIN doctor d ON d.docid=s.docid WHERE 1=1';
$args=[];
if ($role==='p' || empty($_GET['history'])) { $sql.=' AND TIMESTAMP(s.scheduledate,s.scheduletime)>?'; $args[]=date('Y-m-d H:i:s'); }
if (!empty($_GET['q'])) { $sql.=' AND (s.title LIKE ? OR d.docname LIKE ?)'; $args[]='%'.trim($_GET['q']).'%'; $args[]='%'.trim($_GET['q']).'%'; }
if (!empty($_GET['date'])) { $sql.=' AND s.scheduledate=?'; $args[]=$_GET['date']; }
if ($role==='d') { $sql.=' AND s.docid=?'; $args[]=$profile['docid']; }
if ($role!=='d' && !empty($_GET['doctor'])) { $sql.=' AND s.docid=?'; $args[]=(int)$_GET['doctor']; }
$rows=query($sql.' ORDER BY s.scheduledate,s.scheduletime',$args)->get_result();
if (!$rows->num_rows) echo '<tr><td colspan="5">No hay horarios publicados.</td></tr>';
foreach($rows as $s) {
    echo '<tr><td>'.h($s['title']).'</td><td>'.h($s['docname']).'</td><td>'.h($s['scheduledate'].' '.$s['scheduletime']).'</td><td>'.h($s['available']).'</td><td>';
    if ($role==='p' && $s['available']>0) echo '<form method="post">'.token().'<input type="hidden" name="action" value="book"><input type="hidden" name="id" value="'.h($s['scheduleid']).'"><button>Reservar</button></form>';
    if ($role!=='p') echo '<form method="post">'.token().'<input type="hidden" name="action" value="delete_schedule"><input type="hidden" name="id" value="'.h($s['scheduleid']).'"><button class="danger">Eliminar horario</button></form>';
    echo '</td></tr>';
}
echo '</table></section>';
}
if ($view==='appointments') {
echo '<section id="appointments"><form method="get" class="filters"><label>Fecha<input type="date" name="date" value="'.h($_GET['date']??'').'"></label><label>Mostrar<select name="history"><option value="0">Próximas citas</option><option value="1" '.(!empty($_GET['history'])?'selected':'').'>Citas anteriores</option></select></label><button>Filtrar citas</button></form><table><tr><th>Paciente</th><th>Médico</th><th>Consulta</th><th>Fecha y hora</th><th>Turno</th><th></th></tr>';
$sql='SELECT a.*,p.pname,d.docname,s.title,s.scheduledate,s.scheduletime FROM appointment a JOIN patient p ON p.pid=a.pid JOIN schedule s ON s.scheduleid=a.scheduleid JOIN doctor d ON d.docid=s.docid';
$args=[]; $sql.=' WHERE 1=1';
if ($role==='p') { $sql.=' AND a.pid=?'; $args[]=$profile['pid']; }
if ($role==='d') { $sql.=' AND s.docid=?'; $args[]=$profile['docid']; }
if (!empty($_GET['date'])) { $sql.=' AND s.scheduledate=?'; $args[]=$_GET['date']; }
else { $sql.=' AND TIMESTAMP(s.scheduledate,s.scheduletime)'.(!empty($_GET['history'])?'<':'>=').'?'; $args[]=date('Y-m-d H:i:s'); }
$rows=query($sql.' ORDER BY s.scheduledate'.(!empty($_GET['history'])?' DESC':' ASC').',s.scheduletime',$args)->get_result();
if (!$rows->num_rows) echo '<tr><td colspan="6">Todavía no hay citas.</td></tr>';
foreach($rows as $a) {
    echo '<tr id="appointment-'.(int)$a['appoid'].'"><td>'.h($a['pname']).'</td><td>'.h($a['docname']).'</td><td>'.h($a['title']).'</td><td>'.h($a['scheduledate'].' '.$a['scheduletime']).'</td><td>'.h($a['apponum']).'</td><td>';
    echo '<form method="post">'.token().'<input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="'.h($a['appoid']).'"><button>Cancelar</button></form>';
    echo '</td></tr>';
}
echo '</table></section>';
}
endpage();
