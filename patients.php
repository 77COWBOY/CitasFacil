<?php
require __DIR__.'/includes/auth.php';
if (!in_array($role,['a','d'],true)) { http_response_code(403); exit('Acceso no autorizado.'); }
page('Pacientes');
$q=trim($_GET['q']??'');
echo '<form method="get"><label>Buscar por nombre o correo<input name="q" value="'.h($q).'"></label><button>Buscar</button></form><section><table><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Nacimiento</th><th>Dirección</th></tr>';
$sql='SELECT p.* FROM patient p WHERE (p.pname LIKE ? OR p.pemail LIKE ?)';
$args=['%'.$q.'%','%'.$q.'%'];
if ($role==='d') { $sql.=' AND EXISTS(SELECT 1 FROM appointment a JOIN schedule s ON s.scheduleid=a.scheduleid WHERE a.pid=p.pid AND s.docid=?)'; $args[]=$account['docid']; }
$rows=query($sql.' ORDER BY p.pname',$args)->get_result();
if (!$rows->num_rows) echo '<tr><td colspan="5">No se encontraron pacientes.</td></tr>';
foreach($rows as $p) echo '<tr><td>'.h($p['pname']).'</td><td>'.h($p['pemail']).'</td><td>'.h($p['ptel']).'</td><td>'.h($p['pdob']).'</td><td>'.h($p['paddress']).'</td></tr>';
echo '</table></section>'; endpage();
