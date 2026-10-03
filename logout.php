<?php
require __DIR__.'/includes/app.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
check_csrf(); $_SESSION=[]; session_destroy();
setcookie(session_name(),'',time()-3600,'/');
go('/login.php');
