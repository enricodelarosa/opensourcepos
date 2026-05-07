refresh:
	npm run build; docker compose build ospos && docker compose up -d ospos && docker image prune -f

docker-cache-trim:
	docker builder prune -f --filter until=72h
