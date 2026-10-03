<?php
if (PHP_SAPI!=='cli') exit;
require dirname(__DIR__).'/includes/connection.php';
$tables=['admin'=>'aemail','appointment'=>'appoid','doctor'=>'docid','patient'=>'pid','schedule'=>'scheduleid','specialties'=>'id','webuser'=>'email'];
$original=$config['name'];
function digest($db,$tables) {
    $result=[];
    foreach ($tables as $table=>$key) {
        $rows=$db->query("SELECT * FROM `$table` ORDER BY `$key`")->fetch_all(MYSQLI_ASSOC);
        $result[$table]=['count'=>count($rows),'sha256'=>hash('sha256',json_encode($rows))];
    }
    return $result;
}
$before=digest($database,$tables);
$status=$database->query('SELECT @@innodb_force_recovery AS recovery,@@read_only AS ro')->fetch_assoc();
if ($status['recovery']!=0 || $status['ro']!=0) throw new RuntimeException('Servidor no operativo.');
foreach ($tables as $table=>$key) {
    $check=$database->query("CHECK TABLE `$table`")->fetch_all(MYSQLI_ASSOC);
    if (end($check)['Msg_text']!=='OK') throw new RuntimeException('Integridad fallida: '.$table);
}
$probe='rollback-'.bin2hex(random_bytes(8)).'@example.invalid';
$database->begin_transaction();
$stmt=$database->prepare("INSERT INTO webuser(email,usertype) VALUES (?,'p')");
$stmt->bind_param('s',$probe); $stmt->execute(); $database->rollback();
$stmt=$database->prepare('SELECT email FROM webuser WHERE email=?');
$stmt->bind_param('s',$probe); $stmt->execute();
if ($stmt->get_result()->num_rows) throw new RuntimeException('ROLLBACK no funciona.');
echo "PASS: integridad y rollback\n";
$files=glob(dirname(__DIR__).'/.local/backups/*.sql');
if (!$files) throw new RuntimeException('No hay un respaldo para comprobar.');
usort($files,fn($a,$b)=>filemtime($b)<=>filemtime($a));
$temp='edoc_restorecheck_'.bin2hex(random_bytes(6));
$database->query("CREATE DATABASE `$temp` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
try {
    $database->select_db($temp);
    $database->multi_query(file_get_contents($files[0]));
    do { if ($r=$database->store_result()) $r->free(); } while ($database->more_results() && $database->next_result());
    $restored=digest($database,$tables);
    if ($before!==$restored) throw new RuntimeException('El respaldo no coincide con los datos actuales.');
    echo "PASS: respaldo restaurado; conteos y hashes coinciden en 7 tablas\n";
} finally {
    $database->select_db($original);
    $database->query("DROP DATABASE `$temp`");
}
file_put_contents(dirname(__DIR__).'/.local/database-verified.json',json_encode($before,JSON_PRETTY_PRINT));
