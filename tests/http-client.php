<?php
if (PHP_SAPI!=='cli') exit;
require_once dirname(__DIR__).'/includes/connection.php';
function request($path,$data=null) {
    global $cookie;
    $ch=curl_init((getenv('EDOC_TEST_URL') ?: 'http://127.0.0.1:8080').$path);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>15]);
    if ($data!==null) curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($data)]);
    $body=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_RESPONSE_CODE); curl_close($ch);
    return [$code,$body];
}
function csrf($body) { preg_match('/name="csrf" value="([^"]+)"/',$body,$m); return $m[1]??''; }
function check($ok,$label) { if (!$ok) throw new RuntimeException('FAIL: '.$label); echo "PASS: $label\n"; }
function login($email) {
    [, $body]=request('/login.php');
    [$code]=request('/login.php',['csrf'=>csrf($body),'useremail'=>$email,'userpassword'=>'123']);
    check($code===302,'Inicio de sesión '.$email);
}
function logout() { [, $body]=request('/dashboard.php'); request('/logout.php',['csrf'=>csrf($body)]); }
