-- Registro de montadas en atracciones --
CREATE TABLE IF NOT EXISTS ride_log (
    id SERIAL PRIMARY KEY,
    trip_id INT DEFAULT NULL,           -- NULL = visita suelta sin viaje organizado
    park_id INT NOT NULL,
    coaster_id INT NOT NULL,
    user_id INT NOT NULL,
    visit_date DATE NOT NULL,           -- fecha del día de la visita
    ridden_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    seat_row INT DEFAULT NULL,
    first_time BOOLEAN DEFAULT TRUE,
    notes VARCHAR(200) DEFAULT NULL,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE SET NULL,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE,
    FOREIGN KEY (coaster_id) REFERENCES coasters(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);