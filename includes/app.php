<?php
date_default_timezone_set('America/Managua');
foreach ([$_GET, $_POST] as $fields) {
    foreach ($fields as $value) {
        if (!is_string($value)) {
            http_response_code(400);
            exit('Formato de formulario no válido.');
        }
    }
}
session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax','use_strict_mode'=>true]);
require_once __DIR__.'/connection.php';
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function go($url) { header('Location: '.$url); exit; }
function query($sql, $values=[]) {
    global $database;
    $stmt=$database->prepare($sql);
    if ($values) $stmt->bind_param(str_repeat('s',count($values)), ...$values);
    $stmt->execute(); return $stmt;
}
function token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">';
}
function check_csrf() {
    if (empty($_SESSION['csrf']) || !is_string($_POST['csrf']??null) || !hash_equals($_SESSION['csrf'],$_POST['csrf'])) { http_response_code(403); exit('Formulario vencido. Recarga la página.'); }
}
function field($name,$label,$type='text',$extra='') {
    $value=$type==='password' ? '' : h($_POST[$name]??'');
    echo '<label>'.h($label).'<input name="'.$name.'" type="'.$type.'" value="'.$value.'" required '.$extra.'></label>';
}
require_once dirname(__DIR__).'/views/icons.php';
function page($title) {
    global $account,$homeSubtitle;
    $signed=!empty($_SESSION['user']);
    $role=$_SESSION['usertype']??'';
    $roleName=['a'=>'Administrador','d'=>'Médico','p'=>'Paciente'][$role]??'';
    $name=$account['pname']??$account['docname']??$roleName;
    $current=basename($_SERVER['SCRIPT_NAME']);
    $current=['index.php'=>'dashboard.php','appointment.php'=>'appointments.php','patient.php'=>'patients.php'][$current]??$current;
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).' | CitaFácil</title><link rel="stylesheet" href="/css/app.css?v=4"></head><body class="'.($signed?'signed-in':'signed-out').'">';
    if ($signed) {
        echo '<a class="skip-link" href="#main">Saltar al contenido</a><aside class="sidebar" aria-label="Navegación principal"><a class="brand" href="/dashboard.php"><span class="brand-mark" aria-hidden="true">♡</span><span>CitaFácil<small>Clínicas</small></span></a><div class="profile"><span class="avatar">'.h(mb_substr($name,0,1)).'</span><div><strong>'.h($name).'</strong><small>'.h($roleName).'</small></div></div><nav class="menu">';
        $links=['/dashboard.php'=>['home','Inicio'],'/doctors.php'=>['users','Médicos'],'/schedule.php'=>['calendar',$role==='p'?'Citas disponibles':'Horarios'],'/appointments.php'=>['ticket',$role==='p'?'Mis citas':'Agenda de citas']];
        if (in_array($role,['a','d'],true)) $links['/patients.php']=['users',$role==='d'?'Mis pacientes':'Pacientes'];
        $links['/settings.php']=['settings','Configuración'];
        foreach ($links as $url=>[$symbol,$label]) {
            $active=basename($url)===$current;
            echo '<a class="menu-link'.($active?' active':'').'" '.($active?'aria-current="page"':'').' href="'.$url.'">'.icon($symbol).'<span>'.$label.'</span></a>';
        }
        echo '</nav><form class="logout-form" action="/logout.php" method="post">'.token().'<button>'.icon('logout').'Cerrar sesión</button></form></aside>';
    }
    echo '<main id="main" class="'.($signed?'workspace':'auth-card').'"><header class="page-header"><div>';
    if (!$signed) echo '<a class="auth-brand" href="/index.html">CitaFácil · Clínicas</a>';
    echo '<h1>'.h($title).'</h1>';
    if ($signed && isset($homeSubtitle)) echo '<p class="page-subtitle">'.h($homeSubtitle).'</p>';
    if (!$signed) echo '<p>'.(basename($_SERVER['SCRIPT_NAME'])==='login.php'?'Ingresa tus datos para continuar.':'Completa tus datos para reservar tu próxima cita.').'</p>';
    echo '</div>';
    if ($signed) echo '<div class="header-account"><div class="today">'.icon('calendar').'<time datetime="'.date('Y-m-d').'">'.date('d/m/Y').'</time></div><a class="avatar avatar-small" href="/settings.php" aria-label="Abrir mi perfil">'.h(mb_substr($name,0,1)).'</a></div>';
    echo '</header>';
}
function endpage() { echo '</main></body></html>'; }
