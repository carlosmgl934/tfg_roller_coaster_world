-- Día de Visita al Parque en un Viaje --
CREATE TABLE IF NOT EXISTS ride_log(
    
    -- Datos del día --
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Vincular a un viaje en concreto (puede hacerse sobre un día suelto) --
    trip_id INT DEFAULT NULL,

    -- Por si vas a un parque fuera del viaje organizado --
    park_id INT NOT NULL,  --en que parque estás

    -- Detalles de la experiencia --
    coaster_id INT NOT NULL,  --en que coaster has montado
    user_id INT NOT NULL,
    ridden_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  --hora exacta
    seat_row INT DEFAULT NULL,  --fila en la que has montado
    first_time BOOLEAN DEFAULT TRUE,

    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE,
    FOREIGN KEY (coaster_id) REFERENCES coasters(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);