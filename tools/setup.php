<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$config=require dirname(__DIR__).'/config/database.php';
$db=new mysqli($config['host'],$config['user'],$config['password'],'',$config['port']);
$name=$config['name'];
if (!preg_match('/^[a-zA-Z0-9_]+$/',$name)) throw new RuntimeException('Nombre de base inválido.');
$db->query("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->select_db($name); $db->set_charset('utf8mb4');
$tables=$db->query('SHOW TABLES')->num_rows;
if ($tables===0) {
    $sql=file_get_contents(dirname(__DIR__).'/database/schema.sql');
    $sql=str_replace(['ENGINE=MyISAM','DEFAULT CHARSET=latin1'],['ENGINE=InnoDB','DEFAULT CHARSET=utf8mb4'],$sql);
    $db->multi_query($sql);
    do { if ($r=$db->store_result()) $r->free(); } while ($db->more_results() && $db->next_result());
    echo "Base de datos importada.\n";
}
$db->autocommit(true);
// Migración no destructiva: conserva registros y protege las contraseñas existentes.
foreach (['admin','doctor','patient','webuser','schedule','appointment','specialties'] as $table) {
    $stmt=$db->prepare('SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
    $stmt->bind_param('ss',$name,$table); $stmt->execute();
    $info=$stmt->get_result()->fetch_assoc();
    if (!$info) throw new RuntimeException("Falta la tabla $table. No se modificaron las tablas existentes para reemplazarla.");
    if ($info['ENGINE']!=='InnoDB') $db->query("ALTER TABLE `$table` ENGINE=InnoDB");
    if ($info['TABLE_COLLATION']!=='utf8mb4_unicode_ci') $db->query("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}
foreach ([['admin','aemail','apassword'],['doctor','docemail','docpassword'],['patient','pemail','ppassword']] as [$table,$id,$pass]) {
    foreach ($db->query("SELECT `$id`,`$pass` FROM `$table`") as $row) {
        if (!password_get_info($row[$pass]??'')['algo']) {
            $hash=password_hash($row[$pass]??'',PASSWORD_DEFAULT);
            $stmt=$db->prepare("UPDATE `$table` SET `$pass`=? WHERE `$id`=?");
            $stmt->bind_param('ss',$hash,$row[$id]); $stmt->execute();
        }
    }
}
echo "eDoc listo.\n";
