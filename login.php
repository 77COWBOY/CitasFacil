<?php
require __DIR__.'/includes/app.php';
if (!empty($_SESSION['user'])) go('/dashboard.php');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $email=strtolower(trim($_POST['useremail']??''));
    $user=query('SELECT usertype FROM webuser WHERE email=?',[$email])->get_result()->fetch_assoc();
    $roles=['p'=>['patient','pemail','ppassword'],'d'=>['doctor','docemail','docpassword'],'a'=>['admin','aemail','apassword']];
    if ($user && isset($roles[$user['usertype']])) {
        [$table,$column,$pass]=$roles[$user['usertype']];
        $row=query("SELECT * FROM $table WHERE $column=?",[$email])->get_result()->fetch_assoc();
        if ($row && password_verify($_POST['userpassword']??'', $row[$pass])) {
            session_regenerate_id(true);
            $_SESSION['user']=$email; $_SESSION['usertype']=$user['usertype'];
            go('/dashboard.php');
        }
    }
    $error='Correo o contraseña incorrectos.';
}
page('Iniciar sesión');
if ($error) echo '<p class="error">'.h($error).'</p>';
echo '<form class="account" method="post">'.token();
field('useremail','Correo electrónico','email','maxlength="255"');
field('userpassword','Contraseña','password');
echo '<button>Iniciar sesión</button><p>¿No tienes cuenta? <a href="signup.php">Regístrate</a></p></form>';
endpage();
