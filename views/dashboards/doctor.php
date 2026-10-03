<section class="hero hero-doctor">
  <div class="hero-content">
    <span class="eyebrow">TU CONSULTORIO, ORGANIZADO</span>
    <h2>Todo listo para tu próxima consulta.</h2>
    <p>Revisa tus reservas, encuentra a tus pacientes y publica nuevos horarios.</p>
    <div class="hero-actions"><a class="button" href="/appointments.php">Ver mi agenda</a><a class="button secondary" href="/schedule.php#publish">Publicar horario</a></div>
  </div>
  <img class="hero-art" src="/img/care-team.svg" alt="" aria-hidden="true">
</section>
<div class="stats-grid">
<?php
stat_card('blue','calendar','Citas de hoy',$today,date('d/m/Y'),'/appointments.php?date='.date('Y-m-d'),'Ver citas de hoy');
stat_card('green','ticket','Citas próximas',$active,'Reservas por atender','/appointments.php','Ver agenda');
stat_card('amber','users','Mis pacientes',$patientCount,'Con reservas en tus consultas','/patients.php','Ver pacientes');
stat_card('purple','clock','Horarios próximos',$scheduleCount,'Sesiones publicadas','/schedule.php','Gestionar horarios');
?>
</div>
<?php next_appointment($next,$role); agenda_preview($appointments,$role); ?>
