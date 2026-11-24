USE loja_gamer;

-- ═══════════════════════════════════════════════════════════
-- Novos Produtos — Loja Gamer
-- Categorias: 1=Perifericos 2=Placas 3=Monitores 4=Procs 5=Gabinetes 6=Headsets
-- ═══════════════════════════════════════════════════════════

INSERT INTO products
  (category_id, name, slug, short_description, description, image_url,
   unit_price, compare_price, stock_qty, is_active, featured,
   specs)
VALUES

-- ── PERIFÉRICOS ─────────────────────────────────────────────

(1, 'Teclado 65% Wireless Branco',
 'teclado-65-wireless-branco',
 'Compacto, sem fio e com RGB suave',
 'Teclado 65% sem fio com conexao Bluetooth 5.0 e 2.4GHz. Bateria de 4000mAh com ate 3 semanas de autonomia. Switches silenciosos ideais para escritorio e gaming casual. Compativel com Windows e macOS.',
 'images/teclado.png',
 449.00, 549.00, 22, 1, 0,
 '[{"label":"Layout","value":"65% ABNT2"},{"label":"Conectividade","value":"Bluetooth 5.0 + 2.4GHz + USB-C"},{"label":"Switches","value":"Mecanicos silenciosos (Brown)"},{"label":"Bateria","value":"4000 mAh (ate 3 semanas BT)"},{"label":"Iluminacao","value":"RGB backlight"},{"label":"Peso","value":"680 g"},{"label":"Cor","value":"Branco ivory"}]'),

(1, 'Mouse Sem Fio 4K Hz',
 'mouse-sem-fio-4k-hz',
 'Polling rate de 4000 Hz para maxima precisao',
 'Mouse gamer sem fio com polling rate de 4000 Hz, o dobro dos mouses convencionais. Sensor optico de 26.000 DPI, autonomia de 70 horas, carregamento USB-C e peso ultra leve de 63g. Para quem exige o melhor em competitivo.',
 'images/mouse.png',
 599.00, 749.00, 17, 1, 1,
 '[{"label":"Sensor","value":"PixArt PAW3395 26000 DPI"},{"label":"Polling Rate","value":"4000 Hz"},{"label":"Conectividade","value":"2.4 GHz sem fio + USB-C"},{"label":"Bateria","value":"70 horas"},{"label":"Peso","value":"63 g"},{"label":"Botoes","value":"6 programaveis"},{"label":"Carregamento","value":"USB-C 1h carga completa"}]'),

(1, 'Webcam 4K 60fps com AI',
 'webcam-4k-60fps',
 'Streaming e videoconferencia em qualidade cinema',
 'Webcam 4K a 60fps com HDR automatico, cancelamento de ruido por IA e enquadramento automatico que te mantem no centro do frame. Ideal para streamers, criadores de conteudo e reunioes profissionais. Plug and play USB-C.',
 'images/monitor27.png',
 899.00, 1099.00, 11, 1, 0,
 '[{"label":"Resolucao","value":"3840 x 2160 (4K) a 60fps"},{"label":"Campo de visao","value":"90 graus ajustavel"},{"label":"HDR","value":"Automatico"},{"label":"Microfone","value":"Duplo com cancelamento IA"},{"label":"Conexao","value":"USB-C (plug and play)"},{"label":"Compatibilidade","value":"Windows macOS Linux"},{"label":"Enquadramento","value":"Automatico por IA"}]'),

(1, 'Controle Gamer USB+BT',
 'controle-gamer-usb-bt',
 'Compativel com PC PS e Android',
 'Controle sem fio com conexao USB 2.4GHz e Bluetooth 5.0. Vibração dupla de alta frequencia, gatilhos analogicos com 256 niveis de pressao, bateria de 1000mAh e layout semelhante ao PS. Compativel com PC, Android e PS3.',
 'images/mouse.png',
 249.00, 299.00, 35, 1, 0,
 '[{"label":"Conectividade","value":"Bluetooth 5.0 + USB 2.4GHz"},{"label":"Bateria","value":"1000 mAh (20h de uso)"},{"label":"Vibracao","value":"Dual Rumble HD"},{"label":"Gatilhos","value":"Analogicos 256 niveis"},{"label":"Compatibilidade","value":"PC Windows Android PS3"},{"label":"Peso","value":"210 g"}]'),

-- ── PLACAS DE VIDEO ──────────────────────────────────────────

(2, 'RX 7800 XT 16GB',
 'rx-7800-xt-16gb',
 'O melhor custo-beneficio para 1440p em 2024',
 'AMD Radeon RX 7800 XT com 16GB GDDR6 e arquitetura RDNA 3. Performance excepcional para 1440p, suporte a FSR 3 (Frame Generation), AV1 encode/decode e saidas HDMI 2.1 + DisplayPort 2.1. Alternativa solida a RTX.',
 'images/rtx4070.png',
 2799.00, 3299.00, 8, 1, 1,
 '[{"label":"Chipset","value":"AMD Radeon RX 7800 XT"},{"label":"Memoria","value":"16 GB GDDR6"},{"label":"Barramento","value":"256-bit"},{"label":"Clock Boost","value":"2430 MHz"},{"label":"Compute Units","value":"60"},{"label":"TDP","value":"263W"},{"label":"Saidas","value":"1x HDMI 2.1 + 2x DisplayPort 2.1"},{"label":"FSR","value":"Versao 3 Frame Generation"},{"label":"Comprimento","value":"275 mm"}]'),

(2, 'RTX 4060 8GB OC',
 'rtx-4060-8gb-oc',
 'Entrada perfeita no mundo RTX para 1080p',
 'NVIDIA RTX 4060 na versao factory OC com 8GB GDDR6. Ideal para 1080p maximo e 1440p medio com DLSS 3. Consome apenas 115W, perfeita para fontes menores e setups mais compactos. DLSS Frame Generation incluso.',
 'images/rtx4070.png',
 1799.00, 2199.00, 20, 1, 0,
 '[{"label":"Chipset","value":"NVIDIA GeForce RTX 4060 OC"},{"label":"Memoria","value":"8 GB GDDR6"},{"label":"Barramento","value":"128-bit"},{"label":"Clock Boost","value":"2550 MHz (OC)"},{"label":"CUDA Cores","value":"3072"},{"label":"TDP","value":"115W"},{"label":"DLSS","value":"Versao 3 Frame Generation"},{"label":"Saidas","value":"3x DisplayPort 1.4a + 1x HDMI 2.1"},{"label":"Comprimento","value":"240 mm"}]'),

-- ── MONITORES ────────────────────────────────────────────────

(3, 'Monitor 24" FHD 240Hz',
 'monitor-24-fhd-240hz',
 '240Hz IPS para FPS competitivo',
 'Monitor 24 polegadas Full HD com painel IPS a 240Hz para maxima fluidez em FPS competitivos. Tempo de resposta de 0.5ms GtG, FreeSync Premium e G-Sync compativel. O escolhido dos jogadores profissionais de CS2 e Valorant.',
 'images/monitor27.png',
 1099.00, 1399.00, 15, 1, 0,
 '[{"label":"Tamanho","value":"24 polegadas"},{"label":"Resolucao","value":"1920 x 1080 (Full HD)"},{"label":"Painel","value":"IPS"},{"label":"Taxa de Refresh","value":"240 Hz"},{"label":"Tempo de Resposta","value":"0.5 ms GtG"},{"label":"FreeSync","value":"Premium"},{"label":"G-Sync","value":"Compativel"},{"label":"Entradas","value":"1x HDMI 2.0 + 2x DisplayPort 1.4"},{"label":"Brilho","value":"350 cd/m2"}]'),

(3, 'Monitor 32" 4K OLED 144Hz',
 'monitor-32-4k-oled-144hz',
 'OLED 4K: o melhor painel do mercado',
 'Monitor 32 polegadas OLED 4K com 144Hz. Contraste infinito, preto absoluto, cobertura 99% DCI-P3 e tempo de resposta de 0.03ms. A escolha definitiva para quem nao abre mao de qualidade de imagem premium tanto em jogos quanto em criacao de conteudo.',
 'images/monitor49.png',
 5999.00, 7499.00, 4, 1, 1,
 '[{"label":"Tamanho","value":"32 polegadas"},{"label":"Resolucao","value":"3840 x 2160 (4K UHD)"},{"label":"Painel","value":"WOLED"},{"label":"Taxa de Refresh","value":"144 Hz"},{"label":"Tempo de Resposta","value":"0.03 ms"},{"label":"Contraste","value":"Infinito"},{"label":"HDR","value":"DisplayHDR True Black 400"},{"label":"Cobertura DCI-P3","value":"99%"},{"label":"Entradas","value":"2x HDMI 2.1 + 2x DisplayPort 1.4"},{"label":"VESA","value":"100 x 100 mm"}]'),

-- ── PROCESSADORES ────────────────────────────────────────────

(4, 'Intel Core i7-14700K',
 'intel-core-i7-14700k',
 '20 nucleos para gaming e produtividade extrema',
 'Intel Core i7-14700K com 20 nucleos (8P + 12E) e 28 threads. Clock boost de ate 5.6GHz no P-Core. Excelente para gaming em alta frequencia e multitarefa pesada. Compativel com DDR5 e DDR4. Requer cooler de alta performance.',
 'images/proxessador_ryzen7.png',
 2199.00, 2699.00, 10, 1, 0,
 '[{"label":"Modelo","value":"Intel Core i7-14700K"},{"label":"Nucleos Threads","value":"20 nucleos (8P+12E) 28 threads"},{"label":"Clock Base P-Core","value":"3.4 GHz"},{"label":"Clock Boost","value":"5.6 GHz maximo"},{"label":"Cache L3","value":"33 MB Intel Smart Cache"},{"label":"Socket","value":"LGA1700"},{"label":"TDP","value":"125W base 253W boost"},{"label":"PCIe","value":"5.0 + 4.0"},{"label":"Memoria","value":"DDR5-5600 e DDR4-3200"},{"label":"Cooler","value":"Nao incluso"}]'),

(4, 'AMD Ryzen 5 7600X',
 'amd-ryzen5-7600x',
 'O processador mais custo-beneficio de 2024',
 'AMD Ryzen 5 7600X com 6 nucleos e clock boost de 5.3GHz. Melhor processador da categoria preco-desempenho para gaming. Consome apenas 105W, roda quase tudo acima de 144fps em 1080p com uma boa GPU. Plataforma AM5 com suporte longo.',
 'images/proxessador_ryzen7.png',
 1199.00, 1499.00, 18, 1, 0,
 '[{"label":"Modelo","value":"AMD Ryzen 5 7600X"},{"label":"Nucleos Threads","value":"6 nucleos 12 threads"},{"label":"Clock Base","value":"4.7 GHz"},{"label":"Clock Boost","value":"5.3 GHz maximo"},{"label":"Cache L3","value":"32 MB"},{"label":"Socket","value":"AM5"},{"label":"TDP","value":"105W"},{"label":"Fabricacao","value":"TSMC 5nm FinFET"},{"label":"Memoria","value":"DDR5-5200 JEDEC"},{"label":"Cooler","value":"Wraith Stealth incluso"}]'),

-- ── GABINETES ────────────────────────────────────────────────

(5, 'Mini-ITX Compacto RGB',
 'gabinete-mini-itx-compacto',
 'Pequeno por fora, potente por dentro',
 'Gabinete Mini-ITX com painel lateral em vidro temperado e 2 fans ARGB inclusos. Suporta GPUs de ate 330mm e cooler de ate 130mm de altura. Perfeito para quem quer um PC poderoso em formato compacto para a mesa ou estante.',
 'images/gabinete_branco.png',
 499.00, 599.00, 13, 1, 0,
 '[{"label":"Formato","value":"Mini-ITX"},{"label":"Painel lateral","value":"Vidro temperado 3mm"},{"label":"Fans inclusos","value":"2x 120mm ARGB"},{"label":"GPU maxima","value":"330 mm"},{"label":"Cooler maximo","value":"130 mm"},{"label":"Baias","value":"1x 2.5 polegadas + 1x 3.5 polegadas"},{"label":"IO Frontal","value":"1x USB-A 3.0 + audio P2"},{"label":"Dimensoes","value":"210 x 250 x 280 mm"},{"label":"Peso","value":"3.8 kg"}]'),

-- ── HEADSETS ─────────────────────────────────────────────────

(6, 'Headset 7.1 USB Premium',
 'headset-71-usb-premium',
 'Som surround real para imersao total em jogos',
 'Headset gamer com som surround 7.1 virtual via USB. Drivers de 50mm com resposta de frequencia 20Hz-20KHz, microfone com cancelamento de ruido e omnidirecional retravel. Almofadas em couro sintetico com espuma memory foam para longas sessoes.',
 'images/mouse.png',
 349.00, 449.00, 28, 1, 0,
 '[{"label":"Drivers","value":"50mm Neodimio"},{"label":"Resposta de Frequencia","value":"20 Hz a 20 KHz"},{"label":"Surround","value":"7.1 Virtual USB"},{"label":"Microfone","value":"Unidirecional retravel"},{"label":"Cancelamento de Ruido","value":"Sim"},{"label":"Conexao","value":"USB-A"},{"label":"Comprimento Cabo","value":"2.2m"},{"label":"Peso","value":"320 g"},{"label":"Compatibilidade","value":"PC Windows macOS"}]'),

(6, 'Headset Wireless 2.4GHz',
 'headset-wireless-2ghz',
 'Sem fio sem latencia para gaming competitivo',
 'Headset sem fio com transmissor USB de 2.4GHz garantindo latencia ultra baixa de 4ms — imperceptivel mesmo em FPS competitivos. 30 horas de bateria, microfone destacavel com LED mute e drivers de 40mm com som espacial DTS:X 2.0.',
 'images/mouse.png',
 649.00, 799.00, 14, 1, 1,
 '[{"label":"Conectividade","value":"USB 2.4GHz wireless"},{"label":"Latencia","value":"4 ms"},{"label":"Bateria","value":"30 horas"},{"label":"Drivers","value":"40mm Neodimio"},{"label":"Surround","value":"DTS:X 2.0"},{"label":"Microfone","value":"Destacavel com LED mute"},{"label":"Resposta","value":"10 Hz a 20 KHz"},{"label":"Peso","value":"340 g"},{"label":"Carregamento","value":"USB-C 2.5h"}]');
