CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    -- Datos de login --
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(50) NOT NULL UNIQUE,
    firebase_uid VARCHAR(255) NOT NULL UNIQUE,
    -- Datos personales --
    full_name VARCHAR(100) DEFAULT NULL,
    birthdate DATE DEFAULT NULL,
    gender VARCHAR(15) CHECK (gender IN ('Masculino', 'Femenino', 'Otro')) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    -- Preferencias --
    home_park VARCHAR(255) DEFAULT NULL,
    favorite_coaster VARCHAR(255) DEFAULT NULL,
    -- Sistema --
    rol VARCHAR(10) CHECK (rol IN ('admin', 'user')) DEFAULT 'user',
    user_credits INT DEFAULT 0,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);