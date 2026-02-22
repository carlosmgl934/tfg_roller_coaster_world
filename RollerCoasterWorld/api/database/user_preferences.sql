CREATE TABLE IF NOT EXISTS user_preferences (
    -- Datos del usuario que valora --  
    user_id INT NOT NULL,
    park_id INT DEFAULT NULL,    -- parque recomendado
    coaster_id INT DEFAULT NULL, -- coaster recomendada
    affinity_score NUMERIC(5,4), -- coincidencia
    reason VARCHAR(255), -- razón de la coincidencia (para darle motivos al usuario)
    PRIMARY KEY (user_id, park_id, coaster_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE,
    FOREIGN KEY (coaster_id) REFERENCES coasters(id) ON DELETE CASCADE
);