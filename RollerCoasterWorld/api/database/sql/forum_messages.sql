CREATE TABLE IF NOT EXISTS forum_messages (
    id             SERIAL PRIMARY KEY,
    -- Datos del mensaje --
    forum_id       INT          NOT NULL,
    user_id        INT          NOT NULL,
    content        TEXT         NOT NULL,
    reply_to_id    INT          DEFAULT NULL,   -- id del mensaje al que responde
    attachment_url VARCHAR(500) DEFAULT NULL,   -- URL pública en Supabase Storage
    file_name      VARCHAR(255) DEFAULT NULL,   -- nombre original del archivo adjunto
    is_hidden      BOOLEAN      DEFAULT FALSE,  -- true = owner/admin lo ha ocultado
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (forum_id)    REFERENCES forums(id)          ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)           ON DELETE CASCADE,
    FOREIGN KEY (reply_to_id) REFERENCES forum_messages(id)  ON DELETE SET NULL
);