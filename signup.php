<?php
require __DIR__.'/includes/app.php';
if (!empty($_SESSION['user'])) go('/dashboard.php');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf(); $personal=[];
    foreach (['fname'=>100,'lname'=>100,'address'=>255,'nic'=>15,'dob'=>10] as $key=>$max) {
        $personal[$key]=trim($_POST[$key]??'');
        if ($personal[$key]==='' || strlen($personal[$key])>$max) $error='Completa los datos respetando su longitud máxima.';
    }
    $date=DateTime::createFromFormat('!Y-m-d',$personal['dob']);
    if (!$date || $date->format('Y-m-d')!==$personal['dob'] || $personal['dob']>date('Y-m-d')) $error='La fecha de nacimiento no es válida.';
    if (!$error) { $_SESSION['personal']=$personal; go('/create-account.php'); }
}
page('Crear cuenta · Datos personales');
if ($error) echo '<p class="error">'.h($error).'</p>';
echo '<form class="account" method="post">'.token();
field('fname','Nombre','text','maxlength="100"');
field('lname','Apellido','text','maxlength="100"');
field('address','Dirección','text','maxlength="255"');
field('nic','Cédula / NIC','text','maxlength="15"');
field('dob','Fecha de nacimiento','date','max="'.date('Y-m-d').'"');
echo '<button>Siguiente</button><p><a href="login.php">Ya tengo una cuenta</a></p></form>';
endpage();
