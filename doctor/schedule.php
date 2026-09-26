
<?php

require_once "auth.php";
require_once "db.php";

$docid = (string)$_SESSION["docid"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"]);
    $date = $_POST["date"];
    $time = $_POST["time"];
    $nop = intval($_POST["nop"]);

    if ($title == "" || $date == "" || $time == "" || $nop <= 0) {

        $message = "Complete correctamente todos los campos.";

    } else {

        $sql = "INSERT INTO schedule
                (docid, title, scheduledate, scheduletime, nop)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $docid,
            $title,
            $date,
            $time,
            $nop
        );

        if (mysqli_stmt_execute($stmt)) {
            $message = "Horario creado correctamente.";
        } else {
            $message = "Error al crear el horario.";
        }
    }
}

$sql = "SELECT *
        FROM schedule
        WHERE docid = ?
        ORDER BY scheduledate ASC, scheduletime ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $docid);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Horarios</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<?php include "menu.php"; ?>

<main class="content">

    <h1>Mis horarios</h1>

    <?php if ($message != ""): ?>
        <div class="success">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-box">

        <h2>Crear nueva sesión</h2>

        <form method="POST">

            <label>Título</label>
            <input type="text" name="title" placeholder="Consulta médica" required>

            <label>Fecha</label>
            <input type="date" name="date" required>

            <label>Hora</label>
            <input type="time" name="time" required>

            <label>Número de pacientes</label>
            <input type="number" name="nop" min="1" required>

            <button type="submit">Crear sesión</button>

        </form>

    </div>

    <h2>Sesiones registradas</h2>

    <table>

        <thead>
            <tr>
                <th>Título</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Pacientes permitidos</th>
            </tr>
        </thead>

        <tbody>

        <?php while ($row = mysqli_fetch_assoc($result)): ?>

            <tr>
                <td><?php echo htmlspecialchars($row["title"]); ?></td>
                <td><?php echo htmlspecialchars($row["scheduledate"]); ?></td>
                <td><?php echo htmlspecialchars($row["scheduletime"]); ?></td>
                <td><?php echo htmlspecialchars($row["nop"]); ?></td>
            </tr>

        <?php endwhile; ?>

        </tbody>

    </table>

</main>

</body>
</html>