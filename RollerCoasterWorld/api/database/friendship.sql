CREATE TABLE IF NOT EXISTS friendship(

    id INT AUTO_INCREMENT PRIMARY KEY,
    estado_solicitud ENUM('PENDIENTE', 'ACEPTADA') DEFAULT 'PENDIENTE',

    -- Persona solicitante --
    solicitante_id INT NOT NULL,
    
    -- Persona solicitada --
    solicitada_id INT NOT NULL,

    -- Antiguedad amistad --
    accepted_at TIMESTAMP NULL DEFAULT NULL,
   
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     
    FOREIGN KEY (solicitante_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (solicitada_id) REFERENCES users(id) ON DELETE CASCADE
);