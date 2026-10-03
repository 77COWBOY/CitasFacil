<?php
require_once __DIR__.'/app.php';
$role=$_SESSION['usertype']??'';
if (empty($_SESSION['user']) || !in_array($role,['a','d','p'],true)) go('/login.php');
if (isset($requiredRole) && $requiredRole!==$role) { http_response_code(403); exit('Acceso no autorizado.'); }
$columns=['a'=>['admin','aemail','aemail','apassword'],'d'=>['doctor','docemail','docid','docpassword'],'p'=>['patient','pemail','pid','ppassword']];
[$accountTable,$emailColumn,$idColumn,$passwordColumn]=$columns[$role];
$account=query("SELECT * FROM $accountTable WHERE $emailColumn=?",[$_SESSION['user']])->get_result()->fetch_assoc();
if (!$account) { $_SESSION=[]; go('/login.php'); }
function message($error='') {
    if ($error) echo '<p class="error">'.h($error).'</p>';
    if (isset($_SESSION['flash'])) { echo '<p class="notice">'.h($_SESSION['flash']).'</p>'; unset($_SESSION['flash']); }
}
function success($url) { $_SESSION['flash']='Cambios guardados correctamente.'; go($url); }
function input_value($name,$label,$value='',$type='text',$required=true,$max=255) {
    echo '<label>'.h($label).'<input name="'.h($name).'" type="'.h($type).'" value="'.h($type==='password'?'':$value).'" maxlength="'.$max.'" '.($required?'required':'').'></label>';
}
function specialty_select($selected=0) {
    echo '<label>Especialidad<select name="specialties" required>';
    foreach(query('SELECT * FROM specialties ORDER BY sname')->get_result() as $s) echo '<option value="'.h($s['id']).'" '.((int)$selected===(int)$s['id']?'selected':'').'>'.h($s['sname']).'</option>';
    echo '</select></label>';
}
function validate_contact($name,$email,$phone,$nic) {
    if (!$name || strlen($name)>255 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>255 || !preg_match('/^[0-9+() -]{8,15}$/',$phone) || !$nic || strlen($nic)>15) throw new RuntimeException('Revisa nombre, correo, teléfono (8 a 15 caracteres) y cédula (máximo 15).');
}
function validate_password($password) {
    if (strlen($password)<8 || strlen($password)>72) throw new RuntimeException('La contraseña debe tener entre 8 y 72 caracteres.');
}
function friendly_error($e) {
    error_log($e->getMessage());
    return $e instanceof mysqli_sql_exception ? ($e->getCode()===1062?'Ese correo ya está registrado.':'No se pudieron guardar los cambios.') : $e->getMessage();
}
