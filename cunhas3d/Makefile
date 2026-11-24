up:        ## Sobe todo o ambiente
	docker compose up -d --build

down:      ## Desliga o ambiente
	docker compose down

logs:      ## Mostra os logs da aplicação
	docker compose logs -f app

shell:     ## Abre um terminal dentro do container PHP
	docker compose exec app bash

migrate:   ## Roda as migrations
	docker compose exec app php artisan migrate

seed:      ## Recria o banco do zero com dados de exemplo
	docker compose exec app php artisan migrate:fresh --seed

assets:    ## Recompila CSS/JS
	docker compose run --rm node sh -c "npm install && npm run build"

dev:       ## Front com recarregamento automático (http://localhost:5173)
	docker compose run --rm -p 5173:5173 node sh -c "npm install && npm run dev"
