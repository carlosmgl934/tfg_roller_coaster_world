CREATE TABLE IF NOT EXISTS user_park_credits (
    id SERIAL PRIMARY KEY,
    -- Datos de User y Parks --
    user_id INT NOT NULL,
    park_id INT NOT NULL,
    rank_position INT NOT NULL,  -- cada parque ocupa una posición
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE,
    UNIQUE (user_id, rank_position)
);
