<?php
if (PHP_SAPI!=='cli' || !str_starts_with(getenv('EDOC_DB_NAME')?:'','edoc_calendar_check_')) exit(1);
require dirname(__DIR__).'/includes/connection.php';
$email='calendar-dom-patient@example.invalid';
$stmt=$database->prepare('SELECT pid FROM patient WHERE pemail=?'); $stmt->bind_param('s',$email); $stmt->execute(); $patient=$stmt->get_result()->fetch_assoc();
if (($argv[1]??'')==='--clean') {
    if ($patient) { $database->query('DELETE FROM appointment WHERE pid='.(int)$patient['pid']); $database->query('DELETE FROM patient WHERE pid='.(int)$patient['pid']); }
    $stmt=$database->prepare('DELETE FROM webuser WHERE email=?'); $stmt->bind_param('s',$email); $stmt->execute();
    exit;
}
if (!$patient) {
    $stmt=$database->prepare("INSERT INTO webuser(email,usertype) VALUES (?,'p')"); $stmt->bind_param('s',$email); $stmt->execute();
    $hash=password_hash('PruebaDOM123!',PASSWORD_DEFAULT);
    $stmt=$database->prepare("INSERT INTO patient(pemail,pname,ppassword) VALUES (?,'Paciente de pruebas DOM',?)"); $stmt->bind_param('ss',$email,$hash); $stmt->execute();
}
