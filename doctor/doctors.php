<?php

require_once "auth.php";
require_once "db.php";

$docid = $_SESSION["docid"];

$sql = "SELECT 
            d.*,
            s.sname
        FROM doctor d
        LEFT JOIN specialties s ON d.specialties = s.id
        WHERE d.docid = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $docid);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($result);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mi perfil</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<?php include "menu.php"; ?>

<main class="content">

    <h1>Mi perfil</h1>

    <div class="profile">

        <p>
            <strong>Nombre:</strong>
            <?php echo htmlspecialchars($doctor["docname"]); ?>
        </p>

        <p>
            <strong>Correo:</strong>
            <?php echo htmlspecialchars($doctor["docemail"]); ?>
        </p>

        <p>
            <strong>NIC:</strong>
            <?php echo htmlspecialchars($doctor["docnic"]); ?>
        </p>

        <p>
            <strong>Teléfono:</strong>
            <?php echo htmlspecialchars($doctor["doctel"]); ?>
        </p>

        <p>
            <strong>Especialidad:</strong>
            <?php echo htmlspecialchars($doctor["sname"] ?? "Sin especialidad"); ?>
        </p>

        <a class="button" href="edit-doc.php">
            Editar información
        </a>

    </div>

</main>

</body>
</html>