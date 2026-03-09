CREATE TABLE IF NOT EXISTS coaster_photos (
    id SERIAL PRIMARY KEY,
    coaster_id INT NOT NULL,
    user_id INT NOT NULL,
    photo_url VARCHAR(500) NOT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    likes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coaster_id) REFERENCES coasters(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);