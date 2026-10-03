<?php
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) ?: '/');
$pages=['/','/index.html','/login.php','/signup.php','/create-account.php','/dashboard.php','/schedule.php','/appointments.php','/logout.php','/doctors.php','/patients.php','/settings.php','/admin/index.php','/doctor/index.php','/patient/index.php'];
if (in_array($path,$pages,true)) return false;
// Las rutas por rol comparten controladores, sin duplicar archivos.
if (preg_match('~^/(admin|doctor|patient)/(index|doctors|patient|schedule|appointment|settings)\.php$~',$path,$match)) {
    $requiredRole=['admin'=>'a','doctor'=>'d','patient'=>'p'][$match[1]];
    $route=['index'=>'dashboard','patient'=>'patients','appointment'=>'appointments'][$match[2]]??$match[2];
    $_SERVER['SCRIPT_NAME']='/'.$route.'.php';
    require __DIR__.'/'.$route.'.php';
    return true;
}
if ($path==='/css/fonts/inter/Inter-Regular.woff') return false;
if (preg_match('~^/(css|img)/[a-zA-Z0-9_.-]+\.(css|jpg|jpeg|png|svg|webp)$~',$path) && is_file(__DIR__.$path)) return false;
http_response_code(404); echo 'No encontrado';
