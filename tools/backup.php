<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
date_default_timezone_set('America/Managua');
require dirname(__DIR__).'/includes/connection.php';
$dir=dirname(__DIR__).'/.local/backups';
if (!is_dir($dir) && !mkdir($dir,0700,true)) throw new RuntimeException('No se pudo crear el directorio de respaldos.');
$file=$dir.'/edoc-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sql';
$temp=$file.'.tmp';
$stream=fopen($temp,'xb');
if (!$stream) throw new RuntimeException('No se pudo escribir el respaldo.');
try {
    $database->begin_transaction(MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT | MYSQLI_TRANS_START_READ_ONLY);
    fwrite($stream,"-- Respaldo de eDoc. Restaurar en una base vacía.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
    foreach (['admin','appointment','doctor','patient','schedule','specialties','webuser'] as $table) {
        $schema=$database->query("SHOW CREATE TABLE `$table`")->fetch_row()[1];
        fwrite($stream,$schema.";\n");
        foreach ($database->query("SELECT * FROM `$table`") as $row) {
            $values=array_map(fn($v)=>$v===null?'NULL':"'".$database->real_escape_string($v)."'",$row);
            $columns=implode(',',array_map(fn($v)=>"`$v`",array_keys($row)));
            fwrite($stream,"INSERT INTO `$table` ($columns) VALUES (".implode(',',$values).");\n");
        }
    }
    fwrite($stream,"SET FOREIGN_KEY_CHECKS=1;\n");
    $database->commit();
    if (!fflush($stream)) throw new RuntimeException('No se pudo completar el archivo.');
    fclose($stream); $stream=null;
    if (!rename($temp,$file)) throw new RuntimeException('No se pudo finalizar el respaldo.');
    echo $file.PHP_EOL;
} catch (Throwable $e) {
    $database->rollback();
    if (is_resource($stream)) fclose($stream);
    if (is_file($temp)) unlink($temp);
    throw $e;
}
