#!/bin/bash
# Monta o pacote do site para a Hospedagem de Sites da Locaweb (Apache, sem Docker).
# Rode dentro do container:  docker exec cunhas3d-app bash deploy/build-locaweb.sh
#
# O zip espelha a raiz do FTP da Locaweb:
#   cunhas3d-app/            -> código do Laravel (FORA do public_html, protegido)
#   public_html/cunhas3d/    -> raiz do subdomínio: index.php, CSS/JS, fotos
#   LEIA-ME-LOCAWEB.md e banco-de-dados/  -> só para você (não enviar ao servidor)
set -euo pipefail

# ---- Configuração da hospedagem -------------------------------------------
SITE_URL="https://cunhas3d.adminfo.dev.br"
APP_DIR="cunhas3d-app"                # pasta do código, na raiz do FTP
PUBLIC_DIR="public_html/cunhas3d"     # "Diretório raiz" do subdomínio no painel
# ---------------------------------------------------------------------------

SRC=/var/www
OUT=/tmp/cunhas3d-locaweb
ZIP=$SRC/deploy/cunhas3d-locaweb.zip
APP="$OUT/$APP_DIR"
PUB="$OUT/$PUBLIC_DIR"

# Caminho relativo da pasta pública até a pasta do código (ex.: ../../cunhas3d-app)
UP=$(echo "$PUBLIC_DIR" | sed -E 's#[^/]+#..#g')
PUBLIC_FROM_APP="../$PUBLIC_DIR"

rm -rf "$OUT" && mkdir -p "$APP" "$PUB"
cd "$SRC"

echo ">> Copiando o código para $APP_DIR/"
for item in app bootstrap config database lang resources routes artisan composer.json composer.lock; do
    cp -a "$item" "$APP/"
done
# Remove o que não vai para produção
rm -rf "$APP/database/backups" "$APP/resources/js" "$APP/resources/css" "$APP/bootstrap/cache/"*.php
find "$APP" -name '*:Zone.Identifier' -delete

echo ">> Criando a pasta storage (vazia, só a estrutura)"
for d in app/private framework/cache/data framework/sessions framework/views framework/testing logs; do
    mkdir -p "$APP/storage/$d"
done

echo ">> Instalando dependências de produção (sem pacotes de desenvolvimento)"
cd "$APP"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-progress --quiet
rm -f bootstrap/cache/*.php   # gerados de novo no servidor, com o .env de produção

echo ">> Montando $PUBLIC_DIR/"
cd "$SRC"
cp -a public/build public/.htaccess "$PUB/"
[ -f public/favicon.ico ] && cp public/favicon.ico "$PUB/"
[ -f public/robots.txt ] && cp public/robots.txt "$PUB/"

# Fotos e modelos 3D já enviados pelo admin vão direto para a pasta pública
mkdir -p "$PUB/storage"
cp -a storage/app/public/. "$PUB/storage/"
find "$PUB/storage" -name '.gitignore' -delete

# index.php apontando para a pasta do código, fora do public_html
cat > "$PUB/index.php" <<PHP
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Pasta do código do Laravel, na raiz da hospedagem (fora do acesso público)
\$app_path = __DIR__.'/$UP/$APP_DIR';

if (file_exists(\$maintenance = \$app_path.'/storage/framework/maintenance.php')) {
    require \$maintenance;
}

require \$app_path.'/vendor/autoload.php';

/** @var Application \$app */
\$app = require_once \$app_path.'/bootstrap/app.php';

// A pasta pública é esta (raiz do subdomínio), não a "public" do projeto
\$app->usePublicPath(__DIR__);

\$app->handleRequest(Request::capture());
PHP

echo ">> Modelo do .env de produção (já com APP_KEY nova e o endereço do site)"
APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
sed -e "s#<COLE_A_CHAVE_AQUI>#${APP_KEY}#" \
    -e "s#https://<SEU-DOMINIO.com.br>#${SITE_URL}#" \
    -e "s#^PUBLIC_DISK_ROOT=.*#PUBLIC_DISK_ROOT=${PUBLIC_FROM_APP}/storage#" \
    deploy/env.producao > "$APP/.env.producao-modelo"

echo ">> Guia de publicação e backup do banco"
cp deploy/LEIA-ME-LOCAWEB.md "$OUT/"
mkdir -p "$OUT/banco-de-dados"
cp "$(ls -t database/backups/*-locaweb.sql | head -1)" "$OUT/banco-de-dados/"
# Atualizações do banco para quem já está com o site no ar (rodar pelo phpMyAdmin, uma vez cada)
cp deploy/atualizacao-*.sql "$OUT/banco-de-dados/" 2>/dev/null || true

echo ">> Compactando"
rm -f "$ZIP"
php -r '
$src = $argv[1]; $zip = new ZipArchive();
$zip->open($argv[2], ZipArchive::CREATE | ZipArchive::OVERWRITE) or exit(1);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($it as $f) {
    $rel = substr($f->getPathname(), strlen($src) + 1);
    $f->isDir() ? $zip->addEmptyDir($rel) : $zip->addFile($f->getPathname(), $rel);
}
$zip->close();
' "$OUT" "$ZIP"

chown "$(stat -c %u:%g "$SRC/deploy")" "$ZIP"
echo ">> Pronto: $(du -h "$ZIP" | cut -f1)  deploy/cunhas3d-locaweb.zip"
echo "   arquivos: $(find "$OUT" -type f | wc -l)  |  fotos: $(find "$PUB/storage" -type f | wc -l)"
echo "   index.php -> $UP/$APP_DIR   |   PUBLIC_DISK_ROOT=${PUBLIC_FROM_APP}/storage"
rm -rf "$OUT"
