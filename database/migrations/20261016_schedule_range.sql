-- MariaDB. Se aplica automáticamente con tools/setup.php al iniciar.
-- NULL conserva la salida desconocida de los horarios existentes.
ALTER TABLE schedule ADD COLUMN IF NOT EXISTS endtime TIME NULL AFTER scheduletime;
