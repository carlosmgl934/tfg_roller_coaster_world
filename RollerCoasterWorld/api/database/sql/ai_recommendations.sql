-- ================================================================
-- ai_recommendations.sql — Caché de recomendaciones IA
-- Se regenera cada 24h o cuando el perfil cambia.
-- ================================================================

CREATE TABLE IF NOT EXISTS ai_recommendations (
    id                SERIAL PRIMARY KEY,
    user_id           INT NOT NULL,
    park_id           INT NOT NULL,
    park_name         VARCHAR(255) NOT NULL,
    park_country      VARCHAR(255) DEFAULT NULL,
    park_image_url    VARCHAR(500) DEFAULT NULL,
    price_estimate    NUMERIC(7,2) DEFAULT NULL,
    hotel_name        VARCHAR(255) DEFAULT NULL,
    hotel_stars       SMALLINT DEFAULT 3,
    hotel_price_night NUMERIC(7,2) DEFAULT NULL,
    duration_days     SMALLINT DEFAULT 2,
    affinity_score    NUMERIC(5,4) DEFAULT 0,
    reason            TEXT NOT NULL,
    rec_type          VARCHAR(10) CHECK (rec_type IN ('match','wildcard')) DEFAULT 'match',
    generated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at        TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL '24 hours'),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_ai_recs_user ON ai_recommendations(user_id, expires_at);
