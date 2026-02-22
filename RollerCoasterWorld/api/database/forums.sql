CREATE TABLE IF NOT EXISTS forums (
    id SERIAL PRIMARY KEY,
    -- Datos del foro --
    title VARCHAR(100) NOT NULL,
    forum_subject VARCHAR(500) NOT NULL,
    author_id INT NOT NULL,
    privacy VARCHAR(10) CHECK (privacy IN ('public', 'private')) DEFAULT 'public', -- depende de la privacidad se podrá sólo leer o también participar
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);
