# Mejoras de reservas y horarios

Este documento conserva la revisión inicial. El sistema de cupos descrito a continuación fue reemplazado por la [agenda por horas](AGENDA.md), con calendario de mes y semana, horas concretas y cambio de cita sin perder la reserva original.

Se aplicaron los cinco pendientes de Pendiente.docx al proyecto local. Posteriormente se rediseñó el hero de la portada con la referencia visual proporcionada, incluyendo iconos animados; los detalles están en [HERO-REDESIGN.md](HERO-REDESIGN.md).

| Pendiente | Resultado |
| --- | --- |
| Quitar el llamado a la acción de Inicio | Se retiró el botón de la portada pública. Los accesos de iniciar sesión y registro siguen en la navegación. |
| Sustituir el buscador inicial por Hacer cita | El panel del paciente lleva al flujo de reserva. Si tiene una cita activa, muestra Ver mi cita. |
| Definir inicio y salida de los médicos | Publicación y edición con ambas horas obligatorias, duración visible y validación de superposiciones del mismo médico. |
| Una cita por paciente | Máximo una cita activa entre todos los médicos y horarios. Una cita está activa hasta su hora de salida; cancelar o finalizar permite reservar otra. Se valida en el servidor y con bloqueo del paciente. |
| Ocultar el buscador tras buscar | Los filtros del directorio y de horarios quedan plegados después de una búsqueda. Cambiar búsqueda permite volver a abrirlos y Limpiar filtros reinicia la selección. |

Las reservas y cancelaciones muestran confirmaciones cuando JavaScript está disponible. El formulario de horarios calcula la duración mientras se cambian las horas. La reserva confirmada abre Mis citas, y los mensajes indican el resultado de cada operación. Se mantiene la atención por número de turno dentro del rango; no se asigna una hora individual a cada paciente.

Los horarios con reservas conservan su médico, fecha e inicio. Pueden ajustar salida y cupos, sin reducir estos por debajo de las reservas existentes. Los rangos contiguos están permitidos y los superpuestos se rechazan.

## Compatibilidad de datos

La migración `database/migrations/20261016_schedule_range.sql` agrega `endtime` como columna nullable. `tools/setup.php` la aplica automáticamente al iniciar y permite volver a ejecutarla. En esta instalación ya se aplicó después de crear un respaldo; se compararon conteos y hashes de los registros originales en las siete tablas sin diferencias. También se restauró y verificó un respaldo posterior.

Los horarios antiguos conservan todos sus registros y muestran salida sin definir. Un médico o administrador debe completar esa salida desde Editar para aceptar nuevas reservas. Las citas anteriores se conservan; para las que todavía no tienen salida se mantiene la hora inicial como referencia temporal hasta completar el rango. No se cancelan automáticamente reservas existentes si un paciente ya tenía más de una.

## Verificación

Las pruebas funcionales usan una base independiente `edoc_pending_check_20261016`, sin registros reales. Incluyen publicación, rangos inválidos, superposición, edición de horarios reservados, permisos, búsqueda, reserva, cancelación, historial y citas en curso. Dos servidores PHP con sesiones independientes comprueban tanto una reserva simultánea del mismo paciente como dos pacientes compitiendo por el último cupo.

```powershell
$env:EDOC_DB_NAME='edoc_pending_check_20261016'
$env:EDOC_TEST_URL='http://127.0.0.1:8089'
$env:EDOC_TEST_URL_SECOND='http://127.0.0.1:8090'
runtime\php\php.exe -c .local\php.ini tests/dashboard-test.php
runtime\php\php.exe -c .local\php.ini tests/pending-test.php
```

Los dos servidores de prueba deben iniciarse con la misma variable EDOC_DB_NAME. La segunda URL activa las comprobaciones de concurrencia. Los registros de esta ejecución quedan en `.local/pending-tests.txt` y `.local/pending-dashboard-tests.txt`.

Resultado: 106 comprobaciones en la suite de paneles y 83 en la suite de pendientes (30 comprobaciones base son compartidas). Sintaxis correcta en 33 archivos PHP y en JavaScript. Se comprobaron la integridad de tablas y la restauración de respaldo. La sesión no ofrece un navegador controlable, por lo que no se acredita revisión visual ni ejecución de los diálogos en un navegador.
