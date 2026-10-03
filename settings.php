<?php
require __DIR__.'/includes/auth.php';
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    try {
        $database->begin_transaction();
        $account=query("SELECT * FROM $accountTable WHERE $emailColumn=? FOR UPDATE",[$_SESSION['user']])->get_result()->fetch_assoc();
        if (!$account || !password_verify($_POST['current_password']??'',$account[$passwordColumn])) throw new RuntimeException('La contraseña actual no es correcta.');
        if (($_POST['action']??'')==='delete' && $role!=='a') {
            if (($_POST['confirm']??'')!=='ELIMINAR') throw new RuntimeException('Escribe ELIMINAR para confirmar.');
            if ($role==='d' && query('SELECT scheduleid FROM schedule WHERE docid=?',[$account['docid']])->get_result()->num_rows) throw new RuntimeException('Solicita eliminar tus horarios antes de cerrar la cuenta.');
            if ($role==='p') query('DELETE FROM appointment WHERE pid=?',[$account['pid']]);
            query("DELETE FROM $accountTable WHERE $idColumn=?",[$account[$idColumn]]);
            query('DELETE FROM webuser WHERE email=?',[$_SESSION['user']]);
            $database->commit(); $_SESSION=[]; session_regenerate_id(true); go('/login.php');
        }
        if (($_POST['action']??'')!=='save') throw new RuntimeException('Acción no permitida.');
        $email=strtolower(trim($_POST['email']??''));
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>255) throw new RuntimeException('Correo inválido.');
        $pass=$_POST['password']??'';
        if ($pass!=='') { validate_password($pass); if ($pass!==($_POST['password_confirm']??'')) throw new RuntimeException('Las contraseñas no coinciden.'); }
        $hash=$pass===''?$account[$passwordColumn]:password_hash($pass,PASSWORD_DEFAULT);
        if ($role!=='a') {
            $name=trim($_POST['name']??''); $phone=trim($_POST['phone']??''); $nic=trim($_POST['nic']??'');
            validate_contact($name,$email,$phone,$nic);
            if ($role==='p') {
                $address=trim($_POST['address']??''); $dob=$_POST['dob']??''; $date=DateTime::createFromFormat('!Y-m-d',$dob);
                if (!$address || strlen($address)>255 || !$date || $date->format('Y-m-d')!==$dob || $dob>date('Y-m-d')) throw new RuntimeException('Revisa dirección y nacimiento.');
                query('UPDATE patient SET pname=?,ptel=?,pnic=?,paddress=?,pdob=? WHERE pid=?',[$name,$phone,$nic,$address,$dob,$account['pid']]);
            } else query('UPDATE doctor SET docname=?,doctel=?,docnic=? WHERE docid=?',[$name,$phone,$nic,$account['docid']]);
        }
        query('UPDATE webuser SET email=? WHERE email=?',[$email,$_SESSION['user']]);
        query("UPDATE $accountTable SET $emailColumn=?,$passwordColumn=? WHERE $idColumn=?",[$email,$hash,$account[$idColumn]]);
        $database->commit(); $_SESSION['user']=$email; session_regenerate_id(true); success('/settings.php');
    } catch (Throwable $e) { $database->rollback(); $error=friendly_error($e); }
}
page('Ajustes de cuenta'); message($error);
echo '<form method="post" class="account">'.token().'<input type="hidden" name="action" value="save">';
input_value('email','Correo',$account[$emailColumn],'email');
if ($role!=='a') {
    input_value('name','Nombre',$account[$role==='p'?'pname':'docname']);
    input_value('phone','Teléfono',$account[$role==='p'?'ptel':'doctel'],'tel',true,15);
    input_value('nic','Cédula',$account[$role==='p'?'pnic':'docnic'],'text',true,15);
    if ($role==='p') { input_value('address','Dirección',$account['paddress']); input_value('dob','Nacimiento',$account['pdob'],'date'); }
}
input_value('current_password','Contraseña actual','','password');
input_value('password','Nueva contraseña (opcional)','','password',false,72);
input_value('password_confirm','Repetir nueva contraseña','','password',false,72);
echo '<button>Guardar cambios</button></form>';
if ($role!=='a') {
    echo '<section><details><summary>Eliminar mi cuenta</summary><p>Esta acción es permanente.'.($role==='p'?' También se eliminarán tus reservas.':' Debes tener todos tus horarios eliminados primero.').'</p><form method="post">'.token().'<input type="hidden" name="action" value="delete">';
    input_value('current_password','Contraseña actual','','password'); input_value('confirm','Escribe ELIMINAR');
    echo '<button class="danger">Eliminar mi cuenta</button></form></details></section>';
}
endpage();
