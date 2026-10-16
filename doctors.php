<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/calendar.php';
$error=''; $edit=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    if ($role!=='a') { http_response_code(403); exit('Acceso no autorizado.'); }
    try {
        $database->begin_transaction();
        $id=(int)($_POST['id']??0);
        $old=$id ? query('SELECT * FROM doctor WHERE docid=? FOR UPDATE',[$id])->get_result()->fetch_assoc() : null;
        if ($id && !$old) throw new RuntimeException('Médico no encontrado.');
        if (($_POST['action']??'')==='delete') {
            if (!$old || ($_POST['confirm']??'')!=='ELIMINAR') throw new RuntimeException('Escribe ELIMINAR para confirmar.');
            if (query('SELECT scheduleid FROM schedule WHERE docid=?',[$id])->get_result()->num_rows) throw new RuntimeException('Primero elimina los horarios del médico. Sus citas deben cancelarse antes.');
            query('DELETE FROM doctor WHERE docid=?',[$id]);
            query('DELETE FROM webuser WHERE email=?',[$old['docemail']]);
        } elseif (($_POST['action']??'')==='save') {
            $name=trim($_POST['name']??''); $email=strtolower(trim($_POST['email']??'')); $phone=trim($_POST['phone']??''); $nic=trim($_POST['nic']??''); $spec=(int)($_POST['specialties']??0); $pass=$_POST['password']??'';
            validate_contact($name,$email,$phone,$nic);
            if (!query('SELECT id FROM specialties WHERE id=?',[$spec])->get_result()->num_rows) throw new RuntimeException('Selecciona una especialidad.');
            if (!$old || $pass!=='') validate_password($pass);
            $hash=$pass!=='' ? password_hash($pass,PASSWORD_DEFAULT) : $old['docpassword'];
            if ($old) {
                query('UPDATE webuser SET email=? WHERE email=?',[$email,$old['docemail']]);
                query('UPDATE doctor SET docname=?,docemail=?,doctel=?,docnic=?,specialties=?,docpassword=? WHERE docid=?',[$name,$email,$phone,$nic,$spec,$hash,$id]);
            } else {
                query('INSERT INTO webuser(email,usertype) VALUES (?,?)',[$email,'d']);
                query('INSERT INTO doctor(docname,docemail,doctel,docnic,specialties,docpassword) VALUES (?,?,?,?,?,?)',[$name,$email,$phone,$nic,$spec,$hash]);
            }
        } else throw new RuntimeException('Acción inválida.');
        $database->commit(); success('/doctors.php');
    } catch (Throwable $e) { $database->rollback(); $error=friendly_error($e); }
}
if ($role==='a' && !empty($_GET['edit'])) $edit=query('SELECT * FROM doctor WHERE docid=?',[(int)$_GET['edit']])->get_result()->fetch_assoc();
page('Médicos y especialidades'); message($error);
$search=trim($_GET['q']??''); $spec=(int)($_GET['specialty']??0);
echo '<details class="filter-disclosure" '.($search==='' && !$spec?'open':'').'><summary>'.($search!=='' || $spec?'Cambiar búsqueda':'Buscar médico o especialidad').'</summary><form method="get" class="filters"><label>Buscar médico o especialidad<input name="q" maxlength="100" value="'.h($search).'"></label><label>Especialidad<select name="specialty"><option value="0">Todas</option>';
foreach(query('SELECT * FROM specialties ORDER BY sname')->get_result() as $s) echo '<option value="'.h($s['id']).'" '.($spec===(int)$s['id']?'selected':'').'>'.h($s['sname']).'</option>';
echo '</select></label><button>Buscar</button><a href="/doctors.php">Limpiar filtros</a></form></details>';
$rows=query('SELECT d.docid,d.docname,d.docemail,d.doctel,d.specialties,s.sname FROM doctor d LEFT JOIN specialties s ON s.id=d.specialties WHERE (d.docname LIKE ? OR s.sname LIKE ?) AND (?=0 OR d.specialties=?) ORDER BY d.docname',['%'.$search.'%','%'.$search.'%',$spec,$spec])->get_result()->fetch_all(MYSQLI_ASSOC);
$availability=doctor_availability(array_column($rows,'docid'));
$rows=array_map(fn($d)=>array_merge($d,$availability[$d['docid']]??[]),$rows);
$demoCount=count(array_filter($rows,fn($d)=>str_ends_with($d['docemail']??'','@demo.example.invalid')));
echo '<section><div class="directory-summary"><h2>Equipo médico</h2><p role="status">'.count($rows).' médico'.(count($rows)===1?'':'s').' encontrado'.(count($rows)===1?'':'s').'</p></div>';
if ($demoCount) echo '<p class="muted">Los profesionales marcados como Demostración son datos ficticios para explorar el sistema. Sus reservas se guardan en esta instalación de ejemplo.</p>';
echo '<table><tr><th>Médico</th><th>Especialidad</th><th>Disponibilidad</th><th>Contacto</th><th>Acciones</th></tr>';
if (!$rows) echo '<tr><td colspan="5">No se encontraron médicos. <a href="/doctors.php">Ver todos los médicos</a></td></tr>';
foreach($rows as $d) {
    echo '<tr><td>'.h($d['docname']).demo_badge($d['docemail']).'</td><td>'.h($d['sname']).'</td><td>'.(!empty($d['sessions'])?'<span class="availability">'.(int)$d['sessions'].' jornadas</span><small class="cell-detail">Próxima: '.date('d/m/Y · H:i',strtotime($d['next_start'])).'</small>':'<span class="muted">Sin horarios disponibles</span>').'</td><td>'.h($d['docemail']).'<br>'.h($d['doctel']).'</td><td><a href="/schedule.php?doctor='.(int)$d['docid'].'">'.($role==='p'?'Hacer cita':'Ver horarios').'</a>';
    if ($role==='a') echo ' · <a href="?edit='.(int)$d['docid'].'#editor">Editar</a><details><summary>Eliminar médico</summary><form method="post">'.token().'<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'.(int)$d['docid'].'"><label>Escribe ELIMINAR<input name="confirm" required pattern="ELIMINAR"></label><button class="danger">Eliminar</button></form></details>';
    echo '</td></tr>';
}
echo '</table></section>';
if ($role==='a') {
    echo '<section id="editor"><h2>'.($edit?'Editar médico':'Agregar médico').'</h2><form method="post">'.token().'<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="'.h($edit['docid']??0).'">';
    input_value('name','Nombre',$edit['docname']??''); input_value('email','Correo',$edit['docemail']??'','email'); input_value('phone','Teléfono',$edit['doctel']??'','tel',true,15); input_value('nic','Cédula',$edit['docnic']??'','text',true,15); specialty_select($edit['specialties']??0); input_value('password',$edit?'Nueva contraseña (dejar vacía para conservar)':'Contraseña','', 'password',!$edit,72);
    echo '<button>Guardar médico</button></form></section>';
}
endpage();
