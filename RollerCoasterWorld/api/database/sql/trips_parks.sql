CREATE TABLE IF NOT EXISTS trip_parks (
    id SERIAL PRIMARY KEY,
    trip_id INT NOT NULL,
    park_id INT NOT NULL,
    visit_date DATE NOT NULL,
    visit_order INT NOT NULL DEFAULT 1,   -- 1=primer parque del día, 2=segundo
    notes TEXT DEFAULT NULL,
    UNIQUE (trip_id, visit_date, visit_order),
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE
);