# Flujo de trabajo del proyecto

La entrega inicial se prepara desde `main`. Para las siguientes tareas, el flujo definido es:

1. Cada integrante mantiene su propio clon del repositorio compartido.
2. Antes de trabajar, actualiza `main` y crea una rama para una tarea concreta.
3. Implementa y comprueba el cambio; realiza commits descriptivos.
4. Publica la rama y abre un pull request a `main`.
5. Otro integrante revisa el código y comprueba el comportamiento afectado.
6. Tras la revisión, se integra el cambio y los demás actualizan sus clones.

```powershell
git switch main
git pull --ff-only origin main
git switch -c feature/nombre-de-tarea
# Realizar el cambio y comprobarlo.
git add ARCHIVOS_MODIFICADOS
git commit -m "Descripción concreta del cambio"
git push -u origin feature/nombre-de-tarea
```

Usar `fix/descripcion` para correcciones. La base de datos activa, las sesiones y los respaldos permanecen en `.local/`. Los cambios del esquema se comparten como SQL versionado. Los ejecutables portátiles se distribuyen por separado.

## Comprobar cambios

En una copia limpia de pruebas, con la aplicación iniciada:

```powershell
runtime\php\php.exe -c .local\php.ini tests\dashboard-test.php
runtime\php\php.exe -c .local\php.ini tools\backup.php
runtime\php\php.exe -c .local\php.ini tests\database-test.php
```

`dashboard-test.php` también ejecuta `adaptation-test.php` y `smoke-test.php`: son 103 comprobaciones en total. Las pruebas crean y eliminan registros de ejemplo, por lo que deben realizarse sobre una base de pruebas. La prueba de base de datos necesita un respaldo recién generado y verifica su restauración en una base temporal.

## Evidencia del clon de cada integrante

Después de clonar desde GitHub, cada integrante puede ejecutar:

```powershell
git remote -v
git status
git log -1 --oneline
```

Debe guardar una captura de esos comandos ejecutados en su propio equipo, identificada con su nombre. Los clones de otra persona o varias carpetas en un solo equipo no prueban este requisito.
