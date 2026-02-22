CREATE TABLE IF NOT EXISTS trips(

    -- Datos del viaje --
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,          --usuario que realiza el viaje
    title VARCHAR(100) NOT NULL,   --nombre del viaje (a los frikiparques nos encanta bautizar los trips)
    start_date DATE NOT NULL,      --fecha de inicio
    end_date DATE NOT NULL,        --fecha de final

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    parks_visited VARCHAR(255) DEFAULT NULL,  --nuevos parques que visita
    new_credits INT(100) NOT NULL,  --nuevas montañas rusas que prueba
   
   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
