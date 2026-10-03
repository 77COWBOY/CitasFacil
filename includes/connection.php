<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $config = require dirname(__DIR__) . '/config/database.php';
    $database = new mysqli($config['host'], $config['user'], $config['password'], $config['name'], $config['port']);
    $database->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    if (PHP_SAPI==='cli') {
        fwrite(STDERR, "No se pudo conectar con la base de datos. Ejecuta Iniciar-eDoc.cmd.\n");
        exit(1);
    }
    http_response_code(503);
    exit('No se pudo conectar con la base de datos. Ejecuta iniciar.ps1.');
}
