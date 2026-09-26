<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis citas</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<?php include "menu.php"; ?>

<main class="content">

    <h1>Mis citas</h1>

    <?php
    require_once __DIR__ . '/../connection.php';

    $email = $_SESSION["user"];

    $doctor = $database->query("SELECT docid FROM doctor WHERE docemail='$email'");
    $doc = $doctor->fetch_assoc();
    $docid = $doc["docid"];

    $appointments = $database->query("
        SELECT 
            appointment.appoid,
            appointment.apponum,
            appointment.appodate,
            patient.pname,
            schedule.title,
            schedule.scheduledate,
            schedule.scheduletime
        FROM appointment
        INNER JOIN patient ON appointment.pid = patient.pid
        INNER JOIN schedule ON appointment.scheduleid = schedule.scheduleid
        WHERE schedule.docid = '$docid'
        ORDER BY appointment.appodate DESC
    ");
    ?>

    <table>

        <thead>
            <tr>
                <th>N° Cita</th>
                <th>Paciente</th>
                <th>Sesión</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Fecha de cita</th>
            </tr>
        </thead>

        <tbody>

        <?php
        if ($appointments->num_rows > 0) {

            while ($row = $appointments->fetch_assoc()) {
        ?>

                <tr>
                    <td><?php echo $row["apponum"]; ?></td>
                    <td><?php echo htmlspecialchars($row["pname"]); ?></td>
                    <td><?php echo htmlspecialchars($row["title"]); ?></td>
                    <td><?php echo $row["scheduledate"]; ?></td>
                    <td><?php echo $row["scheduletime"]; ?></td>
                    <td><?php echo $row["appodate"]; ?></td>
                </tr>

        <?php
            }

        } else {
        ?>

            <tr>
                <td colspan="6">No tienes citas registradas.</td>
            </tr>

        <?php
        }
        ?>

        </tbody>

    </table>

</main>

</body>
</html>