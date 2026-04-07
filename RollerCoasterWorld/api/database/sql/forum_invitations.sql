-- Tabla de invitaciones de colaboración en foros privados
CREATE TABLE IF NOT EXISTS forum_invitations (
    id          SERIAL PRIMARY KEY,
    forum_id    INT          NOT NULL REFERENCES forums(id)  ON DELETE CASCADE,
    sender_id   INT          NOT NULL REFERENCES users(id)   ON DELETE CASCADE,
    receiver_id INT          NOT NULL REFERENCES users(id)   ON DELETE CASCADE,
    status      VARCHAR(20)  NOT NULL DEFAULT 'pending'
                CHECK (status IN ('pending', 'accepted', 'declined')),
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (forum_id, receiver_id)
);

CREATE INDEX IF NOT EXISTS idx_forum_invitations_receiver ON forum_invitations(receiver_id, status);
