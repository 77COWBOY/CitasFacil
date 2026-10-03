# Verificación de la distribución y Git

Realizada el 2 de octubre de 2026 en este equipo.

## Repositorio local

Se incorporó el historial del remoto `https://github.com/77COWBOY/CitasFacil.git` y se preparó el commit de implementación `710da58`. Se creó un clon de control desde ese repositorio local con `git clone --no-hardlinks`, se configuró su remoto como la URL de GitHub y se comprobó que no tenía cambios pendientes.

En la primera comprobación, el clon de control se obtuvo desde la carpeta local porque la publicación todavía requería autenticación.

Después de autenticar GitHub, se publicaron los commits de la entrega y se comprobó que el remoto `main` apuntara a `d256530fc264aca3b6548ee0a31603574785f706`. Se creó además un clon nuevo directamente desde `https://github.com/77COWBOY/CitasFacil.git`: su remoto corresponde a ese repositorio, su commit coincide y no presenta cambios locales pendientes. Esta comprobación acredita el clon realizado en este equipo; no se presenta como evidencia de otro integrante.

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
