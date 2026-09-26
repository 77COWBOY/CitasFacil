<nav class="sidebar">

    <h2>eDoc</h2>

    <p class="doctor-name">
        Dr. <?php echo htmlspecialchars($_SESSION["docname"]); ?>
    </p>

    <a href="index.php">Dashboard</a>

    <a href="appointment.php">Mis citas</a>

    <a href="patient.php">Mis pacientes</a>

    <a href="schedule.php">Mis horarios</a>

    <a href="doctors.php">Mi perfil</a>

    <a href="settings.php">Configuración</a>

    <a href="delete-session.php" class="logout">
        Cerrar sesión
    </a>

</nav>