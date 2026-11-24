-- =============================================================
-- Loja Gamer — Schema + Seed
-- Banco: loja_gamer  |  charset: utf8mb4_unicode_ci
-- =============================================================

CREATE DATABASE IF NOT EXISTS loja_gamer
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE loja_gamer;

-- ─────────────────────────────────────────
-- Usuários (clientes e admins)
-- ─────────────────────────────────────────
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(180) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_admin      TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- Categorias
-- ─────────────────────────────────────────
CREATE TABLE categories (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120) NOT NULL,
  slug       VARCHAR(160) NOT NULL UNIQUE,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- Produtos
-- ─────────────────────────────────────────
CREATE TABLE products (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  category_id       INT          NOT NULL,
  name              VARCHAR(200) NOT NULL,
  slug              VARCHAR(220) NOT NULL UNIQUE,
  short_description VARCHAR(255) NOT NULL,
  description       TEXT         NOT NULL,
  image_url         VARCHAR(255) NOT NULL,
  unit_price        DECIMAL(10,2) NOT NULL,
  stock_qty         INT          NOT NULL DEFAULT 0,
  is_active         TINYINT(1)   NOT NULL DEFAULT 1,
  featured          TINYINT(1)   NOT NULL DEFAULT 0,
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_featured  ON products(featured);

-- ─────────────────────────────────────────
-- Avaliações
-- ─────────────────────────────────────────
CREATE TABLE reviews (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT     NOT NULL,
  user_id    INT     NOT NULL,
  rating     TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment    VARCHAR(500),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_user    FOREIGN KEY (user_id)    REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_reviews_product ON reviews(product_id);

-- ─────────────────────────────────────────
-- Favoritos
-- ─────────────────────────────────────────
CREATE TABLE favorites (
  user_id    INT NOT NULL,
  product_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, product_id),
  CONSTRAINT fk_fav_user    FOREIGN KEY (user_id)    REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_fav_product FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- Banners (página inicial)
-- ─────────────────────────────────────────
CREATE TABLE banners (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT          NOT NULL,
  title      VARCHAR(160) NOT NULL,
  subtitle   VARCHAR(220),
  image_url  VARCHAR(255) NOT NULL,
  sort_order INT          NOT NULL DEFAULT 0,
  is_active  TINYINT(1)   NOT NULL DEFAULT 1,
  CONSTRAINT fk_banners_product FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_banners_active ON banners(is_active, sort_order);

-- ─────────────────────────────────────────
-- Carrinho (session-based ou user-based)
-- ─────────────────────────────────────────
CREATE TABLE carts (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT,           -- null para visitante
  session_id VARCHAR(64),
  status     VARCHAR(20)  NOT NULL DEFAULT 'open',
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_cart_user    UNIQUE (user_id),
  CONSTRAINT uq_cart_session UNIQUE (session_id),
  CONSTRAINT fk_cart_user    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- Itens do carrinho
-- ─────────────────────────────────────────
CREATE TABLE cart_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  cart_id    INT           NOT NULL,
  product_id INT           NOT NULL,
  quantity   INT           NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME      NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_cart_id    (cart_id),
  INDEX idx_product_id (product_id),
  CONSTRAINT fk_cart_items_cart    FOREIGN KEY (cart_id)    REFERENCES carts(id)    ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- Pedidos
-- ─────────────────────────────────────────
CREATE TABLE orders (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  user_id          INT,
  status           ENUM('pending','paid','shipped','delivered','canceled') NOT NULL DEFAULT 'pending',
  subtotal         DECIMAL(10,2) NOT NULL,
  shipping         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total            DECIMAL(10,2) NOT NULL,
  payment_method   ENUM('pix','boleto','credit_card','debit_card') NOT NULL,
  shipping_address VARCHAR(300)  NOT NULL,
  created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- Itens do pedido
-- ─────────────────────────────────────────
CREATE TABLE order_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT           NOT NULL,
  product_id INT           NOT NULL,
  quantity   INT           NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  total_line DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_order_item_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT fk_order_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- Log de ações do admin
-- ─────────────────────────────────────────
CREATE TABLE admin_logs (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  admin_id   INT          NOT NULL,
  action     VARCHAR(200) NOT NULL,
  entity     VARCHAR(100),
  entity_id  INT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_admin_logs_user FOREIGN KEY (admin_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =============================================================
-- DADOS DE SEED
-- =============================================================

-- Categorias (slugs alinhados com os links do header)
INSERT INTO categories (name, slug) VALUES
('Periféricos',      'perifericos'),
('Placas de vídeo',  'placas-de-video'),
('Monitores',        'monitores'),
('Gabinetes',        'gabinetes'),
('Processadores',    'processadores'),
('Headsets',         'headsets');

-- Produtos de exemplo (category_id referencia a ordem dos INSERTs acima: 1=periféricos, 2=placas-de-video, etc.)
INSERT INTO products (category_id, name, slug, short_description, description, image_url, unit_price, stock_qty, is_active, featured) VALUES
(2, 'RTX 4070 Super',          'rtx-4070-super',         'Placa de vídeo para 1440p alto FPS',    'A RTX 4070 Super oferece desempenho excepcional para jogos em 1440p com ray tracing e DLSS 3. 12 GB GDDR6X, 192-bit, boost 2475 MHz.',             'images/products/rtx4070.jpg',    3499.90, 12, 1, 1),
(1, 'Teclado Mecânico RGB',    'teclado-mecanico-rgb',   'Switches rápidos para gamers',          'Teclado mecânico com switches lineares, iluminação RGB por tecla, Anti-Ghosting completo e construção em alumínio.',                               'images/products/teclado.jpg',    399.00,  30, 1, 1),
(3, 'Monitor 27" 165Hz IPS',   'monitor-27-165hz',       'Alta taxa de atualização para jogos',   'Painel IPS 27 polegadas, resolução 1440p, 165Hz, 1ms MPRT, AMD FreeSync Premium e cobertura 99% sRGB.',                                           'images/products/monitor27.jpg',  1599.00,  8, 1, 0),
(1, 'Mouse Gamer 16K DPI',     'mouse-gamer-16k',        'Sensor preciso e leve',                 'Mouse gamer com sensor óptico 16.000 DPI, 6 botões programáveis, peso 68g e iluminação RGB. Ideal para FPS.',                                      'images/products/mouse.jpg',       249.00, 42, 1, 0),
(5, 'Processador Ryzen 7 7700X','processador-ryzen7-7700x','8 núcleos para alto desempenho',      'AMD Ryzen 7 7700X com 8 núcleos / 16 threads, clock boost 5.4GHz, TDP 105W, AM5. Excelente para gaming e produtividade.',                         'images/products/processador.jpg',1899.00, 15, 1, 1),
(4, 'Gabinete Gamer ATX',      'gabinete-gamer-atx',     'Design clean com boa ventilação',       'Gabinete mid-tower ATX com painel lateral em vidro temperado, 4 fans RGB inclusos, suporte a cooler 360mm e GPUs até 420mm.',                      'images/products/gabinete.jpg',    699.00, 20, 1, 0),
(6, 'Headset 7.1 Surround',    'headset-71-surround',    'Audio imersivo para gaming',             'Headset gamer com som surround 7.1 virtual, microfone com cancelamento de ruído, drivers 50mm e espuma memory foam nas almofadas.',               'images/products/headset.jpg',     299.00, 25, 1, 0),
(2, 'RTX 4060 Ti',             'rtx-4060-ti',            'Ótimo custo-benefício para 1080p/1440p','RTX 4060 Ti com 8GB GDDR6, perfeita para 1080p máximo e 1440p alto. DLSS 3, Ada Lovelace architecture.',                                         'images/products/rtx4060ti.jpg',  2199.00, 18, 1, 0),
(3, 'Monitor 49" UltraWide',   'monitor-49-ultrawide',   'Experiência cinematográfica',           'Monitor curvo 49 polegadas DQHD (5120x1440), 240Hz, 1ms, HDR1000, Mini LED. Para setups premium.',                                               'images/products/monitor49.jpg',  7999.00,  3, 1, 0),
(1, 'Mousepad XXL RGB',        'mousepad-xxl-rgb',       'Cobertura total da mesa',               'Mousepad gigante 900x400x4mm com bordas costuradas, superfície de tecido premium e iluminação RGB nas bordas.',                                    'images/products/mousepad.jpg',     89.00, 60, 1, 0);

-- Banners
INSERT INTO banners (product_id, title, subtitle, image_url, sort_order, is_active) VALUES
(1, 'RTX 4070 Super — Chegou!',      'Desempenho sob medida para 1440p e Ray Tracing',         'images/banners/rtx4070-banner.jpg', 1, 1),
(2, 'Teclado Mecânico RGB',          'Cada tecla conta. Switches precisos, resposta imediata.', 'images/banners/teclado-banner.jpg', 2, 1),
(5, 'Ryzen 7 7700X — Power Unlocked','8 núcleos que não deixam nenhum jogo pra trás.',          'images/banners/ryzen7-banner.jpg',  3, 1);

-- Conta administrativa de demonstração. O hash corresponde a uma senha aleatória
-- não publicada; defina sua própria senha antes do primeiro login.
INSERT INTO users (name, email, password_hash, is_admin) VALUES
('Administrador', 'admin@lojagamer.com', '$2y$12$kn1Y0tgrbqwZjxutxwCPl.3CL1dutTqrboGSoAm7ytTSzOz1a2MOq', 1);
