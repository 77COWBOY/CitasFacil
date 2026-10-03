<section class="hero hero-patient">
  <div class="hero-content">
    <h2>¿Necesitas una cita?</h2>
    <p>Encuentra médicos disponibles según la especialidad que necesites.</p>
    <form class="hero-search" action="/doctors.php" method="get">
      <label class="search-box"><span class="sr-only">Buscar médico o especialidad</span><?= icon('search') ?><input name="q" placeholder="Buscar médico o especialidad…" maxlength="100"></label>
      <button>Buscar médico</button>
    </form>
    <div class="specialty-chips">
      <?php foreach ([18=>'Medicina general',11=>'Odontología',13=>'Dermatología',38=>'Pediatría'] as $id=>$label): ?>
      <a href="/doctors.php?specialty=<?= $id ?>"><?= icon('medical') ?><?= h($label) ?></a>
      <?php endforeach ?>
      <a href="/doctors.php"><?= icon('grid') ?>Ver todas</a>
    </div>
  </div>
  <img class="hero-art" src="/img/care-team.svg" alt="" aria-hidden="true">
</section>
<div class="stats-grid">
<?php
stat_card('blue','calendar','Próxima cita',$next?short_date($next['scheduledate']):'Sin cita',$next?date('h:i a',strtotime($next['scheduletime'])):'Agenda tu consulta','/appointments.php','Ver detalles');
stat_card('green','calendar','Citas activas',$active,'Reservas próximas','/appointments.php','Ver mis citas');
stat_card('amber','clock','Citas anteriores',$past,'Historial de reservas','/appointments.php?history=1','Ver historial');
stat_card('purple','medical','Médicos registrados',$doctorCount,'Explora las especialidades','/doctors.php','Ver médicos');
?>
</div>
<?php next_appointment($next,$role); ?>
