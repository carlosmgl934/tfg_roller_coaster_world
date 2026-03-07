CREATE TABLE IF NOT EXISTS trip_parks (
    trip_id INT NOT NULL,
    park_id INT NOT NULL,
    visit_order INT NOT NULL DEFAULT 1,
    visit_date DATE DEFAULT NULL,
    PRIMARY KEY (trip_id, park_id),
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE
);