<?php
// eDoc utiliza una instancia local independiente de la dañada en XAMPP.
return [
    'host' => getenv('EDOC_DB_HOST') ?: '127.0.0.1',
    'user' => getenv('EDOC_DB_USER') ?: 'root',
    'password' => getenv('EDOC_DB_PASSWORD') ?: '',
    'name' => getenv('EDOC_DB_NAME') ?: 'edoc',
    'port' => (int)(getenv('EDOC_DB_PORT') ?: 3308),
];
