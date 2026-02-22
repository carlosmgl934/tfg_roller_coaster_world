CREATE TABLE IF NOT EXISTS trips (
    id SERIAL PRIMARY KEY,
    -- Datos del viaje --
    user_id INT NOT NULL,   -- usuario que realiza el viaje
    title VARCHAR(100) NOT NULL,    -- nombre del viaje (a los frikiparques nos encanta bautizar los trips)
    start_date DATE NOT NULL,    -- fecha de inicio
    end_date DATE NOT NULL,      -- fecha deL final
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    parks_visited VARCHAR(255) DEFAULT NULL,  --nuevos parques que visita
    new_credits INT DEFAULT 0, --nuevas montañas rusas que prueba
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
