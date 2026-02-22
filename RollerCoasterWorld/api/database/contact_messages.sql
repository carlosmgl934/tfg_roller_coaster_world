CREATE TABLE IF NOT EXISTS contact_messages (
    id SERIAL PRIMARY KEY,
    user_id INT DEFAULT NULL,    -- usuario que nos contacta (si no esta logueado, sera NULL)
    user_name VARCHAR(100) NOT NULL,
    user_email VARCHAR(100) NOT NULL,
    subject VARCHAR(150) NOT NULL,  -- asunto del mensaje
    user_message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,     -- fecha de envio
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);