USE loja_gamer;

CREATE TABLE IF NOT EXISTS coupons (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  code          VARCHAR(30)   NOT NULL UNIQUE,
  type          ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  value         DECIMAL(10,2) NOT NULL,
  min_order     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  max_uses      INT           NOT NULL DEFAULT 0,   -- 0 = ilimitado
  used_count    INT           NOT NULL DEFAULT 0,
  expires_at    DATETIME,
  is_active     TINYINT(1)    NOT NULL DEFAULT 1,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Cupons de exemplo
INSERT INTO coupons (code, type, value, min_order, max_uses, is_active) VALUES
('BEMVINDO10', 'percent', 10.00,    0.00, 0,  1),
('SETUP200',   'fixed',   200.00, 1500.00, 100, 1),
('FRETEGRATIS','fixed',    29.90,  100.00, 0,  1);

-- Tabela para registrar uso por pedido
CREATE TABLE IF NOT EXISTS order_coupons (
  order_id  INT NOT NULL,
  coupon_id INT NOT NULL,
  discount  DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (order_id),
  CONSTRAINT fk_oc_order  FOREIGN KEY (order_id)  REFERENCES orders(id)  ON DELETE CASCADE,
  CONSTRAINT fk_oc_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

ALTER TABLE orders ADD COLUMN discount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER shipping;
