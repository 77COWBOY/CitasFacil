<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $c=require dirname(__DIR__).'/config/database.php';
    $db=mysqli_init();
    $db->options(MYSQLI_OPT_CONNECT_TIMEOUT,2);
    $db->real_connect($c['host'],$c['user'],$c['password'],'',$c['port']);
    $db->set_charset('utf8mb4');
    $row=$db->query('SELECT @@port AS port, @@datadir AS datadir, @@innodb_force_recovery AS recovery, @@read_only AS read_only')->fetch_assoc();
    if (PHP_OS_FAMILY === 'Windows' && !mb_check_encoding($row['datadir'], 'UTF-8')) $row['datadir'] = mb_convert_encoding($row['datadir'], 'UTF-8', 'Windows-1252');
    echo json_encode($row,JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (Throwable $e) { exit(1); }
