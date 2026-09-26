<?php

session_start();

if (!isset($_SESSION['user']) || $_SESSION['usertype'] != 'd') {
    header("location: login.php");
    exit();
}

require_once __DIR__ . '/../connection.php';

$email = $_SESSION['user'];

$sql = "SELECT 
            d.docid,
            d.docemail,
            d.docname,
            d.docnic,
            d.doctel,
            s.sname
        FROM doctor d
        LEFT JOIN specialties s ON d.specialties = s.id
        WHERE d.docemail = '$email'";

$result = $database->query($sql);

if ($result->num_rows == 0) {
    session_destroy();
    header("location: login.php");
    exit();
}

$doctor = $result->fetch_assoc();

$docid = $doctor['docid'];

/* Total de citas */
$resultAppointments = $database->query("
    SELECT COUNT(*) AS total
    FROM appointment a
    INNER JOIN schedule s ON a.scheduleid = s.scheduleid
    WHERE s.docid = '$docid'
");

$totalAppointments = $resultAppointments->fetch_assoc()['total'];

/* Total de pacientes */
$resultPatients = $database->query("
    SELECT COUNT(DISTINCT a.pid) AS total
    FROM appointment a
    INNER JOIN schedule s ON a.scheduleid = s.scheduleid
    WHERE s.docid = '$docid'
");

$totalPatients = $resultPatients->fetch_assoc()['total'];

/* Total de horarios */
$resultSchedules = $database->query("
    SELECT COUNT(*) AS total
    FROM schedule
    WHERE docid = '$docid'
");

$totalSchedules = $resultSchedules->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/style.css">

    <title>Dashboard - eDoc</title>

</head>

<body>

<?php include "menu.php"; ?>

<main class="content">

    <div class="dashboard-header">

        <div>

            <p class="dashboard-welcome">
                Panel médico
            </p>

            <h1>
                Bienvenido, Dr. <?php echo htmlspecialchars($doctor['docname']); ?>
            </h1>

            <p class="dashboard-specialty">
                <?php echo htmlspecialchars($doctor['sname'] ?? 'No registrada'); ?>
            </p>

        </div>

    </div>


    <div class="dashboard-cards">

        <div class="dashboard-card">

            <h3>Citas</h3>

            <p>
                <?php echo $totalAppointments; ?>
            </p>

            <span>
                Total de citas registradas
            </span>

        </div>


        <div class="dashboard-card">

            <h3>Pacientes</h3>

            <p>
                <?php echo $totalPatients; ?>
            </p>

            <span>
                Pacientes atendidos
            </span>

        </div>


        <div class="dashboard-card">

            <h3>Horarios</h3>

            <p>
                <?php echo $totalSchedules; ?>
            </p>

            <span>
                Sesiones registradas
            </span>

        </div>

    </div>


    <div class="dashboard-section">

        <h2>
            Accesos rápidos
        </h2>


        <div class="quick-actions">


            <a href="appointment.php" class="quick-card">

                <div class="quick-icon">
                    <span>📅</span>
                </div>

                <div class="quick-info">

                    <h3>
                        Mis citas
                    </h3>

                    <p>
                        Consulta y administra las citas de tus pacientes.
                    </p>

                </div>

                <div class="quick-arrow">
                    →
                </div>

            </a>


            <a href="patient.php" class="quick-card">

                <div class="quick-icon">
                    <span>👥</span>
                </div>

                <div class="quick-info">

                    <h3>
                        Mis pacientes
                    </h3>

                    <p>
                        Consulta la información de tus pacientes.
                    </p>

                </div>

                <div class="quick-arrow">
                    →
                </div>

            </a>


            <a href="schedule.php" class="quick-card">

                <div class="quick-icon">
                    <span>🕐</span>
                </div>

                <div class="quick-info">

                    <h3>
                        Mis horarios
                    </h3>

                    <p>
                        Crea y administra tus sesiones médicas.
                    </p>

                </div>

                <div class="quick-arrow">
                    →
                </div>

            </a>


            <a href="doctors.php" class="quick-card">

                <div class="quick-icon">
                    <span>👨‍⚕️</span>
                </div>

                <div class="quick-info">

                    <h3>
                        Mi perfil
                    </h3>

                    <p>
                        Consulta y actualiza tu información profesional.
                    </p>

                </div>

                <div class="quick-arrow">
                    →
                </div>

            </a>


        </div>

    </div>


</main>

</body>

</html>