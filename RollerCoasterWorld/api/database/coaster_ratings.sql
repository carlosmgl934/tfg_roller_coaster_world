CREATE TABLE IF NOT EXISTS coaster_ratings(

    -- Datos del usuario que valora --  
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,

    -- Datos de la coaster que se valora --  
    coaster_id INT NOT NULL,
    review TEXT DEFAULT NULL,
    note DECIMAL(3,2) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coaster_id) REFERENCES coasters(id) ON DELETE CASCADE
);