# Evidencias del segundo entregable

Fecha de ejecución: **2 de octubre de 2026**, zona horaria America/Managua.

## Ejecución real

La aplicación se inició en Windows desde una copia limpia, sin una base previa, usando `Iniciar-eDoc.cmd -Port 8081 -NoBrowser`. El lanzador inicializó MariaDB y preparó la base de ejemplo. La prueba utilizó los ejecutables portátiles del proyecto y una base independiente de los datos de la carpeta principal.

Se ejecutó el recorrido de demo en el navegador: acceso de administrador, consulta de médicos, acceso del médico, publicación de una consulta con 2 cupos, acceso del paciente, reserva, consulta de la cita, cierre y reapertura de sesión, cancelación y comprobación del cupo recuperado.

- [Video de demostración](demo-citasfacil.mp4): 2 minutos y 6 segundos, sin audio. Es una secuencia rotulada de capturas reales tomadas durante las operaciones, no una grabación continua de pantalla.
- [Presentación navegable](index.html): abrir localmente en el navegador.
- [Capturas originales](capturas/): 14 imágenes sin modificar.
- [Índice de la secuencia](demo-secuencia.json): correspondencia entre imágenes y operaciones.

## Verificación técnica

| Comprobación | Resultado | Evidencia |
| --- | --- | --- |
| Funciones y permisos por perfil | 103 comprobaciones PASS | [pruebas-funcionales.txt](pruebas-funcionales.txt) |
| Sintaxis PHP | 32 archivos correctos | [pruebas-sintaxis.txt](pruebas-sintaxis.txt) |
| Integridad de tablas y rollback | PASS | [pruebas-base-datos.txt](pruebas-base-datos.txt) |
| Restauración del respaldo | Conteos y hashes coinciden en 7 tablas | [pruebas-base-datos.txt](pruebas-base-datos.txt) |
| Paquete portátil | Extracción, arranque desde cero, HTTP 200 y cierre correctos | [verificacion-distribucion.md](verificacion-distribucion.md) |

Los registros de sintaxis se muestran con rutas relativas para facilitar su lectura. No se publican bases activas, sesiones ni respaldos personales.

## Evidencia del equipo

Faltan los nombres y usuarios de los integrantes, la confirmación de colaboradores en GitHub y una evidencia del clon ejecutada por cada integrante en su propio equipo. Las capturas de la aplicación no sustituyen esas evidencias. El documento [CONTRIBUTING.md](../CONTRIBUTING.md) explica cómo obtenerlas.

## Diseño inicial

El diseño del primer entregable no fue aportado. Estas capturas documentan la interfaz implementada; la comparación con el diseño inicial queda pendiente de revisar esa referencia.
