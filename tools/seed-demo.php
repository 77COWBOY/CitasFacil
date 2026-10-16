<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
date_default_timezone_set('America/Managua');
require dirname(__DIR__).'/includes/connection.php';
require __DIR__.'/demo-data.php';
$result=seed_demo_catalog($database);
echo $result['doctors'].' médicos y '.$result['schedules']." horarios de demostración agregados.\n";
echo $result['skipped']." horarios existentes o superpuestos conservados sin duplicar.\n";
