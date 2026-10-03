# Verificación de la distribución y Git

Realizada el 2 de octubre de 2026 en este equipo.

## Repositorio local

Se incorporó el historial del remoto `https://github.com/77COWBOY/CitasFacil.git` y se preparó el commit de implementación `710da58`. Se creó un clon de control desde ese repositorio local con `git clone --no-hardlinks`, se configuró su remoto como la URL de GitHub y se comprobó que no tenía cambios pendientes.

Esta comprobación verifica que el código versionado puede clonarse. El clon de control se obtuvo desde la carpeta local porque la publicación todavía requería autenticación; no se presenta como un clon de la nueva versión desde GitHub ni como evidencia de otro integrante.

## Paquete portátil

Se generó `CitasFacil-Entregable2-Windows-x64.zip` con los archivos versionados y la carpeta `runtime/`. Se comprobó la integridad del ZIP y la presencia de PHP, MariaDB, el lanzador y el video. Se verificó que no incluyera `.local/` ni `.git/`.

Luego se extrajo en otra carpeta y se ejecutó:

```text
Iniciar-eDoc.cmd -Port 8081 -NoBrowser
```

El lanzador creó una base de ejemplo desde cero y mostró:

```text
Creation of the database was successful
Base de datos importada.
eDoc listo.
eDoc listo: http://127.0.0.1:8081
```

La petición a `/login.php` respondió con **HTTP 200**. Se cerró usando `Detener-eDoc.cmd -Port 8081`; se generó el respaldo y MariaDB se detuvo correctamente.

El archivo `SHA256SUMS.txt` de la carpeta de entrega contiene el hash del ZIP final. La verificación se realizó en otra carpeta del mismo equipo, no en una segunda PC.
