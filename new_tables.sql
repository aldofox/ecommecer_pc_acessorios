USE loja_gamer;

CREATE TABLE IF NOT EXISTS blog_posts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  author_id    INT NOT NULL,
  title        VARCHAR(200) NOT NULL,
  slug         VARCHAR(220) NOT NULL UNIQUE,
  summary      VARCHAR(300) NOT NULL,
  content      TEXT NOT NULL,
  cover_url    VARCHAR(255),
  category     VARCHAR(80) NOT NULL DEFAULT 'Geral',
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_blog_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS newsletter (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(180) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS setups (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT,
  session_id VARCHAR(64),
  name       VARCHAR(120) NOT NULL DEFAULT 'Meu Setup',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_setup_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS setup_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  setup_id   INT NOT NULL,
  product_id INT NOT NULL,
  slot       VARCHAR(60) NOT NULL,
  UNIQUE KEY uq_setup_slot (setup_id, slot),
  CONSTRAINT fk_si_setup   FOREIGN KEY (setup_id)   REFERENCES setups(id)   ON DELETE CASCADE,
  CONSTRAINT fk_si_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Posts de blog de exemplo
INSERT INTO blog_posts (author_id, title, slug, summary, content, category, is_published, created_at) VALUES
(1, 'Como montar um PC Gamer do zero em 2025', 'como-montar-pc-gamer-2025',
 'Guia completo com os melhores componentes custo-benefício para montar seu setup gamer este ano.',
 '<h2>Por onde começar?</h2><p>Montar um PC do zero pode parecer intimidador, mas com a lista certa de componentes e uma boa organização, qualquer pessoa consegue. Neste guia vamos cobrir cada componente essencial.</p><h2>1. Processador (CPU)</h2><p>A CPU é o coração do PC. Para gaming em 2025, recomendamos o AMD Ryzen 5 7600X ou Intel Core i5-13600K. Ambos entregam excelente performance em jogos sem custo excessivo.</p><h2>2. Placa de Vídeo (GPU)</h2><p>A GPU é o componente mais importante para gaming. A RTX 4070 Super oferece o melhor custo-benefício para 1440p com ray tracing. Para 1080p, a RTX 4060 já é mais que suficiente.</p><h2>3. Memória RAM</h2><p>Para gaming moderno, 16GB DDR5 é o mínimo recomendado. Prefira kits dual-channel (2x8GB) para melhor performance. Velocidade de 6000MHz é o sweet spot para AM5.</p><h2>4. Armazenamento</h2><p>Um SSD NVMe PCIe 4.0 de 1TB é essencial. Os tempos de carregamento em jogos caem drasticamente comparado a HDDs ou SSDs SATA.</p><h2>Conclusão</h2><p>Com um orçamento de R$ 5.000 a R$ 7.000 é possível montar um PC capaz de rodar qualquer jogo atual em configurações altas. Acesse nossa loja e use o Monte seu Setup para calcular o total.</p>',
 'Tutoriais', 1, NOW() - INTERVAL 15 DAY),

(1, 'RTX 4070 Super vs RX 7800 XT: qual comprar?', 'rtx-4070-super-vs-rx-7800-xt',
 'Comparamos as duas principais placas de vídeo da faixa de R$ 2.800 a R$ 3.500 em 2025.',
 '<h2>Introdução</h2><p>A escolha entre NVIDIA e AMD nunca foi tão difícil. A RTX 4070 Super e a RX 7800 XT brigam diretamente na mesma faixa de preço com propostas diferentes.</p><h2>Performance em Jogos</h2><p>Em 1440p sem ray tracing, as placas ficam empatadas na maioria dos títulos. A RTX 4070 Super leva vantagem em ray tracing e com DLSS 3 Frame Generation pode dobrar o FPS em títulos suportados.</p><p>A RX 7800 XT contraataca com 16GB de VRAM — vantagem real em jogos como Microsoft Flight Simulator e Alan Wake 2 que usam muita memória.</p><h2>Consumo de energia</h2><p>A RX 7800 XT consome ~263W versus ~220W da RTX 4070 Super. Diferença relevante para quem tem fonte mais simples.</p><h2>Veredicto</h2><p>Joga principalmente FPS e usa ray tracing? RTX 4070 Super. Joga títulos AAA pesados e quer mais VRAM para o futuro? RX 7800 XT.</p>',
 'Reviews', 1, NOW() - INTERVAL 8 DAY),

(1, 'Top 5 Monitores Gamer de 2025', 'top-5-monitores-gamer-2025',
 'Selecionamos os 5 melhores monitores para diferentes perfis: do casual ao profissional competitivo.',
 '<h2>Como escolher um monitor gamer?</h2><p>Taxa de refresh, resolução e tipo de painel são os três pilares. Não existe o monitor perfeito — existe o monitor certo para o seu perfil.</p><h2>1. Para FPS Competitivo — 240Hz 1080p</h2><p>Velocidade acima de tudo. Um IPS 240Hz com 0.5ms é a escolha dos pro players de CS2 e Valorant. Nosso Monitor 24" 240Hz é excelente opção.</p><h2>2. Para Gaming Casual/RPG — 1440p 165Hz</h2><p>O equilíbrio perfeito. 1440p tem visual muito melhor que 1080p e 165Hz é fluido para qualquer jogo. O Monitor 27" 165Hz cobre perfeitamente este nicho.</p><h2>3. Para Imersão Total — Ultrawide 49"</h2><p>Proporção 32:9 transforma qualquer jogo. O Monitor 49" Samsung Odyssey com 240Hz e HDR1000 é a experiência definitiva, mas exige GPU potente.</p><h2>Conclusão</h2><p>Para 90% dos gamers, um IPS 1440p 165Hz entre R$ 1.200 e R$ 1.800 é o melhor custo-benefício de 2025.</p>',
 'Guias', 1, NOW() - INTERVAL 3 DAY),

(1, 'Teclado mecânico: como escolher o switch certo?', 'como-escolher-switch-teclado-mecanico',
 'Lineares, táteis ou clicky? Descubra qual tipo de switch combina com seu estilo de uso.',
 '<h2>Os tipos de switch</h2><p>Switches mecânicos se dividem em três categorias principais, cada uma com características únicas de som e tato.</p><h2>Lineares (Red/Yellow)</h2><p>Movimento suave e silencioso sem ponto tátil. Favoritos para gaming, especialmente FPS onde acionamentos rápidos são essenciais. Sem feedback sonoro, ideais para quem digita ou joga em ambientes compartilhados.</p><h2>Táteis (Brown/Clear)</h2><p>Possuem um bump tátil no ponto de atuação sem click audível. Versáteis — funcionam bem para gaming e digitação. A escolha mais popular para quem faz as duas coisas.</p><h2>Clicky (Blue/Green)</h2><p>Click sonoro e bump tátil pronunciado. Satisfatórios para digitação mas podem incomodar em ambientes silenciosos. Contraindicados para gaming online com microfone aberto.</p><h2>Nossa recomendação</h2><p>Iniciantes: Brown (tátil). Gamers dedicados: Red (linear). Escritores: Blue ou Brown. Nosso Teclado Mecânico RGB usa switches lineares Red, ideais para a maioria dos gamers.</p>',
 'Guias', 1, NOW() - INTERVAL 1 DAY);
