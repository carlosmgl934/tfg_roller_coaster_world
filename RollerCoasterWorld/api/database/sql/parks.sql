CREATE TABLE IF NOT EXISTS parks (
    id SERIAL PRIMARY KEY,
    -- Datos del parque NO estadísticos --
    park_name VARCHAR(255) NOT NULL,
    park_location VARCHAR(255) NOT NULL,
    park_country VARCHAR(250) DEFAULT NULL,
    imagen_url VARCHAR(255) DEFAULT NULL,
    -- Estadísticas --
    num_coasters INT DEFAULT 0,
    operating_coasters INT DEFAULT 0,
    opening_year INT DEFAULT NULL,
    precio_entrada NUMERIC(5,2) DEFAULT NULL,
    -- Valoración --
    stars NUMERIC(3,2) DEFAULT 0,
    -- Mapa --
    latitude NUMERIC(10,8) DEFAULT NULL,
    longitude NUMERIC(11,8) DEFAULT NULL,
    UNIQUE (park_name, park_location)
);
