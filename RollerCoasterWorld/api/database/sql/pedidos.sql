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
    unit_price  NUMERIC(8,2)  NOT NULL,
    price       NUMERIC(8,2)  NOT NULL,

    -- Estado del pedido
    status      VARCHAR(50)    NOT NULL DEFAULT 'confirmado'
                    CHECK (status IN ('pendiente', 'confirmado', 'cancelado', 'solicitada_cancelacion')),

    -- Timestamps y Notificaciones
    reembolso_notificado BOOLEAN DEFAULT FALSE,
    created_at  TIMESTAMPTZ   DEFAULT NOW(),
    updated_at  TIMESTAMPTZ   DEFAULT NOW(),

    -- Claves foráneas
    FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (park_id) REFERENCES parks(id)  ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_pedidos_user_id    ON pedidos(user_id);
CREATE INDEX IF NOT EXISTS idx_pedidos_park_id    ON pedidos(park_id);
CREATE INDEX IF NOT EXISTS idx_pedidos_status     ON pedidos(status);
CREATE INDEX IF NOT EXISTS idx_pedidos_visit_date ON pedidos(visit_date);