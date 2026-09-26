<?php

require_once "auth.php";
require_once "db.php";

$docid = $_SESSION["docid"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $nic = trim($_POST["nic"]);
    $tel = trim($_POST["tel"]);
    $specialty = intval($_POST["specialty"]);

    $sql = "UPDATE doctor
            SET docname = ?,
                docnic = ?,
                doctel = ?,
                specialties = ?
            WHERE docid = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "sssii",
        $name,
        $nic,
        $tel,
        $specialty,
        $docid
    );

    if (mysqli_stmt_execute($stmt)) {

        $_SESSION["docname"] = $name;

        $message = "Información actualizada correctamente.";

    } else {

        $message = "Error al actualizar.";

    }
}

$sql = "SELECT * FROM doctor WHERE docid = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $docid);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($result);

$specialties = mysqli_query(
    $conn,
    "SELECT id, sname FROM specialties ORDER BY sname"
);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Doctor</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<?php include "menu.php"; ?>

<main class="content">

    <h1>Editar información</h1>

    <?php if ($message != ""): ?>
        <div class="success">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-box">

        <form method="POST">

            <label>Nombre</label>
            <input
                type="text"
                name="name"
                value="<?php echo htmlspecialchars($doctor["docname"]); ?>"
                required
            >

            <label>NIC</label>
            <input
                type="text"
                name="nic"
                value="<?php echo htmlspecialchars($doctor["docnic"]); ?>"
            >

            <label>Teléfono</label>
            <input
                type="text"
                name="tel"
                value="<?php echo htmlspecialchars($doctor["doctel"]); ?>"
            >

            <label>Especialidad</label>

            <select name="specialty">

                <?php while ($specialty = mysqli_fetch_assoc($specialties)): ?>

                    <option
                        value="<?php echo $specialty["id"]; ?>"
                        <?php
                        if ($specialty["id"] == $doctor["specialties"]) {
                            echo "selected";
                        }
                        ?>
                    >
                        <?php echo htmlspecialchars($specialty["sname"]); ?>
                    </option>

                <?php endwhile; ?>

            </select>

            <button type="submit">
                Guardar cambios
            </button>

        </form>

    </div>

</main>

</body>
</html>