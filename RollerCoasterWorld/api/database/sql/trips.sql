CREATE TABLE IF NOT EXISTS trips (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    trip_type VARCHAR(20) DEFAULT 'manual',   -- 'manual' | 'ai'
    status VARCHAR(20) DEFAULT 'planned',     -- 'planned' | 'active' | 'completed'
    parks_visited VARCHAR(255) DEFAULT NULL,
    new_credits INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
