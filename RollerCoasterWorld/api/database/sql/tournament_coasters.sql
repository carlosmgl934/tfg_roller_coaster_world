CREATE TABLE IF NOT EXISTS tournament_coasters (
    id SERIAL PRIMARY KEY,
    coaster_id INT NOT NULL REFERENCES coasters(id) ON DELETE CASCADE,
    group_letter CHAR(1) DEFAULT NULL CHECK (group_letter IN ('A','B','C','D','E','F','G','H')),
    country VARCHAR(250) DEFAULT NULL,      -- Cache del país para restricción de sorteo
    points INT DEFAULT 0,
    votes_for INT DEFAULT 0,
    votes_against INT DEFAULT 0,
    matches_played INT DEFAULT 0,
    wins INT DEFAULT 0,
    draws INT DEFAULT 0,
    losses INT DEFAULT 0,
    eliminated_at VARCHAR(20) DEFAULT NULL,
    final_position INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(coaster_id)
);
