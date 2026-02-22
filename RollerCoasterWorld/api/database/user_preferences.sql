CREATE TABLE IF NOT EXISTS user_preferences(

    -- Datos del usuario que valora --  
    user_id INT NOT NULL,
    park_id INT DEFAULT NULL,  --parque recomendado
    coaster_id INT DEFAULT NULL,  --coaster recomendada

    affinity_score DECIMAL(5,4),  --coincidencia
    reason VARCHAR(255),  --para darle un motivo al usuario
     
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE
);