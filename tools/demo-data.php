<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }

/** Agrega datos ficticios de forma repetible, conservando cuentas y reservas. */
function seed_demo_catalog(mysqli $db): array {
    $run=function(string $sql,array $args=[]) use ($db) {
        $stmt=$db->prepare($sql);
        if ($args) $stmt->bind_param(str_repeat('s',count($args)),...$args);
        $stmt->execute(); return $stmt;
    };
    $lock=$db->query("SELECT GET_LOCK(CONCAT(DATABASE(),':demo-catalog'),10)")->fetch_row()[0];
    if ((int)$lock!==1) throw new RuntimeException('Otra carga de demostración está en curso.');
    $result=['doctors'=>0,'schedules'=>0,'skipped'=>0];
    try {
        $db->begin_transaction();
        $fixtures=[
            ['ana-martinez','Dra. Ana Martínez',18],
            ['carlos-mendoza','Dr. Carlos Mendoza',5],
            ['sofia-ramirez','Dra. Sofía Ramírez',38],
            ['valeria-lopez','Dra. Valeria López',13],
            ['elena-torres','Dra. Elena Torres',32],
            ['diego-herrera','Dr. Diego Herrera',34],
            ['andres-castro','Dr. Andrés Castro',35],
            ['lucia-morales','Dra. Lucía Morales',29],
            ['gabriel-reyes','Dr. Gabriel Reyes',16],
            ['paula-navarro','Dra. Paula Navarro',14],
            ['ricardo-silva','Dr. Ricardo Silva',36],
            ['daniela-ortiz','Dra. Daniela Ortiz',23],
            ['fernando-vega','Dr. Fernando Vega',54],
            ['isabel-rojas','Dra. Isabel Rojas',20],
            ['miguel-santos','Dr. Miguel Santos',51],
            ['camila-flores','Dra. Camila Flores',45],
        ];
        $businessDays=[];
        $date=new DateTimeImmutable('tomorrow',new DateTimeZone('America/Managua'));
        while (count($businessDays)<10) {
            if ((int)$date->format('N')<=5) $businessDays[]=$date->format('Y-m-d');
            $date=$date->modify('+1 day');
        }
        $windows=[['08:00','11:00'],['09:00','12:00'],['13:00','16:00'],['14:00','17:00']];
        foreach ($fixtures as $i=>[$slug,$name,$specialty]) {
            $email=$slug.'@demo.example.invalid';
            $doctor=$run('SELECT * FROM doctor WHERE docemail=? FOR UPDATE',[$email])->get_result()->fetch_assoc();
            if (!$doctor) {
                if ($run('SELECT email FROM webuser WHERE email=? FOR UPDATE',[$email])->get_result()->num_rows) throw new RuntimeException('Existe una cuenta con el correo de demostración '.$email.'. No se modificó.');
                $run('INSERT INTO webuser(email,usertype) VALUES (?,\'d\')',[$email]);
                // No se publican ni reutilizan claves de los médicos agregados.
                $hash=password_hash(bin2hex(random_bytes(24)),PASSWORD_DEFAULT);
                $run('INSERT INTO doctor(docemail,docname,docpassword,docnic,doctel,specialties) VALUES (?,?,?,?,?,?)',[$email,$name,$hash,'DEMO'.sprintf('%04d',$i+1),'0000-'.(1001+$i),$specialty]);
                $doctor=['docid'=>$db->insert_id,'specialties'=>$specialty];
                $result['doctors']++;
            }
            $spec=$run('SELECT sname FROM specialties WHERE id=?',[$doctor['specialties']])->get_result()->fetch_assoc();
            if (!$spec) throw new RuntimeException('La especialidad de demostración no existe.');
            [$start,$end]=$windows[$i%count($windows)];
            foreach ([0,2,4,5,7,9] as $offset) {
                $day=$businessDays[$offset];
                // La salida desconocida de un horario anterior también bloquea ese día.
                $conflict=$run('SELECT scheduleid FROM schedule WHERE docid=? AND scheduledate=? AND (endtime IS NULL OR (scheduletime<? AND endtime>?)) LIMIT 1',[$doctor['docid'],$day,$end,$start])->get_result()->num_rows;
                if ($conflict) { $result['skipped']++; continue; }
                $run('INSERT INTO schedule(docid,title,scheduledate,scheduletime,endtime,duration_minutes) VALUES (?,?,?,?,?,?)',[$doctor['docid'],'Consulta de '.mb_strtolower($spec['sname']),$day,$start,$end,30]);
                $result['schedules']++;
            }
        }
        $db->commit();
        return $result;
    } catch (Throwable $e) { $db->rollback(); throw $e; }
    finally { $db->query("SELECT RELEASE_LOCK(CONCAT(DATABASE(),':demo-catalog'))"); }
}
