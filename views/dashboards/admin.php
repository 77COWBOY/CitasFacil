<section class="hero hero-admin">
  <div class="hero-content">
    <span class="eyebrow">PANEL DE ADMINISTRACIÓN</span>
    <h2>Una visión clara de tu clínica.</h2>
    <p>Coordina tu equipo médico, administra la disponibilidad y consulta las reservas.</p>
    <div class="hero-actions"><a class="button" href="/doctors.php#editor">Agregar médico</a><a class="button secondary" href="/schedule.php#publish">Crear horario</a></div>
  </div>
  <div class="admin-hero-card"><?= icon('medical') ?><strong><?= $today ?></strong><span>citas para hoy</span><a href="/appointments.php?date=<?= date('Y-m-d') ?>">Consultar actividad ›</a></div>
</section>
<div class="stats-grid">
<?php
stat_card('blue','medical','Médicos',$doctorCount,'Equipo de la clínica','/doctors.php','Gestionar médicos');
stat_card('green','users','Pacientes',$patientCount,'Cuentas registradas','/patients.php','Ver pacientes');
stat_card('amber','ticket','Reservas próximas',$active,'En todos los consultorios','/appointments.php','Ver reservas');
stat_card('purple','clock','Horarios próximos',$scheduleCount,'Sesiones publicadas','/schedule.php','Ver horarios');
?>
</div>
<div class="quick-links"><a href="/doctors.php"><?= icon('medical') ?><span><strong>Equipo médico</strong><small>Especialidades y cuentas</small></span>›</a><a href="/patients.php"><?= icon('users') ?><span><strong>Pacientes</strong><small>Directorio de la clínica</small></span>›</a><a href="/schedule.php"><?= icon('calendar') ?><span><strong>Disponibilidad</strong><small>Publicación y cupos</small></span>›</a></div>
<?php agenda_preview($appointments,$role); ?>
