CREATE TABLE IF NOT EXISTS users(

    -- Datos de login --
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(50) NOT NULL UNIQUE,
    firebase_uid VARCHAR(255) NOT NULL UNIQUE,
    rol ENUM('admin', 'user') DEFAULT 'user',

    -- Datos secundarios --
    city VARCHAR(100) DEFAULT NULL,
    birthdate DATE DEFAULT NULL,
    gender ENUM('male', 'female', 'other') DEFAULT NULL,
    home_park VARCHAR(255) DEFAULT NULL,
    user_credits INT DEFAULT 0,
    favorite_coaster VARCHAR(255) DEFAULT NULL,   
    profile_image VARCHAR(255) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP    
);
     