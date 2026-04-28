CREATE TABLE IF NOT EXISTS pedidos (
    id          SERIAL PRIMARY KEY,

    -- Relaciones
    user_id     INT            NOT NULL,
    park_id     INT            NOT NULL,

    -- Detalle de la compra
    ticket_type VARCHAR(20)    NOT NULL DEFAULT 'entrada'
                    CHECK (ticket_type IN ('entrada', 'pase_rapido')),
    visit_date  DATE           NOT NULL,
    quantity    INT            NOT NULL CHECK (quantity > 0),
    unit_price  NUMERIC(8,2)  NOT NULL,           -- precio unitario en el momento de compra
    price       NUMERIC(8,2)  NOT NULL,           -- total = unit_price × quantity

    -- Estado del pedido
    status      VARCHAR(20)    NOT NULL DEFAULT 'pendiente'
                    CHECK (status IN ('pendiente', 'confirmado', 'cancelado')),

    -- Timestamps
    created_at  TIMESTAMPTZ   DEFAULT NOW(),
    updated_at  TIMESTAMPTZ   DEFAULT NOW(),

    -- Claves foráneas
    FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id)  ON DELETE CASCADE
);

-- Índices de rendimiento
CREATE INDEX IF NOT EXISTS idx_pedidos_user_id   ON pedidos(user_id);
CREATE INDEX IF NOT EXISTS idx_pedidos_park_id   ON pedidos(park_id);
CREATE INDEX IF NOT EXISTS idx_pedidos_status    ON pedidos(status);
CREATE INDEX IF NOT EXISTS idx_pedidos_visit_date ON pedidos(visit_date);
