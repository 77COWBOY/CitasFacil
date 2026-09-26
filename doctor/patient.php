<?php

session_start();

require_once "auth.php";
require_once "db.php";

$docid = (string)$_SESSION["docid"];

$sql = "SELECT DISTINCT
            p.pid,
            p.pname,
            p.pemail,
            p.paddress,
            p.pnic,
            p.pdob,
            p.ptel
        FROM patient p
        INNER JOIN appointment a ON p.pid = a.pid
        INNER JOIN schedule s ON a.scheduleid = s.scheduleid
        WHERE s.docid = ?
        ORDER BY p.pname";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $docid);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis pacientes</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<?php include "menu.php"; ?>

<main class="content">

    <h1>Mis pacientes</h1>

    <table>

        <thead>
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>NIC</th>
                <th>Fecha nacimiento</th>
            </tr>
        </thead>

        <tbody>

        <?php if (mysqli_num_rows($result) > 0): ?>

            <?php while ($row = mysqli_fetch_assoc($result)): ?>

                <tr>
                    <td><?php echo htmlspecialchars($row["pname"]); ?></td>
                    <td><?php echo htmlspecialchars($row["pemail"]); ?></td>
                    <td><?php echo htmlspecialchars($row["ptel"]); ?></td>
                    <td><?php echo htmlspecialchars($row["paddress"]); ?></td>
                    <td><?php echo htmlspecialchars($row["pnic"]); ?></td>
                    <td><?php echo htmlspecialchars($row["pdob"]); ?></td>
                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="6">No hay pacientes asociados.</td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>

</main>

</body>
</html>