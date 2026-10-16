<?php
$availableDays=[];
foreach ($data['schedules'] as $s) if (array_filter($s['slots'],fn($slot)=>$slot['available'])) $availableDays[$s['date']]=true;
$blocked=$role==='p' && $active && (!$reschedule || $active['appoid']!=$reschedule['appoid']);
$dateUrl=fn($date,$month=null)=>'/schedule.php?'.http_build_query(array_filter(['doctor'=>$doctorId,'specialty'=>$specialty,'date'=>$date,'month'=>$month,'reschedule'=>$reschedule['appoid']??null],fn($v)=>$v!==null));
$specialties=[];
foreach ($doctors as $d) $specialties[$d['specialties']]=$d['sname'];
asort($specialties);
$selectedDoctor=null;
foreach ($doctors as $d) if ((int)$d['docid']===$doctorId) $selectedDoctor=$d;
?>
<?php if ($blocked): ?><div class="active-booking"><span><?= icon('calendar') ?><strong>Ya tienes una cita</strong> · <?= h(spanish_date($active['scheduledate'])) ?> · <?= h(appointment_time($active)) ?></span><a href="/appointments.php">Ver o cambiar mi cita →</a></div><?php endif ?>
<div class="calendar-app" data-calendar>
  <aside class="calendar-sidebar" aria-label="Elegir atención">
    <div class="calendar-intro"><span class="calendar-kicker">CITAFÁCIL · CLÍNICAS</span><h2><?= $role==='p'?'Un momento para tu salud.':'Todo en tu agenda.' ?></h2><p><?= $role==='p'?'Elige tu médico, un día y la hora.':'Organiza consultas y disponibilidad.' ?></p></div>
    <form method="get" class="calendar-filters">
      <?php if ($role!=='d'): ?><label>Especialidad<select name="specialty" id="calendar-specialty"><option value="0">Todas las especialidades</option><?php foreach ($specialties as $id=>$name): ?><option value="<?= (int)$id ?>" <?= $specialty===(int)$id?'selected':'' ?>><?= h($name) ?></option><?php endforeach ?></select></label><?php endif ?>
      <label><?= $role==='d'?'Mi consultorio':'Médico' ?><select name="doctor" id="calendar-doctor" <?= $role==='d'?'disabled':'' ?>><?php if ($role==='a'): ?><option value="0" <?= !$doctorId?'selected':'' ?>>Todo el equipo</option><?php endif ?><?php foreach ($matching as $d): ?><option value="<?= (int)$d['docid'] ?>" <?= $doctorId===(int)$d['docid']?'selected':'' ?>><?= h($d['docname']) ?></option><?php endforeach ?></select></label>
      <?php if ($reschedule): ?><input type="hidden" name="reschedule" value="<?= (int)$reschedule['appoid'] ?>"><?php endif ?>
      <button class="secondary" data-nojs>Ver disponibilidad</button>
    </form>
    <div class="doctor-selection" data-doctor-card><span class="avatar"><?= h(mb_substr($selectedDoctor['docname']??'C',0,1)) ?></span><div><strong><?= h($selectedDoctor['docname']??'Equipo de la clínica') ?></strong><small><?= h($selectedDoctor['sname']??'Todas las especialidades') ?></small></div></div>
    <div class="calendar-note"><?= icon('clock') ?><p>Consultas con hora definida.<small>Hora local de Nicaragua.</small></p></div>
  </aside>
  <section class="calendar-surface" aria-label="Calendario de atención">
    <div class="calendar-toolbar"><div class="calendar-navigation"><a class="calendar-arrow" data-prev href="<?= h($dateUrl($monthDate->modify('-1 month')->format('Y-m-d'),$monthDate->modify('-1 month')->format('Y-m'))) ?>" aria-label="Mes anterior">‹</a><h2 data-month-title><?= h(ucfirst(spanish_month((int)$monthDate->format('n'))).' '.$monthDate->format('Y')) ?></h2><a class="calendar-arrow" data-next href="<?= h($dateUrl($monthDate->modify('+1 month')->format('Y-m-d'),$monthDate->modify('+1 month')->format('Y-m'))) ?>" aria-label="Mes siguiente">›</a></div><div class="calendar-toolbar-end"><a class="calendar-today" data-today href="<?= h($dateUrl(date('Y-m-d'))) ?>">Hoy</a><div class="calendar-view" data-js-only hidden><button type="button" data-view="month" aria-pressed="true">Mes</button><button type="button" data-view="week" aria-pressed="false">Semana</button></div></div></div>
    <div class="calendar-status" role="status" aria-live="polite" data-calendar-status></div>
    <div class="calendar-content"><div class="calendar-board"><div class="calendar-weekdays"><?php foreach (['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $weekday): ?><span><?= $weekday ?></span><?php endforeach ?></div>
      <div class="calendar-grid" data-calendar-grid>
      <?php for ($i=0;$i<42;$i++): $dt=$gridStart->modify('+'.$i.' days'); $key=$dt->format('Y-m-d'); ?>
        <a class="calendar-day <?= $dt->format('m')!==$monthDate->format('m')?'outside ':'' ?><?= $key===date('Y-m-d')?'today ':'' ?><?= $key===$selected?'selected ':'' ?><?= isset($availableDays[$key])?'has-availability':'' ?>" href="<?= h($dateUrl($key)) ?>" data-date="<?= $key ?>" aria-label="<?= h(spanish_date($key).(isset($availableDays[$key])?', con horas disponibles':'')) ?>" <?= $key===$selected?'aria-current="date"':'' ?>><span><?= $dt->format('j') ?></span><?php if (isset($availableDays[$key])): ?><i aria-hidden="true"></i><?php endif ?></a>
      <?php endfor ?>
      </div><div class="calendar-week" data-calendar-week hidden></div><div class="calendar-legend"><span><i class="legend-dot"></i> Horas disponibles</span><span><i class="legend-dot booked"></i> Mis citas</span></div>
    </div>
    <aside class="calendar-day-panel" aria-label="Horas del día" data-day-panel>
      <span class="calendar-kicker">TU DÍA</span><h3><?= h(spanish_date($selected)) ?></h3>
      <?php if ($role==='p' && !$blocked): $count=0; ?>
        <p class="day-panel-description">Selecciona una hora</p>
        <form method="post" class="time-booking" data-confirm="Confirma la hora elegida para tu consulta." data-confirm-label="<?= $reschedule?'Confirmar cambio':'Confirmar cita' ?>">
        <?= token() ?><input type="hidden" name="action" value="<?= $reschedule?'reschedule':'book' ?>"><input type="hidden" name="appointment_id" value="<?= (int)($reschedule['appoid']??0) ?>">
        <div class="time-options">
        <?php foreach ($data['schedules'] as $s): if ($s['date']!==$selected) continue; foreach ($s['slots'] as $slot): if (!$slot['available']) continue; $count++; ?>
          <label class="time-option"><input type="radio" name="slot" value="<?= h($s['id'].'|'.$slot['start']) ?>" data-end="<?= h($slot['end']) ?>" required><span><?= h($slot['label']) ?></span></label>
        <?php endforeach; endforeach ?>
        </div>
        <?php if (!$count): ?><p class="calendar-empty">No hay horas disponibles este día. Prueba con otro día marcado en azul.</p><?php endif ?>
        <p class="booking-selection" data-booking-selection>Tu hora aparecerá aquí.</p><button class="booking-submit" <?= !$count?'disabled':'' ?>><?= $reschedule?'Confirmar cambio':'Confirmar cita' ?></button>
        </form>
      <?php elseif ($blocked): ?><div class="calendar-empty"><?= icon('calendar') ?><p>Puedes cambiar el día y la hora de tu cita desde Mis citas.</p><a href="/appointments.php">Gestionar mi cita →</a></div>
      <?php else: ?>
        <div class="day-agenda"><?php $count=0; foreach ($data['appointments'] as $a): if (substr($a['start'],0,10)!==$selected) continue; $count++; ?><a class="day-event" href="/appointments.php#appointment-<?= $a['id'] ?>"><strong><?= h(substr($a['start'],11,5)) ?> · <?= h($a['patient']??$a['doctor']) ?></strong><small><?= h($a['doctor']) ?></small></a><?php endforeach; if (!$count): ?><p class="calendar-empty">Sin consultas reservadas este día.</p><?php endif ?></div>
        <h4>Disponibilidad</h4><?php foreach ($data['schedules'] as $s): if ($s['date']!==$selected) continue; ?><a class="availability-event" href="/schedule.php?edit=<?= $s['id'] ?>#publish"><strong><?= h($s['start'].' – '.$s['end']) ?></strong><small><?= h($s['doctor']) ?> · <?= $s['duration'] ?> min</small></a><?php endforeach ?>
      <?php endif ?>
    </aside></div>
  </section>
</div>
<?php
$config=['role'=>$role,'doctors'=>array_map(fn($d)=>['id'=>(int)$d['docid'],'name'=>$d['docname'],'specialty_id'=>(int)$d['specialties'],'specialty'=>$d['sname']],$doctors),'doctor'=>$doctorId,'specialty'=>$specialty,'selected'=>$selected,'month'=>$monthDate->format('Y-m-d'),'blocked'=>(bool)$blocked,'reschedule'=>(int)($reschedule['appoid']??0),'csrf'=>$_SESSION['csrf'],'data'=>$data];
?>
<script type="application/json" id="calendar-config"><?= json_encode($config,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?></script>
<script src="/js/calendar.js?v=2" defer></script>
