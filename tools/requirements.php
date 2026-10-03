<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (PHP_VERSION_ID < 80000) { fwrite(STDERR,"Se necesita PHP 8 o superior.\n"); exit(1); }
foreach (['mysqli','mbstring','curl'] as $extension) {
    if (!extension_loaded($extension)) { fwrite(STDERR,"Falta extension: $extension\n"); exit(1); }
}
