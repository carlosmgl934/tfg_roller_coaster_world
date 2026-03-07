CREATE TABLE IF NOT EXISTS carrito (
    id SERIAL PRIMARY KEY,
    -- Datos del pedido --
    user_id INT NOT NULL,   -- usuario que realiza el pedido
    park_id INT NOT NULL,   -- parque del que se compran las entradas
    quantity INT NOT NULL,
    price NUMERIC(5,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deadline TIMESTAMP DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE
);