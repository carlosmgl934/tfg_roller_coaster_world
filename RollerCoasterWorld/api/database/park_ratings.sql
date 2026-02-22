CREATE TABLE IF NOT EXISTS park_ratings (
    id SERIAL PRIMARY KEY,
    -- Datos del usuario que valora --
    user_id INT NOT NULL,
    -- Datos del parque que se valora --
    park_id INT NOT NULL,
    review TEXT DEFAULT NULL,
    note NUMERIC(3,2) NOT NULL,  -- nota decimal de 0 a 10 con estrellas
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE
);
     