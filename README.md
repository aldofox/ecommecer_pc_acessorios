# Loja Gamer

![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-2.4-D22128?logo=apache&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-dependencies-885630?logo=composer&logoColor=white)

Loja Gamer é uma aplicação de e-commerce desenvolvida em PHP e MySQL para venda de hardware, periféricos e acessórios. O projeto inclui uma área pública para clientes e um painel administrativo para gestão da loja.

## Funcionalidades

- Catálogo de produtos, categorias, busca e ofertas.
- Página de detalhes com especificações, avaliações e produtos relacionados.
- Carrinho, favoritos, cupons e montagem de setup.
- Cadastro e autenticação de clientes, checkout e acompanhamento de pedidos.
- Painel administrativo para produtos, estoque, banners, usuários, pedidos, relatórios e conteúdo do blog.
- Newsletter e notificações.
- Layout responsivo com tema claro e escuro.

> **Pagamentos:** Pix, boleto e cartão fazem parte do fluxo demonstrativo do projeto. Não há integração com um provedor de pagamentos; não use dados reais de cartão.

## Tecnologias

- PHP 8.0 ou superior, com PDO e extensão `pdo_mysql`.
- MySQL 8.0 ou superior.
- Apache 2.4 com PHP habilitado e `mod_rewrite`.
- Composer e PHPMailer para dependências e envio de e-mail.
- HTML, CSS e JavaScript; Swiper e Font Awesome são carregados por CDN.

## Executar localmente

### 1. Pré-requisitos

Instale e inicie Apache e MySQL, PHP com `pdo_mysql` e Composer. Configure o Apache para servir a pasta `public/` como raiz do site e permitir regras `.htaccess` (`AllowOverride All`).

Exemplo de VirtualHost:

```apache
<VirtualHost *:80>
    ServerName lojagamer.local
    DocumentRoot "C:/caminho/para/ecommercephp/public"

    <Directory "C:/caminho/para/ecommercephp/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

No Windows, adicione `127.0.0.1 lojagamer.local` ao arquivo `C:\Windows\System32\drivers\etc\hosts` (edite como administrador). Ajuste os caminhos do exemplo para o local onde clonou o projeto.

### 2. Instalar dependências

Na pasta do projeto, execute:

```bash
composer install
```

### 3. Criar e preparar o banco de dados

Importe o schema e os dados iniciais:

```bash
mysql -u root -p < logjagamer.sql
```

O código também utiliza os campos `compare_price` e `specs` na tabela `products`. Como eles não estão no schema principal, adicione-os antes de abrir o site:

```sql
USE loja_gamer;

ALTER TABLE products
    ADD COLUMN compare_price DECIMAL(10,2) NULL AFTER unit_price,
    ADD COLUMN specs JSON NULL AFTER description;
```

Para habilitar as tabelas auxiliares usadas por recursos adicionais, importe os scripts correspondentes, uma vez cada:

```bash
mysql -u root -p loja_gamer < new_tables.sql
mysql -u root -p loja_gamer < coupons.sql
mysql -u root -p loja_gamer < notifications.sql
mysql -u root -p loja_gamer < product_images.sql
```

O seed de produtos extras (`new_products_seed.sql`) e o de especificações (`specs_seed.sql`) são opcionais. Execute-os depois de preparar o schema; `specs_seed.sql` depende dos produtos já existentes. `reviews_seed.sql` depende de usuários com IDs de 2 a 7 — ajuste os IDs ou crie esses usuários antes de importá-lo.

### 4. Configurar a aplicação

Crie os arquivos locais de configuração a partir dos modelos:

```powershell
Copy-Item config/config.example.php config/config.php
Copy-Item config/mail.example.php config/mail.php
```

Edite `config/config.php` com o host, banco, usuário e senha do MySQL do seu ambiente. Atualize também `BASE_URL` para o endereço local escolhido, por exemplo `http://lojagamer.local`.

Para envio de e-mails, configure as credenciais SMTP em `config/mail.php`. Sem essa configuração, os recursos que enviam e-mail podem não funcionar. Os arquivos locais `config.php` e `mail.php` são ignorados pelo Git para evitar publicar credenciais.

O banco de exemplo inclui uma conta administrativa sem senha utilizável publicada. Antes do primeiro login, defina uma senha própria gerando um hash com PHP (`password_hash`) e atualizando o campo `password_hash` do usuário `admin@lojagamer.com`.

### 5. Abrir o site

Com Apache e MySQL ativos, acesse o `ServerName` configurado no VirtualHost, por exemplo:

```text
http://lojagamer.local/
```

## Estrutura do projeto

```text
config/       Configurações do banco e do SMTP
includes/     Componentes compartilhados e conexão com o banco
models/       Acesso a dados e regras de domínio
public/       Ponto de entrada web, páginas e painel administrativo
public/admin/ Páginas administrativas
*.sql         Schema, dados de exemplo e tabelas auxiliares
```

## Segurança e produção

- O schema inicial inclui uma conta administrativa de demonstração. Defina uma senha própria antes do primeiro login e remova ou proteja essa conta antes de disponibilizar o sistema.
- Não publique senhas de banco, credenciais SMTP ou outros segredos. Use credenciais próprias para cada ambiente.
- O fluxo de pagamento é demonstrativo e não deve ser usado para processar pagamentos reais.
- O projeto não inclui uma suíte automatizada de testes nem uma licença de distribuição definida.
