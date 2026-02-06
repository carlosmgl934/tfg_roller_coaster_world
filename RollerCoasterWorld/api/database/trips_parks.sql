CREATE TABLE IF NOT EXISTS trip_parks(

    trip_id INT NOT NULL,
    park_id INT NOT NULL,
    visit_order INT NOT NULL DEFAULT 1,  --orden de visita de parques
    visit_date DATE DEFAULT NULL,  --fecha de visita al parque

    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE
);