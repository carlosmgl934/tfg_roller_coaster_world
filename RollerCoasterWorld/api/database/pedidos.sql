CREATE TABLE IF NOT EXISTS pedidos (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    park_id INT NOT NULL,
    quantity INT NOT NULL,
    price NUMERIC(5,2) DEFAULT NULL,
    status VARCHAR(20) CHECK (status IN ('pendiente', 'completado')) DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE
);

