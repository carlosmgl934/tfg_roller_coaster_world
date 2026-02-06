CREATE TABLE IF NOT EXISTS park_ratings(

    -- Datos del usuario que valora --  
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,

    -- Datos del parque que se valora --  
    park_id INT NOT NULL,
    review TEXT DEFAULT NULL,
    note DECIMAL(3,2) NOT NULL,  --nota decimal de 0 a 10 con estrellas

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE
);
     