CREATE TABLE IF NOT EXISTS park_review_tags (
    id SERIAL PRIMARY KEY,
    review_id INT NOT NULL,
    tag VARCHAR(50) NOT NULL,
    type VARCHAR(3) CHECK (type IN ('pro', 'con')) NOT NULL,
    FOREIGN KEY (review_id) REFERENCES park_ratings(id) ON DELETE CASCADE
);
