<?php
require __DIR__.'/includes/app.php';
if (!empty($_SESSION['user'])) go('/dashboard.php');
if (empty($_SESSION['personal'])) go('/signup.php');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $email=strtolower(trim($_POST['newemail']??''));
    $phone=trim($_POST['tele']??'');
    $password=$_POST['newpassword']??'';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>255) $error='Ingresa un correo válido.';
    elseif (!preg_match('/^[0-9]{8}$/',$phone)) $error='El teléfono debe tener 8 dígitos.';
    elseif (strlen($password)<8 || strlen($password)>72) $error='La contraseña debe tener entre 8 y 72 caracteres.';
    elseif ($password!==($_POST['cpassword']??'')) $error='Las contraseñas no coinciden.';
    if (!$error) {
        $p=$_SESSION['personal']; $database->begin_transaction();
        try {
            query('INSERT INTO webuser(email,usertype) VALUES (?,?)',[$email,'p']);
            query('INSERT INTO patient(pemail,pname,ppassword,paddress,pnic,pdob,ptel) VALUES (?,?,?,?,?,?,?)',[$email,$p['fname'].' '.$p['lname'],password_hash($password,PASSWORD_DEFAULT),$p['address'],$p['nic'],$p['dob'],$phone]);
            $database->commit(); session_regenerate_id(true); unset($_SESSION['personal']);
            $_SESSION['user']=$email; $_SESSION['usertype']='p'; go('/patient/index.php');
        } catch (mysqli_sql_exception $e) {
            $database->rollback();
            $error=$e->getCode()===1062 ? 'Ya existe una cuenta con ese correo.' : 'No se pudo crear la cuenta.';
            error_log($e->getMessage());
        }
    }
}
page('Crear cuenta · Acceso');
if ($error) echo '<p class="error">'.h($error).'</p>';
echo '<form class="account" method="post">'.token();
field('newemail','Correo electrónico','email','maxlength="255"');
field('tele','Teléfono','tel','pattern="[0-9]{8}" maxlength="8"');
field('newpassword','Contraseña','password','minlength="8" maxlength="72"');
field('cpassword','Confirmar contraseña','password','minlength="8" maxlength="72"');
echo '<button>Crear cuenta</button><p><a href="signup.php">Volver a los datos personales</a></p></form>';
endpage();
