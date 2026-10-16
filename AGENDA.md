# Agenda por horas

La reserva se realiza eligiendo especialidad, médico, día y hora. El calendario permite alternar entre mes y semana y consultar las horas disponibles de un día sin recargar toda la página. Se conserva el azul, la marca CitaFácil, la tipografía y la navegación por roles.

Los pacientes pueden confirmar una hora, cambiar su cita o cancelarla. Mis citas muestra tarjetas breves con médico, fecha y hora. Médicos y administración consultan las reservas y publican disponibilidad desde el calendario; el formulario permanece plegado hasta que se necesita.

## Reglas de atención

Cada horario define fecha, inicio, salida y duración de consulta. Las duraciones admitidas son 15, 20, 30, 45 y 60 minutos, con 30 por defecto. Por ejemplo, un médico disponible de 09:00 a 11:00 con consultas de 30 minutos ofrece 09:00, 09:30, 10:00 y 10:30. Nunca se genera una consulta que termine fuera del horario.

Solo se permite una cita activa por paciente. La cita finaliza a su hora individual de salida. Dos pacientes no pueden reservar la misma hora del mismo médico. Horas adyacentes sí se pueden reservar. El servidor valida fecha, duración, alineación con el horario y ocupación, incluso si se modifica el formulario o llegan solicitudes simultáneas desde sesiones diferentes.

Un cambio de hora conserva el identificador de la cita. Se actualiza dentro de una transacción y libera la hora anterior al confirmar la nueva. Si la nueva hora se ocupó, la reserva original sigue intacta. Se vuelven a comprobar los permisos al cancelar. Las citas finalizadas no se eliminan mediante la acción de cancelación.

El horario de un médico no puede superponerse con otro suyo. Si tiene citas, se conservan médico, fecha, inicio y duración. No se permite acortar la salida si alguna cita quedaría fuera. Para eliminar el horario, primero deben cambiarse o cancelarse sus citas.

## Conservación de datos

La migración `database/migrations/20261016_timed_appointments.sql` agrega inicio y fin a cada cita, duración al horario e índices para las consultas de agenda. No elimina ni asigna horas inventadas a reservas antiguas.

Una cita anterior sin hora concreta aparece como Hora por confirmar. Mantiene protegida la disponibilidad de ese médico en ese día hasta que se cambie de hora o se cancele. Los horarios antiguos sin salida requieren completar esa salida para generar horas reservables.

Las nuevas instalaciones ya no importan las consultas ficticias de 2022 ni la reserva de muestra para 2050. Empiezan con las jornadas próximas del catálogo y el paciente de ejemplo puede reservar directamente. Los registros de instalaciones existentes permanecen intactos.

Las columnas antiguas `nop` y `apponum` permanecen únicamente para conservar datos y respaldos históricos. No se utilizan para calcular disponibilidad ni se muestran como cupos o números de turno. Las nuevas reservas no asignan número de turno.

Se verificaron los 17 médicos, 104 horarios, 2 pacientes, 56 especialidades, 20 usuarios y el administrador contra el respaldo previo, conservando todos sus valores originales. En ese respaldo no había citas activas. Se generó un respaldo posterior y se comprobó su restauración en siete tablas.

## Revisión y limpieza del código

- `includes/appointments.php` concentra las reglas de horarios, reserva, cambio y cancelación. Panel, tarjetas y calendario usan las mismas reglas para reconocer una cita activa y mostrar su hora.
- `includes/calendar.php` consulta horarios y ocupación en bloque y genera las horas disponibles. La API responde a intervalos máximos de seis semanas. Un paciente recibe ocupación sin datos de otros pacientes; un médico solo recibe su propia agenda.
- `schedule.php` y `appointments.php` tienen controladores separados. Se retiró el envío de operaciones desde el panel, la inclusión de toda la pantalla de horarios para mostrar citas, la tabla extensa de cupos y los formularios duplicados.
- El panel calcula sus métricas de citas con una sola agregación. El directorio ya no ejecuta conteos correlacionados por cada jornada para calcular cupos.
- Se retiraron el buscador antiguo del hero y sus estilos sin uso, los iconos de búsqueda y cuadrícula que ya no se utilizaban, el formateador de rangos duplicado y las pruebas antiguas de cupos. Los clientes HTTP de pruebas comparten un mismo archivo.
- El calendario cancela peticiones superadas por filtros más recientes, bloquea reservas mientras actualiza y ofrece reintento ante fallos de red. Las confirmaciones mantienen el texto correcto al volver con el navegador.

Los iconos, recursos y scripts del sitio son locales. El calendario no depende de una librería externa ni se conecta a Google Calendar. Los estilos y JavaScript del calendario usan archivos propios; los formularios de selección de día y hora también funcionan sin JavaScript.

## Pruebas

La revisión funcional incluye pruebas de permisos, privacidad de la API, reservas, cambios, cancelaciones, horarios inválidos y solicitudes simultáneas a dos servidores PHP. También se simula el DOM para comprobar cambio de especialidad y médico, selección de día y hora, vistas, confirmación real, reintento de red y filtros rápidos.

La sesión no ofrece un navegador controlable. Las pruebas del DOM no acreditan la apariencia final ni sustituyen la revisión visual en escritorio y móvil.

Resultados de la ejecución: 66 comprobaciones de reglas y concurrencia, 109 de regresión, 31 del catálogo, 28 de búsquedas y 28 de interacción en el DOM. También se verificaron sintaxis PHP y JavaScript, conservación de registros, integridad y restauración del respaldo. Las pruebas finales incluyeron una instalación nueva con el esquema actualizado.

Preparación en una base independiente, con MariaDB iniciado:

```powershell
$env:EDOC_DB_NAME='edoc_calendar_check_20261016'
runtime\php\php.exe -c .local\php.ini tools\setup.php
```

Iniciar dos servidores en terminales separadas con esa misma variable de base:

```powershell
runtime\php\php.exe -c .local\php.ini -S 127.0.0.1:8089 -t . router.php
runtime\php\php.exe -c .local\php.ini -S 127.0.0.1:8090 -t . router.php
```

Comprobaciones funcionales:

```powershell
$env:EDOC_TEST_URL='http://127.0.0.1:8089'
$env:EDOC_TEST_URL_SECOND='http://127.0.0.1:8090'
runtime\php\php.exe -c .local\php.ini tests\calendar-test.php
runtime\php\php.exe -c .local\php.ini tests\dashboard-test.php
runtime\php\php.exe -c .local\php.ini tests\catalog-test.php
runtime\php\php.exe -c .local\php.ini tests\catalog-ui-test.php
```

Pruebas de interacción con Node.js y [jsdom](https://github.com/jsdom/jsdom), instalado únicamente para pruebas en `.local/`:

```powershell
npm.cmd install --prefix .local/calendar-dom --no-save --package-lock=false jsdom
runtime\php\php.exe -c .local\php.ini tests\calendar-dom-fixture.php
node tests\calendar-dom-test.cjs
runtime\php\php.exe -c .local\php.ini tests\calendar-dom-fixture.php --clean
```

Los registros están en `.local/calendar-tests.txt`, `.local/calendar-dashboard-tests.txt`, `.local/calendar-catalog-tests.txt`, `.local/calendar-catalog-ui-tests.txt`, `.local/calendar-dom-tests.txt` y `.local/calendar-migration-tests.txt`.
