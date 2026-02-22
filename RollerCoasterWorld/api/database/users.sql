CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    -- Datos de login --
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(50) NOT NULL UNIQUE,
    firebase_uid VARCHAR(255) NOT NULL UNIQUE,
    -- Datos secundarios --
    rol VARCHAR(10) CHECK (rol IN ('admin', 'user')) DEFAULT 'user',
    city VARCHAR(100) DEFAULT NULL,
    birthdate DATE DEFAULT NULL,
    gender VARCHAR(10) CHECK (gender IN ('male', 'female', 'other')) DEFAULT NULL,
    home_park VARCHAR(255) DEFAULT NULL,
    user_credits INT DEFAULT 0,
    favorite_coaster VARCHAR(255) DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
); 