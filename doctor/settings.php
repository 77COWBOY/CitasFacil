<?php

require_once "auth.php";
require_once "db.php";

$docid = $_SESSION["docid"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $newPassword = trim($_POST["password"]);
    $confirmPassword = trim($_POST["confirm_password"]);

    if ($newPassword == "" || $confirmPassword == "") {

        $message = "Complete ambos campos.";

    } elseif ($newPassword != $confirmPassword) {

        $message = "Las contraseñas no coinciden.";

    } else {

        $sql = "UPDATE doctor
                SET docpassword = ?
                WHERE docid = ?";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $newPassword, $docid);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Contraseña actualizada correctamente.";
        } else {
            $message = "No se pudo actualizar la contraseña.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Configuración</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<?php include "menu.php"; ?>

<main class="content">

    <h1>Configuración</h1>

    <?php if ($message != ""): ?>

        <div class="success">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <div class="form-box">

        <h2>Cambiar contraseña</h2>

        <form method="POST">

            <label>Nueva contraseña</label>

            <input
                type="password"
                name="password"
                required
            >

            <label>Confirmar contraseña</label>

            <input
                type="password"
                name="confirm_password"
                required
            >

            <button type="submit">
                Cambiar contraseña
            </button>

        </form>

    </div>

</main>

</body>
</html>