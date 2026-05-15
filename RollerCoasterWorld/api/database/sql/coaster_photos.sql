CREATE TABLE IF NOT EXISTS coaster_photos (
    id SERIAL PRIMARY KEY,
    coaster_id INT NOT NULL,
    user_id INT NOT NULL,
    photo_url VARCHAR(500) NOT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    likes INT DEFAULT 0,
    status VARCHAR(10) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coaster_id) REFERENCES coasters(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS coaster_photo_likes (
    photo_id INT NOT NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (photo_id, user_id),
    FOREIGN KEY (photo_id) REFERENCES coaster_photos(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
