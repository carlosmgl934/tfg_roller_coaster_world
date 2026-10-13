CREATE TABLE IF NOT EXISTS tournament_votes (
    id SERIAL PRIMARY KEY,
    match_id INT NOT NULL REFERENCES tournament_matches(id) ON DELETE CASCADE,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    voted_coaster_id INT NOT NULL REFERENCES coasters(id),
    voted_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(match_id, user_id)
);
