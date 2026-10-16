<?php
require __DIR__.'/includes/auth.php';
require __DIR__.'/includes/appointment-actions.php';
$error=handle_appointment_action($role,$account);
$history=!empty($_GET['history']);
$filterDate=$_GET['date']??'';
if ($filterDate!=='' && !valid_local_datetime($filterDate.' 00:00')) { $error='Selecciona una fecha válida.'; $filterDate=''; }
$rows=appointment_list($role,$account,$history,$filterDate);
$homeSubtitle=$role==='p'?'La información que necesitas para tu próxima visita.':'Consulta y gestiona las reservas de tu agenda.';
page($role==='p'?'Mis citas':'Citas de la clínica'); message($error);
echo '<div class="appointments-toolbar"><nav class="view-tabs" aria-label="Estado de citas"><a href="/appointments.php" '.(!$history?'aria-current="page"':'').'>Próximas</a><a href="/appointments.php?history=1" '.($history?'aria-current="page"':'').'>Anteriores</a></nav><a class="button secondary" href="/schedule.php">'.icon('calendar').($role==='p'?'Hacer cita':'Abrir agenda').'</a></div>';
if ($role!=='p') echo '<form method="get" class="compact-filter"><input type="hidden" name="history" value="'.(int)$history.'"><label>Fecha<input type="date" name="date" value="'.h($filterDate).'"></label><button class="secondary">Ver citas</button></form>';
if (!$rows) echo '<section class="surface empty-state">'.icon('calendar').'<h2>'.($history?'Aún no hay citas anteriores':'Tu agenda está libre').'</h2><p>'.($role==='p'?'Elige un día y una hora para tu próxima consulta.':'Las reservas aparecerán aquí cuando se confirmen.').'</p><a class="button" href="/schedule.php">Abrir calendario</a></section>';
echo '<div class="appointment-cards">';
foreach ($rows as $a) {
    $dt=new DateTimeImmutable($a['starts_at']??$a['scheduledate']);
    echo '<article class="appointment-card" id="appointment-'.(int)$a['appoid'].'"><div class="appointment-date"><strong>'.$dt->format('d').'</strong><span>'.h(spanish_month((int)$dt->format('n'),true)).'</span></div><div class="appointment-card-copy"><span class="appointment-status '.($history?'past':'').'">'.($history?'Finalizada':(empty($a['starts_at'])?'Hora por confirmar':'Confirmada')).'</span><h2>'.h($role==='p'?$a['docname']:$a['pname']).'</h2><p>'.h($role==='p'?$a['title']:$a['docname']).'</p><strong class="appointment-hour">'.icon('clock').h(appointment_time($a)).'</strong><small>'.date('d/m/Y',strtotime($a['scheduledate'])).'</small></div>';
    if (!$history) {
        echo '<div class="appointment-card-actions">';
        if ($role==='p') echo '<a class="button secondary" href="/schedule.php?reschedule='.(int)$a['appoid'].'&doctor='.(int)$a['docid'].'">Cambiar hora</a>';
        echo '<form method="post" data-confirm="¿Cancelar esta cita? La hora quedará disponible." data-confirm-label="Cancelar cita">'.token().'<input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="'.(int)$a['appoid'].'"><button class="text-danger">Cancelar cita</button></form></div>';
    }
    echo '</article>';
}
echo '</div>'; endpage();
