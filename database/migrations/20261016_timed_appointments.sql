-- Conserva las columnas antiguas solo como datos históricos, sin usarlas para reservar.
ALTER TABLE schedule ADD COLUMN IF NOT EXISTS duration_minutes SMALLINT NOT NULL DEFAULT 30 AFTER endtime;
ALTER TABLE appointment ADD COLUMN IF NOT EXISTS starts_at DATETIME NULL;
ALTER TABLE appointment ADD COLUMN IF NOT EXISTS ends_at DATETIME NULL;
ALTER TABLE appointment ADD UNIQUE INDEX IF NOT EXISTS appointment_schedule_start (scheduleid,starts_at);
ALTER TABLE appointment ADD INDEX IF NOT EXISTS appointment_patient_end (pid,ends_at);
ALTER TABLE schedule ADD INDEX IF NOT EXISTS schedule_doctor_date (docid,scheduledate,scheduletime);
ALTER TABLE schedule ADD INDEX IF NOT EXISTS schedule_date (scheduledate,scheduletime);
