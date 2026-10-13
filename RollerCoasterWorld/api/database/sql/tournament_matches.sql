CREATE TABLE IF NOT EXISTS tournament_matches (
    id SERIAL PRIMARY KEY,
    phase VARCHAR(20) NOT NULL CHECK (phase IN ('group','round16','quarter','semi','third','final')),
    group_letter CHAR(1) DEFAULT NULL,
    match_number INT NOT NULL,
    coaster1_id INT NOT NULL REFERENCES coasters(id),
    coaster2_id INT NOT NULL REFERENCES coasters(id),
    votes_coaster1 INT DEFAULT 0,
    votes_coaster2 INT DEFAULT 0,
    winner_id INT DEFAULT NULL REFERENCES coasters(id),
    is_draw BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending','active','finished')),
    starts_at TIMESTAMP DEFAULT NULL,
    ends_at TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);
