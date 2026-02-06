CREATE TABLE IF NOT EXISTS user_credits(

    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Datos de User y Coasters --
    user_id INT NOT NULL,
    coaster_id INT NOT NULL,
    rank_position INT NOT NULL,  --cada coaster ocupa una posición


    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coaster_id) REFERENCES coasters(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_ranking (user_id, rank_position) 
);