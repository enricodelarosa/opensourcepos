refresh:
	npm run build; docker compose build ospos && docker compose up -d ospos
