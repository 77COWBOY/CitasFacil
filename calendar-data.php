<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/calendar.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    echo json_encode(calendar_data($role,$account,$_GET['from']??'',$_GET['to']??'',(int)($_GET['doctor']??0),(int)($_GET['specialty']??0),(int)($_GET['reschedule']??0)),JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $known=$e instanceof RuntimeException && !($e instanceof mysqli_sql_exception);
    error_log($e->getMessage()); http_response_code($known?400:500);
    echo json_encode(['error'=>$known?$e->getMessage():'No se pudo cargar la agenda. Inténtalo de nuevo.'],JSON_UNESCAPED_UNICODE);
}
