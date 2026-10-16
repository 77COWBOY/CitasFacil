<?php
$values=($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='schedule')?$_POST:($edit?['id'=>$edit['scheduleid'],'docid'=>$edit['docid'],'date'=>$edit['scheduledate'],'time'=>substr($edit['scheduletime'],0,5),'endtime'=>substr($edit['endtime']??'',0,5),'duration'=>$edit['duration_minutes']]:['duration'=>30,'date'=>$selected,'docid'=>$doctorId]);
$hasBookings=$edit && query('SELECT appoid FROM appointment WHERE scheduleid=? LIMIT 1',[$edit['scheduleid']])->get_result()->num_rows;
?>
<details class="schedule-editor" id="publish" <?= $edit || ($error && ($_POST['action']??'')==='schedule')?'open':'' ?>>
  <summary><?= icon('calendar') ?> <?= $edit?'Editar disponibilidad':'Publicar horario de consulta' ?><span aria-hidden="true">＋</span></summary>
  <form method="post" data-schedule-form class="schedule-form">
    <?= token() ?><input type="hidden" name="action" value="schedule"><input type="hidden" name="id" value="<?= h($values['id']??0) ?>">
    <?php if ($role==='a'): ?><label>Médico<select name="docid" required <?= $edit?'disabled':'' ?>><?php foreach ($doctors as $d): ?><option value="<?= (int)$d['docid'] ?>" <?= ($edit['docid']??$values['docid']??0)==$d['docid']?'selected':'' ?>><?= h($d['docname']) ?></option><?php endforeach ?></select></label><?php if ($edit): ?><input type="hidden" name="docid" value="<?= (int)$edit['docid'] ?>"><?php endif; endif ?>
    <?php foreach ([['date','Fecha','date'],['time','Desde','time'],['endtime','Hasta','time']] as [$key,$label,$type]): ?><label><?= $label ?><input name="<?= $key ?>" type="<?= $type ?>" value="<?= h($values[$key]??'') ?>" <?= $key==='date'?'min="'.date('Y-m-d').'"':'' ?> <?= $hasBookings && $key!=='endtime'?'readonly':'' ?> required></label><?php endforeach ?>
    <label>Duración por consulta<select name="duration" <?= $hasBookings?'disabled':'' ?>><?php foreach ([15,20,30,45,60] as $minutes): ?><option value="<?= $minutes ?>" <?= (int)($hasBookings?$edit['duration_minutes']:($values['duration']??30))===$minutes?'selected':'' ?>><?= $minutes ?> minutos</option><?php endforeach ?></select></label>
    <?php if ($hasBookings): ?><input type="hidden" name="duration" value="<?= (int)$edit['duration_minutes'] ?>"><p class="muted">Tiene citas: la fecha, el inicio y la duración se conservan.</p><?php endif ?>
    <p class="range-preview" data-range-preview aria-live="polite">Las horas se generan automáticamente.</p>
    <div class="form-actions"><button><?= $edit?'Guardar cambios':'Guardar disponibilidad' ?></button><?php if ($edit): ?><a href="/schedule.php">Salir de edición</a><?php endif ?></div>
  </form>
  <?php if ($edit): ?><form method="post" class="delete-schedule" data-confirm="¿Eliminar este horario? Debe estar libre de citas." data-confirm-label="Eliminar horario"><?= token() ?><input type="hidden" name="action" value="delete_schedule"><input type="hidden" name="id" value="<?= (int)$edit['scheduleid'] ?>"><button class="text-danger">Eliminar horario</button></form><?php endif ?>
</details>
