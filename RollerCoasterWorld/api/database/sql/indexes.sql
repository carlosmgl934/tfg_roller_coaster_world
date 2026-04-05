-- indexes.sql
-- ─────────────────────────────────────────────────────────────────────────────
-- Índices de rendimiento para las tablas principales.
-- Mejoran la velocidad en JOINs frecuentes y búsquedas filtradas.
-- Se pueden recrear en cualquier momento sin pérdida de datos.
-- ─────────────────────────────────────────────────────────────────────────────

-- Coasters: búsqueda por parque
CREATE INDEX IF NOT EXISTS idx_coasters_park_id ON coasters(park_id);

-- Ratings de coasters: búsqueda por coaster
CREATE INDEX IF NOT EXISTS idx_coaster_ratings_coaster_id ON coaster_ratings(coaster_id);

-- Ratings de parques: búsqueda por parque
CREATE INDEX IF NOT EXISTS idx_park_ratings_park_id ON park_ratings(park_id);
