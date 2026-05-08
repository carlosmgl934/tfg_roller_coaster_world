-- Colaboradores de viajes (sistema de invitación tipo solicitud) --
CREATE TABLE IF NOT EXISTS trip_collaborators (
    id SERIAL PRIMARY KEY,
    trip_id INT NOT NULL,
    user_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',  -- 'pending' | 'accepted' | 'rejected'
    invited_by INT NOT NULL,               -- usuario que envió la invitación
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (trip_id, user_id),
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE
);
