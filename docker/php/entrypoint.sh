#!/bin/sh
set -e

cd /var/www

if [ "$RUN_SETUP" = "1" ]; then
    # Primeira execução: cria o .env e instala as dependências
    if [ ! -f .env ]; then
        echo ">> Criando .env a partir do .env.example"
        cp .env.example .env
    fi

    if [ ! -f vendor/autoload.php ]; then
        echo ">> Instalando dependências do Composer (pode levar alguns minutos)"
        composer install --no-interaction --prefer-dist
    fi

    if ! grep -q "^APP_KEY=base64" .env; then
        php artisan key:generate --force
    fi

    echo ">> Rodando migrations"
    php artisan migrate --force

    # Popula o banco só se ainda não houver produtos
    if [ ! -f storage/app/.seeded ]; then
        echo ">> Populando o banco com dados de exemplo"
        php artisan db:seed --force && touch storage/app/.seeded
    fi

    php artisan storage:link 2>/dev/null || true

    chown -R www-data:www-data storage bootstrap/cache
    chmod -R ug+rwX storage bootstrap/cache
    echo ">> Pronto! Acesse http://localhost:8000"
fi

exec "$@"
