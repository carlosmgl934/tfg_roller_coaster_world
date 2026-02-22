CREATE TABLE IF NOT EXISTS forum_collaborators (
    id SERIAL PRIMARY KEY,
    forum_id INT NOT NULL,
    user_id INT NOT NULL,   -- si estás en esta tabla, tienes permiso de escribir en el foro
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (forum_id) REFERENCES forums(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);