CREATE TABLE IF NOT EXISTS user_credits (
    id SERIAL PRIMARY KEY,
    -- Datos de User y Coasters --
    user_id INT NOT NULL,
    coaster_id INT NOT NULL,
    rank_position INT NOT NULL,  -- cada coaster ocupa una posición
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- fecha de último guardado del top
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coaster_id) REFERENCES coasters(id) ON DELETE CASCADE,
    UNIQUE (user_id, rank_position)
);