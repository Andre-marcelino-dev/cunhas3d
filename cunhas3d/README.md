# Cunhas 3D — Loja de impressões 3D

E-commerce completo em **Laravel 12 + MySQL + Docker**, com painel do administrador, área do cliente, carrinho, pagamento pelo **Mercado Pago** (Pix, cartão em até 12x e boleto) e visual moderno com **cenas 3D em Three.js** e animações GSAP.

---

## 1. Como rodar (passo a passo)

**Pré-requisito:** ter o [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado e aberto.

1. Descompacte a pasta do projeto.
2. Abra o terminal **dentro da pasta** do projeto.
3. Rode:

   ```bash
   docker compose up -d --build
   ```

4. Espere de 3 a 5 minutos na primeira vez (ele baixa o PHP, o MySQL, instala o Laravel e cria o banco).
   Para acompanhar: `docker compose logs -f app`. Quando aparecer **"Pronto!"**, acesse:

| O quê | Endereço |
|---|---|
| 🛍️ Loja | http://localhost:8000 |
| 🛠️ Painel admin | http://localhost:8000/admin |
| 🗄️ Banco de dados (phpMyAdmin) | http://localhost:8080 |

### Logins de teste

| Perfil | Onde entrar | Tabela | E-mail | Senha |
|---|---|---|---|---|
| Administrador | http://localhost:8000/admin/entrar | `admins` | `admin@cunhas3d.com.br` | `admin123` |
| Cliente | http://localhost:8000/entrar | `users` | `cliente@teste.com` | `cliente123` |

> Administradores e clientes ficam em tabelas e logins separados: um cliente nunca acessa o painel, e o admin não entra pela loja.

> ⚠️ Troque a senha do administrador antes de colocar o site no ar.

Para desligar: `docker compose down`.

---

## 2. Configurar o Mercado Pago

1. Entre em https://www.mercadopago.com.br/developers/panel/app e crie uma aplicação (tipo **Checkout Pro**).
2. Em **Credenciais de teste**, copie a *Public Key* e o *Access Token*.
3. Abra o arquivo `.env` (criado automaticamente na primeira execução) e preencha:

   ```env
   MP_PUBLIC_KEY=TEST-xxxxxxxx
   MP_ACCESS_TOKEN=TEST-xxxxxxxx
   MP_SANDBOX=true
   ```

4. Reinicie: `docker compose restart app`.
5. Faça uma compra no site. Para pagar no modo de teste, use os [cartões de teste do Mercado Pago](https://www.mercadopago.com.br/developers/pt/docs/checkout-pro/additional-content/your-integrations/test/cards) e entre com uma **conta de comprador de teste**.

### Webhook (confirmação automática de pagamento)

O Mercado Pago avisa o site quando um pagamento é aprovado, inclusive Pix e boleto pagos depois. Isso só funciona com o site num endereço público:

- **Em produção:** em *Suas integrações → Webhooks*, cadastre `https://SEU-DOMINIO/webhooks/mercadopago`, marque o evento **Pagamentos** e copie a **assinatura secreta** para `MP_WEBHOOK_SECRET` no `.env`.
- **No seu computador:** use o [ngrok](https://ngrok.com/) (`ngrok http 8000`) e coloque o endereço gerado em `APP_URL`.

Mesmo sem webhook, quando o cliente volta do Mercado Pago o site consulta o pagamento e atualiza o pedido. No painel também existe o botão **"Sincronizar com Mercado Pago"**.

Para ir para produção, troque as credenciais pelas de **produção** e use `MP_SANDBOX=false`.

---

## 3. O que o sistema tem

**Loja (cliente)**
- Home com cena 3D animada (peça sendo impressa camada por camada), animações no scroll e cards com efeito 3D
- Catálogo com filtros (categoria, material, preço), busca e ordenação
- Página de produto com **visualizador 3D interativo** (girar, zoom, trocar a cor) e suporte a arquivos `.glb`
- Carrinho (funciona sem login), barra de progresso para frete grátis
- Checkout com preenchimento automático do endereço pelo CEP (ViaCEP)
- Pagamento pelo Mercado Pago: Pix, cartão (12x), débito e boleto
- Área do cliente: pedidos, linha do tempo do pedido, rastreio, "pagar novamente", dados e senha

**Painel do administrador** (`/admin`)
- Dashboard: faturamento do dia e do mês, gráfico de 14 dias, mais vendidos, estoque baixo
- Categorias: criar, editar, excluir, categoria pai, cor e imagem
- Produtos: preço promocional, estoque, material, imagem principal, galeria, modelo 3D `.glb` com pré-visualização
- Pedidos: filtro por status, mudança de status, código de rastreio, histórico, sincronização com o Mercado Pago
- Clientes: lista com total gasto e quantidade de pedidos

**Regras de negócio e segurança**
- Preços sempre recalculados no servidor
- Estoque reservado ao criar o pedido e devolvido se ele for cancelado
- Pedidos não pagos são cancelados automaticamente depois de 3 dias (serviço `scheduler`)
- Webhook com validação de assinatura, consulta do status real na API e proteção contra processamento duplicado
- Proteção contra excesso de tentativas no login, no checkout e no webhook

---

## 4. Estrutura principal

```
app/
  Http/Controllers/        Loja, carrinho, checkout, webhook
  Http/Controllers/Admin/  Painel administrativo
  Http/Controllers/Account Área do cliente
  Models/                  Category, Product, Order, OrderItem...
  Services/                CartService, OrderService, MercadoPagoService
database/migrations        Tabelas
database/seeders           Dados de exemplo
resources/views            Telas (Blade + Tailwind)
resources/js/three         Cenas 3D (hero, visualizador, miniaturas)
docker/                    Configuração do PHP e do Nginx
```

## 5. Comandos úteis

| Comando | O que faz |
|---|---|
| `make up` / `make down` | Liga / desliga |
| `make logs` | Mostra os logs |
| `make seed` | Apaga e recria o banco com os dados de exemplo |
| `make assets` | Recompila o visual (CSS/JS) depois de mudar arquivos em `resources/` |
| `make shell` | Abre o terminal do container |

(No Windows sem `make`, use os comandos equivalentes que estão dentro do arquivo `Makefile`.)

## 6. Próximos passos sugeridos
- Frete calculado por CEP com a API do Melhor Envio (hoje: valor fixo configurável no `.env` + frete grátis acima de um valor)
- E-mails automáticos a cada mudança de status
- Cupons de desconto e avaliações de produtos
- Upload de STL para orçamento personalizado
