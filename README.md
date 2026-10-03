# CitasFacil / eDoc

Aplicación web para gestionar citas médicas con perfiles de administrador, médico y paciente. Implementación inicial para el segundo entregable de proyecto.

Repositorio compartido: [77COWBOY/CitasFacil](https://github.com/77COWBOY/CitasFacil).

## Funciones implementadas

- Registro e inicio de sesión de pacientes.
- Panel y navegación según el rol del usuario.
- Gestión de médicos desde la cuenta de administrador.
- Publicación y consulta de horarios médicos.
- Reserva y cancelación de citas, con control de cupos.
- Consulta de pacientes según permisos y actualización del perfil.
- Respaldo y restauración de la base de datos mediante herramientas locales.

## Tecnologías

PHP, MariaDB, HTML y CSS. La distribución portátil incluye PHP 8.2.12 y MariaDB en `runtime/`, con sus licencias. El esquema inicial está en `database/schema.sql`.

## Ejecutar en Windows

1. Clonar el repositorio o extraer la distribución completa en una carpeta con permisos de escritura.
2. Si se usa un clon de Git, copiar dentro de él la carpeta `runtime/` de la distribución portátil. El ZIP completo ya incluye esa carpeta. Los ejecutables y el ZIP no se versionan en Git.
3. Ejecutar `Iniciar-eDoc.cmd`.
4. Acceder a `http://127.0.0.1:8080`.
5. Al terminar, ejecutar `Detener-eDoc.cmd`.

Requisitos: Windows 10/11 de 64 bits, PowerShell 5.1 y un navegador moderno. La primera ejecución prepara la base de ejemplo. Los datos locales se guardan en `.local/`; cada clon tiene una base independiente.

| Perfil | Correo de ejemplo | Contraseña inicial |
| --- | --- | --- |
| Administrador | admin@edoc.com | 123 |
| Médico | doctor@edoc.com | 123 |
| Paciente | patient@edoc.com | 123 |

Consultar `LEEME.txt` para puertos alternativos, configuración externa y respaldos.

El paquete local de entrega se llama `CitasFacil-Entregable2-Windows-x64.zip`, contiene el proyecto completo con `runtime/` y las evidencias, y se acompaña de `SHA256SUMS.txt`. Se comprobó su extracción y ejecución desde cero en otra carpeta del mismo equipo. La publicación de la descarga requiere completar la autenticación en GitHub.

## Organización

| Ruta | Contenido |
| --- | --- |
| `includes/` | Lógica compartida, autenticación y conexión |
| `config/` | Configuración de la base de datos |
| `views/` | Plantillas de interfaz |
| `css/`, `img/` | Estilos y recursos visuales |
| `database/` | Esquema SQL inicial |
| `tools/` | Preparación, respaldo y restauración |
| `tests/` | Pruebas funcionales y de base de datos |
| `runtime/` | Ejecutables de la distribución portátil |
| `.local/` | Datos, sesiones y registros de cada instalación; excluidos de Git |

## Flujo de trabajo

La entrega inicial se prepara desde `main`. Para las siguientes tareas se define mantener `main` como versión demostrable y desarrollar cada tarea en una rama `feature/nombre-de-tarea` o `fix/nombre-del-error`. Cada integrante clona el repositorio compartido, realiza commits descriptivos y abre un pull request. Otro integrante revisa el cambio y comprueba el flujo afectado antes de integrarlo. Después de cada integración, el equipo actualiza sus clones con `git pull`. Los comandos y las comprobaciones están en [CONTRIBUTING.md](CONTRIBUTING.md).

Los cambios de base de datos se comparten como archivos SQL versionados; las bases activas y las sesiones de cada integrante permanecen locales.

## Verificación y evidencias

El 2 de octubre de 2026 se verificó una copia limpia de la aplicación: **103 comprobaciones funcionales correctas**, sintaxis correcta en **32 archivos PHP**, integridad y rollback de la base de datos, y restauración de un respaldo con conteos y hashes coincidentes en **7 tablas**. Los resultados están en [evidencias](evidencias/README.md).

La demo incluye [14 capturas reales](evidencias/capturas/) y un [video de 2 minutos y 6 segundos](evidencias/demo-citasfacil.mp4), presentado como recorrido con capturas de la ejecución, sin audio. Se usaron datos de ejemplo en una instalación independiente de pruebas.

`VERIFICACION.txt` conserva la verificación histórica del 26 de septiembre de 2026. No se han acreditado pruebas físicas en los equipos de los demás integrantes.

La lista de evidencias pendientes y el guion de demo se encuentran en `ENTREGABLE-2.md`.
