-- ═══════════════════════════════════════════════════════
-- Seed de Reviews — Loja Gamer
-- Usuários: 1(Admin) 2(Aldo) 3(Sofia) 4(Clariceli) 5(Megue) 6(Clarinha) 7(Gordo)
-- Produtos: 1(RTX4070) 2(Teclado) 3(Monitor27) 4(Mouse) 6(MonitorSamsung) 7(Ryzen7) 8(GabBranco) 9(GabPreto) 10(MousePad)
-- ═══════════════════════════════════════════════════════
USE loja_gamer;

INSERT INTO reviews (product_id, user_id, rating, comment, created_at) VALUES

-- RTX 4070 Super (produto 1) — média ~4.6
(1, 2, 5, 'Absurda essa placa! Rodando Cyberpunk 2077 em 1440p ultra com ray tracing ligado e ainda passando dos 80fps. Valeu cada centavo.', NOW() - INTERVAL 30 DAY),
(1, 3, 5, 'Cheguei do trabalho, instalei em 20 minutos e já estava jogando. A temperatura fica bem controlada, nunca passou de 74 graus nos meus testes.', NOW() - INTERVAL 25 DAY),
(1, 4, 4, 'Muito boa para 1440p. Só achei o cooler um pouco barulhento em carga máxima, mas nada que incomode muito. Entrega foi rápida também.', NOW() - INTERVAL 20 DAY),
(1, 5, 5, 'Upgrade que fiz saindo da GTX 1080 e a diferença é brutal. DLSS 3 funciona muito bem, dá quase o dobro de fps em vários jogos.', NOW() - INTERVAL 15 DAY),
(1, 6, 4, 'Ótima placa. Embalagem bem protegida, veio sem nenhum dano. Uso para design também e renderiza muito rápido.', NOW() - INTERVAL 10 DAY),

-- Teclado Mecânico RGB (produto 2) — média ~4.4
(2, 3, 5, 'Melhor teclado que já tive! Os switches lineares são incríveis para jogos, resposta muito rápida. O RGB ficou lindo no setup.', NOW() - INTERVAL 28 DAY),
(2, 4, 4, 'Qualidade de construção excelente, o alumínio passa uma sensação premium. Só senti falta de um software melhor para customizar o RGB.', NOW() - INTERVAL 22 DAY),
(2, 7, 5, 'Sons dos switches me deixaram obcecado kkk. Digitação é uma delícia, zero fadiga mesmo depois de 8h de trabalho.', NOW() - INTERVAL 18 DAY),
(2, 2, 4, 'Bom custo-benefício. O cabo é removível o que é ótimo. Anti-ghosting funciona perfeitamente testei com várias teclas simultâneas.', NOW() - INTERVAL 12 DAY),
(2, 6, 3, 'Teclado bom mas o software de RGB para Windows trava às vezes. Fisicamente não tenho reclamação nenhuma.', NOW() - INTERVAL 5 DAY),

-- Monitor 27" 165Hz (produto 3) — média ~4.8
(3, 2, 5, 'Cores incríveis para um IPS! O 1ms de resposta é real, zero ghosting em FPS competitivos. Tela perfeita para quem joga e trabalha.', NOW() - INTERVAL 35 DAY),
(3, 5, 5, 'Montagem simples, qualidade de imagem top. FreeSync funciona muito bem com minha RX 6700 XT. Compra que não me arrependo.', NOW() - INTERVAL 26 DAY),
(3, 7, 5, 'Saindo de um monitor 60hz a diferença é surreal. Vale cada real. A calibração de fábrica já veio muito boa.', NOW() - INTERVAL 19 DAY),
(3, 3, 4, 'Ótimo monitor. Tela anti-reflexo funciona bem mesmo com luz do sol entrando. Só o ajuste de altura do suporte que poderia ser melhor.', NOW() - INTERVAL 8 DAY),

-- Mouse Gamer 16K DPI (produto 4) — média ~4.3
(4, 4, 5, 'Levíssimo! Usei um mouse de 120g por anos e esse de 68g mudou meu jogo completamente. Sensor preciso mesmo em velocidades altas.', NOW() - INTERVAL 32 DAY),
(4, 6, 4, 'Ótimo mouse para FPS. Uso a 1600 DPI e é certeiro. O scroll poderia ter mais "trava" nos steps mas é questão de preferência.', NOW() - INTERVAL 24 DAY),
(4, 7, 4, 'Muito bom pelo preço. Já uso há 4 meses sem nenhum problema. O cabo é flexível e não atrapalha o movimento.', NOW() - INTERVAL 16 DAY),
(4, 5, 3, 'Mouse bom mas para mão grande fica um pouco pequeno. Sensor é ótimo mas o tamanho não me agradou 100%.', NOW() - INTERVAL 9 DAY),

-- Monitor Samsung Odyssey (produto 6) — média ~4.5
(6, 2, 5, 'Experiência cinematográfica mesmo! Jogar Forza Horizon 5 nesse ultrawide é de outro nível. Imersão total.', NOW() - INTERVAL 21 DAY),
(6, 3, 5, 'Tela incrível para produtividade. Consigo ter 3 janelas abertas lado a lado sem problema. O HDR é impressionante.', NOW() - INTERVAL 14 DAY),
(6, 4, 4, 'Painel lindo mas pesado e o suporte é um pouco complicado de montar. Depois de montar é perfeito, não saio mais.', NOW() - INTERVAL 7 DAY),

-- Processador Ryzen 7 (produto 7) — média ~4.7
(7, 5, 5, 'Montei um PC novo com esse Ryzen e estou impressionado. Edição de vídeo em 4K ficou muito mais fluida comparado ao meu Intel antigo.', NOW() - INTERVAL 29 DAY),
(7, 6, 5, 'Temperatura ótima mesmo sem OC, com um cooler mediano fica abaixo de 70 graus em carga plena. Recomendo muito.', NOW() - INTERVAL 23 DAY),
(7, 7, 4, 'Ótimo processador, mas a caixa veio sem cooler incluso — atenção nisso na hora de comprar. O desempenho em jogos é excelente.', NOW() - INTERVAL 17 DAY),
(7, 2, 5, 'Usava um Ryzen 5 antes, o salto de performance é enorme especialmente em multicore. Recomendo para quem usa o PC para trabalho e jogo.', NOW() - INTERVAL 11 DAY),

-- Gabinete ATX Branco (produto 8) — média ~4.2
(8, 3, 5, 'Montagem simples e bem intuitiva. O vidro temperado é espesso, parece resistente. Os 4 fans RGB já fazem o setup ficar lindo.', NOW() - INTERVAL 20 DAY),
(8, 4, 4, 'Ótimo gabinete pelo preço. Cable management é muito bom, ficou organizado. Só faltou vir com mais presilhas de cabo.', NOW() - INTERVAL 13 DAY),
(8, 5, 3, 'Bom custo-benefício. O branco é bem bonito mas risca com facilidade então cuidado na montagem. Os fans são um pouco barulhentos.', NOW() - INTERVAL 6 DAY),

-- Gabinete ATX Preto (produto 9) — média ~4.4
(9, 6, 5, 'Acabamento excelente, parece mais caro do que é. Interior espaçoso, consegui montar uma placa de vídeo de 380mm sem problema.', NOW() - INTERVAL 27 DAY),
(9, 7, 4, 'Muito bom. A malha frontal tem boa ventilação. Veio com 3 fans que já dão conta do recado sem precisar trocar.', NOW() - INTERVAL 18 DAY),
(9, 2, 4, 'Gostei bastante. Só o painel frontal USB-C que não funciona na minha placa-mãe mais antiga, mas isso é detalhe da compatibilidade.', NOW() - INTERVAL 9 DAY),

-- Mouse Pad XXL (produto 10) — média ~4.6
(10, 5, 5, 'Cobre toda a mesa, o material é de alta qualidade e o mouse desliza perfeitamente. As bordas costuradas são bem feitas, sem desfiamento.', NOW() - INTERVAL 22 DAY),
(10, 3, 5, 'Comprei junto com o mouse e a combinação ficou perfeita. O RGB das bordas é suave e não ofusca. Vale muito.', NOW() - INTERVAL 15 DAY),
(10, 4, 4, 'Ótimo produto. Único ponto: demorou uns 2 dias para "descansar" e ficar completamente plano depois de tirar da embalagem.', NOW() - INTERVAL 8 DAY);
