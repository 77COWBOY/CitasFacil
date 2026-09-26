<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/login.css">

    <title>Iniciar Sesion</title>

</head>

<body>

    <?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    session_start();

    $_SESSION["user"]="";
    $_SESSION["usertype"]="";

    // Set the new timezone
    date_default_timezone_set('America/Managua');
    $date = date('Y-m-d');
    $_SESSION["date"]=$date;

    //import database
    require_once __DIR__ . '/../connection.php';

    if($_POST){

        $email=$_POST['useremail'];
        $password=$_POST['userpassword'];

        $error='<label for="promter" class="form-label"></label>';

        $result= $database->query("select * from webuser where email='$email'");

        if($result->num_rows==1){

            $utype=$result->fetch_assoc()['usertype'];

            if ($utype=='p'){

                //TODO
                $checker = $database->query("select * from patient where pemail='$email' and ppassword='$password'");

                if ($checker->num_rows==1){

                    //   Patient dashbord
                    $_SESSION['user']=$email;
                    $_SESSION['usertype']='p';

                    header('location: patient/index.php');

                }else{

                    $error='<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Wrong credentials: Invalid email or password</label>';

                }

            }elseif($utype=='a'){

                //TODO
                $checker = $database->query("select * from admin where aemail='$email' and apassword='$password'");

                if ($checker->num_rows==1){

                    //   Admin dashbord
                    $_SESSION['user']=$email;
                    $_SESSION['usertype']='a';

                    header('location: admin/index.php');

                }else{

                    $error='<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Wrong credentials: Invalid email or password</label>';

                }

            }elseif($utype=='d'){

                //TODO
                $checker = $database->query("select * from doctor where docemail='$email' and docpassword='$password'");

                if ($checker->num_rows==1){

                    //   doctor dashbord
                    $_SESSION['user']=$email;
                    $_SESSION['usertype']='d';

                    $doctor = $checker->fetch_assoc();

                    $_SESSION['docid']=$doctor['docid'];
                    $_SESSION['docname']=$doctor['docname'];

                    header('location: index.php');

                }else{

                    $error='<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Wrong credentials: Invalid email or password</label>';

                }

            }

        }else{

            $error='<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">NO se pudo encontrar ninguna cuenta para este correo electrónico.</label>';

        }

    }else{

        $error='<label for="promter" class="form-label">&nbsp;</label>';

    }

    ?>

    <center>

    <div class="container">

        <table border="0" style="margin: 0;padding: 0;width: 60%;">

            <tr>

                <td>

                    <p class="header-text">Bienvenido de nuevo!</p>

                </td>

            </tr>

        <div class="form-body">

            <tr>

                <td>

                    <p class="sub-text">Inicie sesión con sus datos para continuar</p>

                </td>

            </tr>

            <tr>

                <form action="" method="POST" >

                <td class="label-td">

                    <label for="useremail" class="form-label">Email: </label>

                </td>

            </tr>

            <tr>

                <td class="label-td">

                    <input type="email" name="useremail" class="input-text" placeholder="Correo Electrónico" required>

                </td>

            </tr>

            <tr>

                <td class="label-td">

                    <label for="userpassword" class="form-label">Contraseña: </label>

                </td>

            </tr>

            <tr>

                <td class="label-td">

                    <input type="Password" name="userpassword" class="input-text" placeholder="Contraseña" required>

                </td>

            </tr>

            <tr>

                <td><br>

                <?php echo $error ?>

                </td>

            </tr>

            <tr>

                <td>

                    <input type="submit" value="Iniciar Sesión" class="login-btn btn-primary btn">

                </td>

            </tr>

        </div>

            <tr>

                <td>

                    <br>

                    <label for="" class="sub-text" style="font-weight: 280;">No tienes una cuenta? </label>

                    <a href="signup.php" class="hover-link1 non-style-link">Regístrate</a>

                    <br><br><br>

                </td>

            </tr>

                    </form>

        </table>

    </div>

</center>

</body>

</html>