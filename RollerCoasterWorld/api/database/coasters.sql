CREATE TABLE IF NOT EXISTS coasters(

    -- Datos de la coaster NO estadísticos --
    id INT AUTO_INCREMENT PRIMARY KEY,
    coaster_name VARCHAR(255) NOT NULL,
    park_id INT NOT NULL,
    coaster_manufacter VARCHAR(255) DEFAULT NULL,
    coaster_model VARCHAR(255) DEFAULT NULL, 
    imagen_url VARCHAR(255) DEFAULT NULL,

    -- Estadísticas --
    height DECIMAL(5,2) DEFAULT NULL,
    speed DECIMAL(5,2) DEFAULT NULL,
    coaster_length DECIMAL(6,2) DEFAULT NULL,
    inversions INT DEFAULT 0,
    opening_year INT DEFAULT NULL,

    -- Valoración --
    stars DECIMAL(3,2) DEFAULT 0,

    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE
);
     