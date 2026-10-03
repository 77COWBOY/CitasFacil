<?php
// Reutiliza el cliente HTTP y ejecuta primero las comprobaciones originales.
require __DIR__.'/smoke-test.php';
$cookie=tempnam(sys_get_temp_dir(),'edoc-extra');
$suffix=bin2hex(random_bytes(5)); $docemail='qa-doc-'.$suffix.'@example.com'; $did=null;
try {
    login('admin@edoc.com');
    [, $b]=request('/doctors.php');
    $data=['csrf'=>csrf($b),'action'=>'save','id'=>0,'name'=>"QA Médico O'Connor",'email'=>$docemail,'phone'=>'81234567','nic'=>'QA123','specialties'=>1,'password'=>'Prueba123!'];
    check(request('/doctors.php',$data)[0]===302,'Administrador crea médico');
    $stmt=$database->prepare('SELECT * FROM doctor WHERE docemail=?'); $stmt->bind_param('s',$docemail); $stmt->execute(); $doc=$stmt->get_result()->fetch_assoc(); $did=$doc['docid'];
    check(password_verify('Prueba123!',$doc['docpassword']),'Clave de médico protegida');
    $data['id']=$did; $data['name']='QA Médico Editado'; $data['password']='';
    check(request('/doctors.php',$data)[0]===302,'Administrador edita médico');
    [, $b]=request('/doctors.php?q=QA+M%C3%A9dico+Editado');
    check(str_contains($b,'QA Médico Editado'),'Búsqueda de médico');
    check(request('/admin/patient.php')[0]===200,'Ruta de pacientes del ZIP adaptada');
    logout();
    [, $b]=request('/login.php');
    check(request('/login.php',['csrf'=>csrf($b),'useremail'=>$docemail,'userpassword'=>'Prueba123!'])[0]===302,'Acceso del médico creado');
    [, $b]=request('/patients.php');
    check(!str_contains($b,'Test Patient'),'Médico nuevo no ve pacientes ajenos');
    check(request('/admin/doctors.php')[0]===403,'Ruta administrativa protegida');
    [, $b]=request('/settings.php');
    $settings=['csrf'=>csrf($b),'action'=>'save','email'=>$docemail,'name'=>'QA Perfil actualizado','phone'=>'87654321','nic'=>'QA123','current_password'=>'incorrecta'];
    [$code,$b]=request('/settings.php',$settings);
    check($code===200 && str_contains($b,'no es correcta'),'Edición requiere contraseña actual');
    $settings['current_password']='Prueba123!'; $settings['password']='OtraPrueba123!'; $settings['password_confirm']='OtraPrueba123!';
    check(request('/settings.php',$settings)[0]===302,'Actualización de perfil y contraseña');
    logout();
    [, $b]=request('/login.php');
    check(request('/login.php',['csrf'=>csrf($b),'useremail'=>$docemail,'userpassword'=>'OtraPrueba123!'])[0]===302,'Acceso con contraseña actualizada');
    [, $b]=request('/dashboard.php');
    check(request('/doctors.php',['csrf'=>csrf($b),'action'=>'delete','id'=>$did,'confirm'=>'ELIMINAR'])[0]===403,'Médico no puede administrar médicos');
    logout(); login('admin@edoc.com');
    [, $b]=request('/doctors.php');
    check(request('/doctors.php',['csrf'=>csrf($b),'action'=>'delete','id'=>$did,'confirm'=>'ELIMINAR'])[0]===302,'Eliminar médico temporal');
    check($database->query('SELECT docid FROM doctor WHERE docid='.(int)$did)->num_rows===0,'Eliminación persistida');
    check(request('/css/fonts/inter/Inter-Regular.woff')[0]===200,'Fuente local del ZIP');
    check(request('/img/care-team.svg')[0]===200,'Ilustración del dashboard');
    logout();
} finally {
    if ($did) $database->query('DELETE FROM doctor WHERE docid='.(int)$did);
    $stmt=$database->prepare('DELETE FROM webuser WHERE email=?'); $stmt->bind_param('s',$docemail); $stmt->execute();
    unlink($cookie);
}
