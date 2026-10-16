<?php
if (PHP_SAPI!=='cli') exit;
require __DIR__.'/http-client.php';
$cookie=tempnam(sys_get_temp_dir(),'edoc');
$email='test-'.bin2hex(random_bytes(6)).'@example.com';
$pid=null; $sid=null;
try {
    check(request('/')[0]===200,'Portada');
    check(request('/dashboard.php')[0]===302,'Panel privado');
    check(request('/login.php',['csrf'=>''])[0]===403,'Token vacío rechazado');
    check(request('/login.php?useremail[]=x')[0]===400,'Parámetros de tipo incorrecto rechazados');
    foreach (['/includes/app.php','/config/database.php','/database/schema.sql','/tests/smoke-test.php'] as $private) check(request($private)[0]===404,'Archivo interno protegido: '.$private);
    check(request('/SQL_Database_edoc.sql')[0]===404,'SQL no público');
    check(request('/.local/login.php')[0]===404,'Respaldo no público');
    check(request('/create-account.php')[0]===302,'Registro requiere primer paso');
    check(request('/login.php',['useremail'=>'x'])[0]===403,'Protección CSRF');
    [, $b]=request('/login.php');
    [$code,$b]=request('/login.php',['csrf'=>csrf($b),'useremail'=>"' OR 1=1 --",'userpassword'=>'123']);
    check($code===200 && str_contains($b,'incorrectos'),'Rechazo de inyección SQL');
    [, $b]=request('/signup.php');
    [$code]=request('/signup.php',['csrf'=>csrf($b),'fname'=>"Prueba O'Connor",'lname'=>'Álvarez','address'=>"Dirección de prueba",'nic'=>'TEST123','dob'=>'2000-01-01']);
    check($code===302,'Datos personales con acentos y apóstrofe');
    [, $b]=request('/create-account.php');
    [$code]=request('/create-account.php',['csrf'=>csrf($b),'newemail'=>$email,'tele'=>'81234567','newpassword'=>'Prueba123!','cpassword'=>'Prueba123!']);
    check($code===302,'Registro completo');
    check(request('/signup.php')[0]===302 && request('/create-account.php')[0]===302,'Sesión activa no vuelve a registrarse');
    [, $b]=request('/appointments.php');
    [$code,$b]=request('/appointments.php',['csrf'=>csrf($b),'action'=>'cancel','id'=>'0']);
    check($code===200 && str_contains($b,'La cita no existe'),'Cancelar cita inexistente muestra error');
    $stmt=$database->prepare('SELECT * FROM patient WHERE pemail=?'); $stmt->bind_param('s',$email); $stmt->execute(); $p=$stmt->get_result()->fetch_assoc(); $pid=$p['pid'];
    check(password_verify('Prueba123!',$p['ppassword']) && $p['ppassword']!=='Prueba123!','Contraseña protegida');
    check(request('/admin/index.php')[0]===403,'Separación de roles');
    logout();
    login('doctor@edoc.com');
    [, $b]=request('/dashboard.php'); $title='QA-'.bin2hex(random_bytes(6)); $date=date('Y-m-d',strtotime('+2 days'));
    [$code]=request('/schedule.php',['csrf'=>csrf($b),'action'=>'schedule','title'=>$title,'date'=>$date,'time'=>'14:00','endtime'=>'15:00','duration'=>'30']);
    check($code===302,'Publicar horario médico');
    $stmt=$database->prepare('SELECT scheduleid FROM schedule WHERE title=?'); $stmt->bind_param('s',$title); $stmt->execute(); $sid=$stmt->get_result()->fetch_assoc()['scheduleid'];
    logout();
    [, $b]=request('/login.php');
    check(request('/login.php',['csrf'=>csrf($b),'useremail'=>$email,'userpassword'=>'Prueba123!'])[0]===302,'Acceso con cuenta nueva');
    [, $b]=request('/dashboard.php'); $t=csrf($b);
    check(request('/schedule.php',['csrf'=>$t,'action'=>'book','id'=>$sid,'start'=>$date.' 14:00'])[0]===302,'Reservar cita a una hora concreta');
    [$code,$b]=request('/schedule.php',['csrf'=>$t,'action'=>'book','id'=>$sid,'start'=>$date.' 14:30']);
    check($code===200 && str_contains($b,'Ya tienes una cita activa'),'Una cita activa por paciente');
    $aid=$database->query('SELECT appoid FROM appointment WHERE pid='.(int)$pid.' AND scheduleid='.(int)$sid)->fetch_assoc()['appoid'];
    check(request('/appointments.php',['csrf'=>$t,'action'=>'cancel','id'=>$aid])[0]===302,'Cancelar cita');
    check($database->query('SELECT appoid FROM appointment WHERE appoid='.(int)$aid)->num_rows===0,'Cancelación persistida');
    logout(); login('admin@edoc.com');
    check(request('/admin/index.php')[0]===200,'Panel administrador'); logout();
    login('patient@edoc.com'); check(request('/patient/index.php')[0]===200,'Panel paciente'); logout();
} finally {
    if ($pid) { $database->query('DELETE FROM appointment WHERE pid='.(int)$pid); $database->query('DELETE FROM patient WHERE pid='.(int)$pid); }
    $stmt=$database->prepare('DELETE FROM webuser WHERE email=?'); $stmt->bind_param('s',$email); $stmt->execute();
    if ($sid) $database->query('DELETE FROM schedule WHERE scheduleid='.(int)$sid);
    unlink($cookie);
}
