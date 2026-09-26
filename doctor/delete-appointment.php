<?php

require_once "auth.php";
require_once "db.php";

if (!isset($_GET["id"])) {
    header("Location: appointment.php");
    exit();
}

$appoid = intval($_GET["id"]);
$docid = (string)$_SESSION["docid"];

$sql = "DELETE a
        FROM appointment a
        INNER JOIN schedule s ON a.scheduleid = s.scheduleid
        WHERE a.appoid = ? AND s.docid = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "is", $appoid, $docid);
mysqli_stmt_execute($stmt);

header("Location: appointment.php");
exit();

?>