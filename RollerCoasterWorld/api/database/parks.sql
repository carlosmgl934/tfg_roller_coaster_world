CREATE TABLE IF NOT EXISTS parks(

    -- Datos del parque NO estadísticos --
    id INT AUTO_INCREMENT PRIMARY KEY,
    park_name VARCHAR(255) NOT NULL,
    park_location VARCHAR(255) NOT NULL,
    park_country VARCHAR(250) DEFAULT NULL,
    imagen_url VARCHAR(255) DEFAULT NULL,

    -- Estadísticas --
    num_coasters INT DEFAULT 0,
    operating_coasters INT DEFAULT 0,
    opening_year INT DEFAULT NULL,
    precio_entrada DECIMAL(5,2) DEFAULT NULL,

    -- Valoración --
    stars DECIMAL(3,2) DEFAULT 0,

    -- Mapa --
    latitude DECIMAL(10,8) DEFAULT NULL,
    longitude DECIMAL(11,8) DEFAULT NULL
);
