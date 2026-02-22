CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT NOT NULL,
<<<<<<< HEAD
    park_id INT DEFAULT NULL,  --parque recomendado
    coaster_id INT DEFAULT NULL,  --coaster recomendada

    affinity_score DECIMAL(5,4),  --coincidencia
    reason VARCHAR(255),  --para darle un motivo al usuario
     
=======
    park_id INT DEFAULT NULL,
    coaster_id INT DEFAULT NULL,
    affinity_score NUMERIC(5,4),
    reason VARCHAR(255),
    PRIMARY KEY (user_id, park_id, coaster_id),
>>>>>>> dda9d06f1d439f23db3a19f583bc468c76af90c1
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE,
    FOREIGN KEY (coaster_id) REFERENCES coasters(id) ON DELETE CASCADE
);