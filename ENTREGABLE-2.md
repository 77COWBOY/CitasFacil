# Segundo entregable: implementación inicial

## Estado de la entrega

| Requisito | Evidencia disponible | Pendiente |
| --- | --- | --- |
| Software de aplicación | Código de CitasFacil/eDoc ejecutado en una instalación limpia el 2 de octubre de 2026 | Presentar las evidencias adjuntas |
| Repositorio compartido en GitHub | [77COWBOY/CitasFacil](https://github.com/77COWBOY/CitasFacil), código publicado en `main` y clon desde GitHub comprobado | Confirmar accesos de los demás integrantes |
| Clones locales de todos los integrantes | Sin evidencia aportada | Captura o registro de cada integrante |
| Descripción del flujo de trabajo | Flujo definido en `README.md` y `CONTRIBUTING.md` | Acreditar participación y revisiones reales del equipo |
| Demo funcional o video | Video de 2:06 y 14 capturas reales publicados en `evidencias/` | Presentar las evidencias adjuntas |
| Coherencia con el primer diseño de UI | No se dispone del primer entregable | Comparar pantallas y justificar cambios, si existen |

## Datos por completar

- Repositorio de GitHub: https://github.com/77COWBOY/CitasFacil.
- Distribución portátil: [Release del segundo entregable](https://github.com/77COWBOY/CitasFacil/releases/tag/entregable-2-2026-10-02).
- Integrantes y usuarios de GitHub: pendiente.
- Video: [evidencias/demo-citasfacil.mp4](evidencias/demo-citasfacil.mp4). Recorrido con capturas reales, sin audio; no es una grabación continua de pantalla.
- Referencia al diseño del primer entregable: pendiente.

## Configuración del repositorio

Esta carpeta está conectada al remoto `origin` indicado y la rama local `main` sigue a `origin/main`. Se conservó el contenido local al incorporar el historial remoto. El código y las evidencias se publicaron en `main`; se verificó el commit remoto y se obtuvo un clon nuevo directamente desde GitHub.

1. Confirmar a todos los integrantes como colaboradores del repositorio existente.
2. Mantener el código publicado y revisar los siguientes cambios mediante el flujo descrito.
3. Versionar el código, los recursos, el esquema SQL, las instrucciones y las evidencias. Mantener `.local/` fuera de Git: contiene bases activas, sesiones y respaldos.
4. Distribuir `runtime/` mediante el paquete `CitasFacil-Entregable2-Windows-x64.zip`, fuera del historial Git.
5. Comprobar el acceso de los integrantes a la rama principal publicada.
6. Cada integrante debe clonar el repositorio en su equipo y ejecutar la aplicación siguiendo el README.

Comandos para cada integrante:

```powershell
git clone https://github.com/77COWBOY/CitasFacil.git
cd CitasFacil
git remote -v
git status
```

Guardar una evidencia por integrante que muestre su clon y el remoto compartido. Tener una copia descargada como ZIP no acredita un clon de Git.

## Guion sugerido de demo (5–7 minutos)

La demo ya se ejecutó en una instalación independiente con datos ficticios. Se inició mediante `Iniciar-eDoc.cmd -Port 8081 -NoBrowser`. El recorrido se documenta en [evidencias/README.md](evidencias/README.md), con video y capturas originales. Mostró los tres perfiles, publicación de un horario con 2 cupos, reserva (quedó 1 cupo), persistencia después de volver a iniciar sesión, cancelación y recuperación del cupo (volvieron a quedar 2).

También pasaron 103 comprobaciones funcionales, la sintaxis de 32 archivos PHP, la integridad de tablas y el rollback, y la restauración del respaldo con conteos y hashes coincidentes en 7 tablas. Los registros se adjuntan en `evidencias/`.

Para una presentación en vivo más extensa, se puede seguir este guion:

1. **Presentación:** explicar que el sistema permite consultar horarios y gestionar citas médicas.
2. **Arranque:** ejecutar `Iniciar-eDoc.cmd` y mostrar la portada en el navegador.
3. **Administrador:** iniciar sesión, mostrar el panel y crear un médico de demostración.
4. **Médico:** ingresar con la cuenta del médico y publicar un horario futuro con cupos disponibles.
5. **Paciente:** iniciar sesión, buscar el horario, reservar una cita y mostrarla en el listado.
6. **Persistencia:** cerrar sesión y volver a ingresar para comprobar que la cita sigue registrada.
7. **Cancelación:** cancelar la cita y mostrar el resultado.
8. **Repositorio:** mostrar el código en GitHub, colaboradores, ramas o pull requests y evidencias de los clones locales.

Usar datos ficticios y una copia de demostración para los cambios del guion. Probar el recorrido antes de grabarlo. Una prueba escrita complementa la demo, pero no sustituye la evidencia de ejecución solicitada.

## Comparación de UI

Agregar imágenes de las pantallas equivalentes del primer diseño y de la implementación. Para cada cambio significativo, describir qué cambió y su motivo real. No declarar coherencia ni inventar justificaciones sin revisar el primer entregable.

## Lista final

- [x] Repositorio de GitHub accesible y clon desde GitHub comprobado.
- [x] Archivos fuente y pasos de ejecución completos.
- [ ] Todos los integrantes agregados como colaboradores.
- [ ] Evidencia de un clon local por integrante.
- [x] Flujo de trabajo definido y descrito.
- [x] Video y capturas de la demo publicados junto al código.
- [x] Publicación del código en GitHub verificada.
- [ ] Comparación con el diseño inicial y justificaciones necesarias.
- [ ] Datos del equipo y enlaces completados.
