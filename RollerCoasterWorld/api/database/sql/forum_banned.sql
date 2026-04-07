CREATE TABLE IF NOT EXISTS forum_banned (
    id        SERIAL PRIMARY KEY,
    forum_id  INT NOT NULL REFERENCES forums(id) ON DELETE CASCADE,
    user_id   INT NOT NULL REFERENCES users(id)  ON DELETE CASCADE,
    banned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (forum_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_forum_banned_forum ON forum_banned(forum_id);
