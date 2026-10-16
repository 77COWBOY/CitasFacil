# Especialidades y médicos de demostración

Las 56 especialidades originales están en español en el esquema inicial y en la base activa. La migración `database/migrations/20261016_specialties_es.sql` conserva sus identificadores y solo traduce los nombres originales conocidos; no reemplaza nombres personalizados.

Se agregaron 16 médicos ficticios, para un total de 17 en esta instalación. Cada médico nuevo tiene seis jornadas próximas de tres horas y consultas de 30 minutos. La primera carga agregó 96 jornadas del 19 al 30 de octubre de 2026. La [agenda por horas](AGENDA.md) genera las horas de atención a partir de cada jornada y no utiliza cupos. No se generaron pacientes ni reservas ficticias.

| Médico de demostración | Especialidad |
| --- | --- |
| Dra. Ana Martínez | Medicina general |
| Dr. Carlos Mendoza | Cardiología |
| Dra. Sofía Ramírez | Pediatría |
| Dra. Valeria López | Dermatología |
| Dra. Elena Torres | Ginecología y obstetricia |
| Dr. Diego Herrera | Oftalmología |
| Dr. Andrés Castro | Ortopedia |
| Dra. Lucía Morales | Neurología |
| Dr. Gabriel Reyes | Gastroenterología |
| Dra. Paula Navarro | Endocrinología |
| Dr. Ricardo Silva | Otorrinolaringología |
| Dra. Daniela Ortiz | Medicina interna |
| Dr. Fernando Vega | Urología |
| Dra. Isabel Rojas | Geriatría |
| Dr. Miguel Santos | Estomatología |
| Dra. Camila Flores | Psiquiatría |

El directorio muestra las jornadas con horas libres y la siguiente hora reservable. La búsqueda funciona en español, con o sin tildes. El directorio identifica los médicos ficticios con la etiqueta Demostración. Sus correos terminan en `@demo.example.invalid` y los teléfonos son de ejemplo.

## Cargar o renovar los horarios

La primera instalación carga los datos automáticamente. Reiniciar una instalación existente no recrea los médicos eliminados ni modifica sus horarios.

Para cargar la demostración o agregar jornadas futuras cuando hayan pasado las anteriores, ejecutar desde la raíz del proyecto con la base de datos iniciada:

```powershell
runtime\php\php.exe -c .local\php.ini tools\setup.php
runtime\php\php.exe -c .local\php.ini tools\seed-demo.php
```

La carga utiliza los próximos diez días laborables según America/Managua y publica seis jornadas por médico. Conserva los registros existentes, evita rangos superpuestos y no duplica médicos, cuentas ni horarios al repetirla el mismo día. Tampoco reemplaza cambios de nombre, especialidad o contraseña hechos por administración. Las cuentas nuevas reciben contraseñas aleatorias protegidas; el administrador puede establecer una contraseña desde Editar médico si necesita acceder con ellas.

## Verificación de esta ejecución

Se crearon respaldos antes y después del cambio. Se compararon todos los registros anteriores de administradores, médicos, pacientes, horarios, citas y usuarios, sin modificaciones. La integridad y la restauración del respaldo se verificaron en siete tablas.

Las comprobaciones del catálogo se actualizaron para la agenda por horas. Verifican la repetición de la carga sin duplicados, la protección de cambios administrativos, las fechas futuras, los rangos y la duración de las consultas. Los resultados de la revisión actual se detallan en [AGENDA.md](AGENDA.md).
