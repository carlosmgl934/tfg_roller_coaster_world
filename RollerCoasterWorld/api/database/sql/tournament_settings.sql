-- Tabla de configuración general del torneo (hero image, etc.)
CREATE TABLE IF NOT EXISTS tournament_settings (
    key   VARCHAR(100) PRIMARY KEY,
    value TEXT,
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Valor por defecto para la imagen del hero (vacío = sin foto)
INSERT INTO tournament_settings (key, value)
VALUES ('hero_image_url', '')
ON CONFLICT (key) DO NOTHING;
