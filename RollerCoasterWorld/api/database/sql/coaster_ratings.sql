CREATE TABLE IF NOT EXISTS coaster_ratings (
    id SERIAL PRIMARY KEY,
    -- Datos del usuario que valora -- 
    user_id INT NOT NULL,
    -- Datos de la coaster que se valora -- 
    coaster_id INT NOT NULL,
    review TEXT DEFAULT NULL,
    note NUMERIC(3,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coaster_id) REFERENCES coasters(id) ON DELETE CASCADE
);