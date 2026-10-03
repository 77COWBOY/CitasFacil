<?php
if (PHP_SAPI!=='cli') exit;
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$c=require dirname(__DIR__).'/config/database.php';
$source=$argv[1]??'';
if (!is_file($source) || strtolower(pathinfo($source,PATHINFO_EXTENSION))!=='sql') throw new RuntimeException('Indica un respaldo SQL válido.');
if (!preg_match('/^[a-zA-Z0-9_]+$/',$c['name'])) throw new RuntimeException('Nombre inválido.');
$db=new mysqli($c['host'],$c['user'],$c['password'],'',$c['port']);
$name=$c['name'];
$db->query("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->select_db($name);
if ($db->query('SHOW TABLES')->num_rows) throw new RuntimeException('La base debe estar vacía. No se reemplazarán datos existentes.');
$db->multi_query(file_get_contents($source));
do { if ($r=$db->store_result()) $r->free(); } while ($db->more_results() && $db->next_result());
echo "Respaldo restaurado.\n";
