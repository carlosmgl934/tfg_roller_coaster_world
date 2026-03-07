CREATE TABLE IF NOT EXISTS coasters (
    id SERIAL PRIMARY KEY,
    -- Datos de la coaster NO estadísticos --
    rcdb_id INT DEFAULT NULL UNIQUE,
    rcdb_url VARCHAR(255) DEFAULT NULL,
    coaster_name VARCHAR(255) NOT NULL,
    park_id INT NOT NULL,
    coaster_manufacter VARCHAR(255) DEFAULT NULL,
    coaster_model VARCHAR(255) DEFAULT NULL,
    coaster_status VARCHAR(50) DEFAULT NULL,
    imagen_url VARCHAR(255) DEFAULT NULL,
    -- Estadísticas --
    height NUMERIC(5,2) DEFAULT NULL,
    speed NUMERIC(5,2) DEFAULT NULL,
    coaster_length NUMERIC(6,2) DEFAULT NULL,
    inversions INT DEFAULT 0,
    opening_year INT DEFAULT NULL,
    -- Valoración --
    stars NUMERIC(3,2) DEFAULT 0,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE
);
     