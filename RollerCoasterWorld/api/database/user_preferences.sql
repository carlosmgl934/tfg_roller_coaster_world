CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT NOT NULL,
    park_id INT DEFAULT NULL,
    coaster_id INT DEFAULT NULL,
    affinity_score NUMERIC(5,4),
    reason VARCHAR(255),
    PRIMARY KEY (user_id, park_id, coaster_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE,
    FOREIGN KEY (coaster_id) REFERENCES coasters(id) ON DELETE CASCADE
);