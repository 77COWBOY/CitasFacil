<?php
if (PHP_SAPI!=='cli') exit;
require __DIR__.'/adaptation-test.php';
$cookie=tempnam(sys_get_temp_dir(),'edoc-dash');
try {
    foreach(['p'=>['patient@edoc.com','patient','¿Necesitas una cita?'],'d'=>['doctor@edoc.com','doctor','Todo listo para tu próxima consulta.'],'a'=>['admin@edoc.com','admin','Una visión clara de tu clínica.']] as $role=>[$email,$path,$heading]) {
        login($email);
        [$code,$html]=request('/'.$path.'/index.php');
        check($code===200 && str_contains($html,$heading),'Dashboard específico: '.$path);
        check(substr_count($html,'class="stat-card ')===4,'Cuatro métricas: '.$path);
        check(str_contains($html,'href="/appointments.php"') && str_contains($html,'href="/schedule.php"'),'Navegación separada: '.$path);
        check(!str_contains($html,'Publicar horario de consulta'),'Inicio sin formularios de gestión: '.$path);
        foreach(['patient','doctor','admin'] as $other) {
            if ($other!==$path) check(request('/'.$other.'/index.php')[0]===403,'Restricción '.$path.' → '.$other);
        }
        [$code,$schedule]=request('/schedule.php');
        check($code===200 && (str_contains($schedule,'Publicar horario de consulta')===($role!=='p')),'Publicación según rol: '.$path);
        check(request('/appointments.php?history=1')[0]===200,'Historial: '.$path);
        check(request('/appointments.php?date=2026-01-01')[0]===200,'Filtro de fecha: '.$path);
        preg_match_all('/(?:href|src)="(\/[^"#?]*)(?:[?#][^"]*)?"/',$html,$matches);
        foreach(array_unique($matches[1]) as $url) {
            if ($url==='/logout.php') continue;
            check(request($url)[0]===200,'Enlace/recurso '.$path.': '.$url);
        }
        logout();
    }
    check(request('/views/dashboards/admin.php')[0]===404,'Plantillas privadas');
    check(request('/.local/previews/admin.html')[0]===404,'Previsualización privada');
    check(request('/conection.php')[0]===404,'Conexión duplicada retirada');
} finally { unlink($cookie); }
