-- Visitas sueltas a parques (sin viaje organizado) --
CREATE TABLE IF NOT EXISTS daily_visits (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    park_id INT NOT NULL,
    visit_date DATE NOT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, park_id, visit_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE
);
