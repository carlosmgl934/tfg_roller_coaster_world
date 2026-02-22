CREATE TABLE IF NOT EXISTS parks (
    id SERIAL PRIMARY KEY,
    park_name VARCHAR(255) NOT NULL,
    park_location VARCHAR(255) NOT NULL,
    park_country VARCHAR(250) DEFAULT NULL,
    imagen_url VARCHAR(255) DEFAULT NULL,
    num_coasters INT DEFAULT 0,
    operating_coasters INT DEFAULT 0,
    opening_year INT DEFAULT NULL,
    precio_entrada NUMERIC(5,2) DEFAULT NULL,
    stars NUMERIC(3,2) DEFAULT 0,
    latitude NUMERIC(10,8) DEFAULT NULL,
    longitude NUMERIC(11,8) DEFAULT NULL
);
