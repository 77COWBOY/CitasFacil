<?php
function handle_appointment_action($role,$profile) {
    if ($_SERVER['REQUEST_METHOD']!=='POST') return '';
    check_csrf();
    try {
        $action=$_POST['action']??'';
        if (in_array($action,['book','reschedule'],true) && $role==='p') {
            if ($action==='reschedule' && (int)($_POST['appointment_id']??0)<1) throw new RuntimeException('Selecciona la cita que quieres cambiar.');
            $id=(int)($_POST['id']??0); $start=$_POST['start']??'';
            if (!empty($_POST['slot'])) {
                $parts=explode('|',$_POST['slot'],2); $id=(int)$parts[0]; $start=$parts[1]??'';
            }
            book_appointment($profile['pid'],$id,$start,$action==='reschedule'?(int)($_POST['appointment_id']??0):0);
            $_SESSION['flash']=$action==='reschedule'?'Tu cita se cambió de hora correctamente.':'Tu cita está confirmada. Te esperamos a la hora elegida.';
            go('/appointments.php');
        }
        if ($action==='cancel') {
            cancel_appointment($role,$profile,(int)($_POST['id']??0));
            $_SESSION['flash']='Cita cancelada. La hora vuelve a estar disponible.'; go('/appointments.php');
        }
        if ($action==='schedule') save_schedule($role,$profile,$_POST);
        elseif ($action==='delete_schedule') delete_schedule($role,$profile,(int)($_POST['id']??0));
        else throw new RuntimeException('Acción no permitida.');
        $_SESSION['flash']=$action==='schedule'?'Disponibilidad guardada.':'Horario eliminado.';
        go('/schedule.php');
    } catch (Throwable $e) {
        error_log($e->getMessage());
        return $e instanceof RuntimeException && !($e instanceof mysqli_sql_exception)?$e->getMessage():($e->getCode()===1062?'Esa hora acaba de ocuparse. Actualiza la agenda y elige otra.':'No se pudo guardar el cambio. Inténtalo de nuevo.');
    }
}
