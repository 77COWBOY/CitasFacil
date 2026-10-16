<section class="hero hero-patient">
  <div class="hero-content">
    <h2>¿Necesitas una cita?</h2>
    <p>Encuentra médicos disponibles según la especialidad que necesites.</p>
    <div class="hero-actions"><a class="button" href="<?= $active?'/appointments.php':'/schedule.php' ?>"><?= icon('calendar') ?><?= $active?'Ver mi cita':'Hacer cita' ?></a></div>
    <small class="hero-policy">Puedes tener una cita activa a la vez.</small>
  </div>
  <img class="hero-art" src="/img/care-team.svg" alt="" aria-hidden="true">
</section>
<div class="stats-grid">
<?php
stat_card('blue','calendar','Próxima cita',$next?short_date($next['scheduledate']):'Sin cita',$next?appointment_time($next):'Agenda tu consulta','/appointments.php','Ver detalles');
stat_card('green','calendar','Citas activas',$active,'Reservas próximas','/appointments.php','Ver mis citas');
stat_card('amber','clock','Citas anteriores',$past,'Historial de reservas','/appointments.php?history=1','Ver historial');
stat_card('purple','medical','Médicos registrados',$doctorCount,'Explora las especialidades','/doctors.php','Ver médicos');
?>
</div>
<?php next_appointment($next,$role); ?>
