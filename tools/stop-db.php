<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/includes/connection.php';
$row=$database->query('SELECT @@datadir AS datadir, @@port AS port')->fetch_assoc();
$expected=realpath(dirname(__DIR__).'/.local/mysql/data');
if (PHP_OS_FAMILY === 'Windows' && !mb_check_encoding($row['datadir'], 'UTF-8')) $row['datadir'] = mb_convert_encoding($row['datadir'], 'UTF-8', 'Windows-1252');
$actual=realpath($row['datadir']);
if (!$expected || !$actual || strcasecmp($expected,$actual)!==0 || (int)$row['port']!==3308) {
    throw new RuntimeException('No se detendrá un servidor ajeno a este proyecto.');
}
$database->query('SET GLOBAL innodb_fast_shutdown=0');
$database->query('SHUTDOWN');
echo "MariaDB de eDoc detenida correctamente.\n";
