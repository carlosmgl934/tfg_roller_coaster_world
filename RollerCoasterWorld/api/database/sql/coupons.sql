 CREATE TABLE IF NOT EXISTS coupons (
      id              SERIAL PRIMARY KEY,
      code            VARCHAR(32) UNIQUE NOT NULL,
      description     VARCHAR(255),
      discount_type   VARCHAR(10) NOT NULL DEFAULT 'percent',
      discount_value  NUMERIC(10,2) NOT NULL,
      min_order       NUMERIC(10,2) DEFAULT 0,
      max_uses        INT DEFAULT NULL,
      uses_count      INT DEFAULT 0,
      active          BOOLEAN DEFAULT true,
      expires_at      DATE DEFAULT NULL,
      created_at      TIMESTAMP DEFAULT NOW()
    );