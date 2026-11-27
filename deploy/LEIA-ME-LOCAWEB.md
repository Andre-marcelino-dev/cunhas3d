# Publicar o Cunhas 3D na Locaweb

**Endereço:** https://cunhas3d.adminfo.dev.br
**Subdomínio no painel:** tipo *Conteúdo de pasta*, diretório raiz `/public_html/cunhas3d`

## O que tem neste pacote

O zip já vem montado igual à raiz do seu FTP:

```
/  (raiz do FTP da Locaweb)
├── cunhas3d-app/            ← ENVIAR: código do site (fica fora do public_html, protegido)
├── public_html/
│   ├── ...                  ← o que já existe (adminfo.dev.br). NÃO mexa.
│   └── cunhas3d/            ← ENVIAR: raiz do subdomínio (index.php, build/, storage/)
│
├── LEIA-ME-LOCAWEB.md       ← NÃO enviar (este guia)
└── banco-de-dados/          ← NÃO enviar: importar pelo phpMyAdmin (passo 2)
```

> Os nomes dos menus do painel da Locaweb podem mudar um pouco. Se não achar algo, procure pelo assunto, por exemplo "PHP" ou "MySQL".

---

## 1. Configurar o PHP

No painel da Locaweb, em **Hospedagem → Configurações / PHP**:
- Escolha **PHP 8.3** (ou 8.2, no mínimo).
- Confira se estas extensões estão ligadas: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `intl`, `zip`, `bcmath`, `exif`.

## 2. Criar o banco e importar o backup

1. Em **Banco de Dados → MySQL**, crie um banco. Anote o **host**, o **nome do banco**, o **usuário** e a **senha**.
2. Abra o **phpMyAdmin** da Locaweb, clique no banco criado → **Importar**.
3. Escolha `banco-de-dados/cunhas3d-...-locaweb.sql` e clique em **Executar**.
4. Devem aparecer **21 tabelas** (`products`, `product_variants`, `orders`, `users`, `admins`...).

## 3. Enviar os arquivos

**Opção A, a mais rápida (pelo Gerenciador de Arquivos do painel):**
1. Envie o `cunhas3d-locaweb.zip` para a **raiz** e use **Extrair**. As pastas `cunhas3d-app/` e `public_html/cunhas3d/` caem no lugar certo.
2. Apague do servidor o zip, a pasta `banco-de-dados/` e o `LEIA-ME-LOCAWEB.md`.

**Opção B (FTP, com o FileZilla):**
1. Envie a pasta `cunhas3d-app/` para a **raiz** do FTP.
2. Envie a pasta `public_html/cunhas3d/` para dentro do `public_html` do servidor.
3. A pasta `vendor` tem milhares de arquivos, então o envio pode demorar.

> Se a pasta `public_html/cunhas3d` do servidor tiver um `index.html` de boas-vindas da Locaweb, apague esse arquivo.

## 4. Configurar o `.env`

1. Em `cunhas3d-app/`, renomeie `.env.producao-modelo` para **`.env`**.
2. A `APP_KEY` e o `APP_URL` (https://cunhas3d.adminfo.dev.br) já vêm preenchidos. **Não troque nem compartilhe a `APP_KEY`.**
3. Troque o que estiver entre `< >`:
   - `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: os dados do passo 2
   - `MAIL_...`: uma conta de e-mail do painel da Locaweb
   - `MP_...`: as credenciais de **produção** do Mercado Pago (veja o passo 7)

## 5. Permissões

As pastas abaixo precisam permitir gravação (permissão **755**, ou **775** se der erro):
- `cunhas3d-app/storage/` (com as subpastas)
- `cunhas3d-app/bootstrap/cache/`
- `public_html/cunhas3d/storage/`, onde ficam as fotos enviadas pelo admin

No FileZilla, clique com o botão direito na pasta → **Permissões de arquivo** → marque **Aplicar a subdiretórios**.

## 6. Ativar o SSL e testar

1. **Espere o subdomínio sair de "Em instalação"** e ative o **Certificado SSL** no painel, até aparecer "Ativo".
   *O site usa cookies seguros: sem o cadeado (https), o login e o carrinho não funcionam.*
2. Ative o redirecionamento para HTTPS: edite `public_html/cunhas3d/.htaccess` e tire o `#` das duas linhas abaixo de *"Força HTTPS"*.
3. Confira:
   - [ ] https://cunhas3d.adminfo.dev.br abre com fotos
   - [ ] O **Anjo Luminária** mostra as opções com e sem vela LED
   - [ ] O login do admin funciona em https://cunhas3d.adminfo.dev.br/admin/entrar
   - [ ] O cadastro de produto com foto funciona no admin
   - [ ] https://cunhas3d.adminfo.dev.br/up mostra uma página de "funcionando"

Se aparecer **"500 | Server Error"**, veja o log em `cunhas3d-app/storage/logs/`. Erros comuns:
- *"Access denied for user"*: os dados do banco no `.env` estão errados.
- *"Permission denied"*: refaça o passo 5.
- *"Your Composer dependencies require a PHP version"*: troque para PHP 8.3 (passo 1).
- *"Failed to open stream ... vendor/autoload.php"*: a pasta `cunhas3d-app` não está na raiz do FTP, ao lado do `public_html`.

## 7. Depois de o site estar no ar

1. **Mercado Pago:**
   - Troque para as credenciais de **produção** e deixe `MP_SANDBOX=false`.
   - Em *Suas integrações → Webhooks*, cadastre `https://cunhas3d.adminfo.dev.br/webhooks/mercadopago`, marque **Pagamentos** e copie a assinatura secreta para `MP_WEBHOOK_SECRET`.
2. **Tarefa agendada** (cancela automaticamente os pedidos não pagos): no painel, em **Agendador de tarefas / Cron**, crie uma tarefa com o menor intervalo permitido:
   ```
   php /home/SEU_USUARIO/cunhas3d-app/artisan schedule:run
   ```
   O caminho completo aparece no Gerenciador de Arquivos ou no FTP.
3. **Senha do admin:** troque no painel do admin, em *Meu perfil*, para uma senha forte.

## Atualizar o site depois

Para publicar uma nova versão, gere um pacote novo e envie só o que mudou, normalmente `cunhas3d-app/app`, `cunhas3d-app/resources/views` e `public_html/cunhas3d/build`. **Nunca** sobrescreva o `.env` nem a pasta `public_html/cunhas3d/storage` do servidor: neles estão suas configurações e as fotos enviadas pelo admin.

> **Se o site já está no ar, NÃO importe o backup completo de novo** (o `cunhas3d-...-locaweb.sql`): ele apaga os pedidos, clientes e produtos reais. Rode só os arquivos `atualizacao-...sql` que ainda não rodou.

### Atualização de 27/11/2026: formulário de contato sobre produtos

1. **Banco:** no phpMyAdmin, abra o banco do site → aba **SQL** → cole o conteúdo de `banco-de-dados/atualizacao-2026-11-27-contato.sql` → **Executar**. Rode uma vez só.
2. **Arquivos:** envie por cima no servidor estas pastas do pacote (substituindo):
   - `cunhas3d-app/app`
   - `cunhas3d-app/routes`
   - `cunhas3d-app/resources/views`
   - `cunhas3d-app/database`
   - `public_html/cunhas3d/build`
3. **Limpar o cache das telas:** apague o conteúdo de `cunhas3d-app/storage/framework/views/` (só os arquivos de dentro, não a pasta). Se existirem, apague também `cunhas3d-app/bootstrap/cache/routes-v7.php` e `config.php`.
4. **Conferir:**
   - [ ] A página de um produto mostra "Ficou com alguma dúvida?" e o envio dá "Mensagem enviada!"
   - [ ] https://cunhas3d.adminfo.dev.br/contato abre
   - [ ] No admin aparece o menu **Mensagens** com a mensagem enviada

### Atualização de 27/11/2026: segurança

1. **Banco:** rode `banco-de-dados/atualizacao-2026-11-27-seguranca.sql` no phpMyAdmin (desativa a conta de teste `cliente@teste.com`).
2. **Arquivos:** envie por cima `cunhas3d-app/app`, `cunhas3d-app/config`, `cunhas3d-app/routes`, `cunhas3d-app/resources/views`, `cunhas3d-app/bootstrap/app.php` e `public_html/cunhas3d/.htaccess`.
3. **Cache:** apague os arquivos de `cunhas3d-app/storage/framework/views/` e, se existir, `cunhas3d-app/bootstrap/cache/config.php`.
4. **Conferir:** `http://cunhas3d.adminfo.dev.br` deve pular sozinho para `https://`. Se o site entrar em "redirecionamento em loop", volte o `#` nas 2 linhas de "Força HTTPS" do `.htaccess`.
