CREATE TABLE IF NOT EXISTS friendship (
    id SERIAL PRIMARY KEY,
    estado_solicitud VARCHAR(20) CHECK (estado_solicitud IN ('PENDIENTE', 'ACEPTADA')) DEFAULT 'PENDIENTE',
    solicitante_id INT NOT NULL,
    solicitada_id INT NOT NULL,
    accepted_at TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (solicitante_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (solicitada_id) REFERENCES users(id) ON DELETE CASCADE
);