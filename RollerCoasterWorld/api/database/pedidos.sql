CREATE TABLE IF NOT EXISTS pedidos(

    -- Datos del pedido --
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,  --usuario que realiza el pedido
    park_id INT NOT NULL,  --parque del que se compran las entradas
    quantity INT NOT NULL, --cantidad de entradas
    price DECIMAL(5,2) DEFAULT NULL,
    status ENUM('pendiente', 'completado') DEFAULT 'pendiente',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id) ON DELETE CASCADE
);

