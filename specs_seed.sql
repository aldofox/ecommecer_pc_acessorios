USE loja_gamer;

-- RTX 4070 Super (id=1)
UPDATE products SET specs = '[{"label":"Chipset","value":"NVIDIA GeForce RTX 4070 Super"},{"label":"Memoria","value":"12 GB GDDR6X"},{"label":"Barramento","value":"192-bit"},{"label":"Clock Boost","value":"2475 MHz"},{"label":"CUDA Cores","value":"7168"},{"label":"TDP","value":"220W"},{"label":"Saidas de Video","value":"3x DisplayPort 1.4a + 1x HDMI 2.1"},{"label":"Conector PCIe","value":"1x 16-pin (adaptador incluso)"},{"label":"Comprimento","value":"305 mm"},{"label":"Ray Tracing","value":"4a Geracao"},{"label":"DLSS","value":"Versao 3 (Frame Generation)"}]' WHERE id = 1;

-- Teclado Mecanico RGB (id=2)
UPDATE products SET specs = '[{"label":"Layout","value":"ABNT2 Portugues Brasil"},{"label":"Switches","value":"Mecanicos lineares (Red)"},{"label":"Anti-Ghosting","value":"N-Key Rollover total"},{"label":"Iluminacao","value":"RGB por tecla 16.8M cores"},{"label":"Estrutura","value":"Aluminio escovado"},{"label":"Cabo","value":"USB-C removivel 1.8m"},{"label":"Polling Rate","value":"1000 Hz"},{"label":"Teclas de midia","value":"Sim (Fn + F-row)"},{"label":"Peso","value":"1.05 kg"},{"label":"Dimensoes","value":"440 x 135 x 40 mm"}]' WHERE id = 2;

-- Monitor 27" 165Hz (id=3)
UPDATE products SET specs = '[{"label":"Tamanho","value":"27 polegadas"},{"label":"Resolucao","value":"2560 x 1440 (QHD 1440p)"},{"label":"Painel","value":"IPS"},{"label":"Taxa de Refresh","value":"165 Hz"},{"label":"Tempo de Resposta","value":"1 ms (MPRT)"},{"label":"HDR","value":"DisplayHDR 400"},{"label":"Cobertura sRGB","value":"99%"},{"label":"Brilho","value":"400 cd/m2"},{"label":"Entradas","value":"2x HDMI 2.0 + 1x DisplayPort 1.4"},{"label":"FreeSync","value":"Premium 48-165 Hz"},{"label":"VESA","value":"100 x 100 mm"}]' WHERE id = 3;

-- Mouse Gamer 16K DPI (id=4)
UPDATE products SET specs = '[{"label":"Sensor","value":"Optico PixArt PAW3395"},{"label":"DPI","value":"100 a 16000 DPI ajustavel"},{"label":"Botoes","value":"6 programaveis"},{"label":"Polling Rate","value":"1000 Hz"},{"label":"Peso","value":"68 g sem cabo"},{"label":"Cabo","value":"Paracord 1.8m USB-A"},{"label":"Switches","value":"Huano 60M cliques"},{"label":"Iluminacao","value":"RGB 1 zona"},{"label":"Compatibilidade","value":"Windows macOS Linux"},{"label":"Dimensoes","value":"125 x 65 x 38 mm"}]' WHERE id = 4;

-- Monitor Samsung Odyssey (id=6)
UPDATE products SET specs = '[{"label":"Tamanho","value":"49 polegadas"},{"label":"Resolucao","value":"5120 x 1440 (DQHD)"},{"label":"Curvatura","value":"1000R"},{"label":"Painel","value":"VA Mini LED"},{"label":"Taxa de Refresh","value":"240 Hz"},{"label":"Tempo de Resposta","value":"1 ms GtG"},{"label":"HDR","value":"DisplayHDR 1000"},{"label":"Brilho Pico","value":"1000 cd/m2"},{"label":"Entradas","value":"1x HDMI 2.1 + 2x DisplayPort 1.4 + USB Hub"},{"label":"FreeSync","value":"Premium Pro"},{"label":"Proporcao","value":"32:9 Super Ultrawide"}]' WHERE id = 6;

-- Processador Ryzen 7 (id=7)
UPDATE products SET specs = '[{"label":"Modelo","value":"AMD Ryzen 7 7700X"},{"label":"Nucleos Threads","value":"8 nucleos 16 threads"},{"label":"Clock Base","value":"4.5 GHz"},{"label":"Clock Boost","value":"5.4 GHz maximo"},{"label":"Cache L3","value":"32 MB"},{"label":"Socket","value":"AM5"},{"label":"TDP","value":"105W TDP 142W PPT"},{"label":"Fabricacao","value":"TSMC 5nm FinFET"},{"label":"PCIe","value":"5.0 GPU + 4.0 SSD"},{"label":"Memoria Suportada","value":"DDR5-5200 JEDEC ate DDR5-6000 OC"},{"label":"Cooler Incluso","value":"Nao (requer compra separada)"}]' WHERE id = 7;

-- Gabinete ATX Branco (id=8)
UPDATE products SET specs = '[{"label":"Formato","value":"Mid-Tower ATX"},{"label":"Compatibilidade","value":"ATX Micro-ATX Mini-ITX"},{"label":"Painel lateral","value":"Vidro temperado 4mm"},{"label":"Fans inclusos","value":"4x 120mm ARGB"},{"label":"Suporte Radiador","value":"360mm frontal 240mm topo"},{"label":"GPU maxima","value":"420 mm"},{"label":"Cooler maximo","value":"165 mm altura"},{"label":"Baias HD","value":"2x 3.5 polegadas + 2x 2.5 polegadas"},{"label":"IO Frontal","value":"1x USB-C + 2x USB-A 3.0 + audio P2"},{"label":"Cor","value":"Branco"},{"label":"Peso","value":"7.2 kg"}]' WHERE id = 8;

-- Gabinete ATX Preto (id=9)
UPDATE products SET specs = '[{"label":"Formato","value":"Mid-Tower ATX"},{"label":"Compatibilidade","value":"ATX Micro-ATX Mini-ITX"},{"label":"Painel lateral","value":"Vidro temperado 4mm"},{"label":"Fans inclusos","value":"3x 120mm ARGB"},{"label":"Suporte Radiador","value":"360mm frontal 240mm topo"},{"label":"GPU maxima","value":"400 mm"},{"label":"Cooler maximo","value":"160 mm altura"},{"label":"Baias HD","value":"2x 3.5 polegadas + 2x 2.5 polegadas"},{"label":"IO Frontal","value":"1x USB-C + 2x USB-A 3.0 + audio P2"},{"label":"Cor","value":"Preto"},{"label":"Peso","value":"6.9 kg"}]' WHERE id = 9;

-- Mouse Pad XXL (id=10)
UPDATE products SET specs = '[{"label":"Dimensoes","value":"900 x 400 x 4 mm"},{"label":"Material Superior","value":"Tecido de microfibra premium"},{"label":"Base","value":"Borracha antiderrapante"},{"label":"Bordas","value":"Costuradas para durabilidade"},{"label":"Iluminacao","value":"RGB nas bordas USB 5V"},{"label":"Superficie","value":"Controle e Speed mista"},{"label":"Resistencia","value":"Respingos de agua"},{"label":"Conexao RGB","value":"USB-A cabo 1.5m"},{"label":"Cor","value":"Preto com bordas RGB"}]' WHERE id = 10;
