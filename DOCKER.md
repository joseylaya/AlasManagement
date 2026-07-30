# Local Docker setup

The local app is served at `http://localhost:8080`.

```bash
npm run docker:up
docker compose exec php php artisan migrate
docker compose exec php php artisan db:seed
```

Use `npm run docker:logs` to follow the application logs and `npm run docker:down` to stop the stack.

The SQLite database and Laravel storage are mounted from the local project, so they remain available when containers restart. Set `HTTP_PORT` in `.env` if port `8080` is already in use.

## Production deployment

Use the production configuration rather than the local development stack:

```bash
cp .env.example .env
touch database/database.sqlite
sudo chown -R 82:82 database storage
sudo docker compose -f compose.production.yaml up -d --build
sudo docker compose -f compose.production.yaml exec php php artisan key:generate --force
sudo docker compose -f compose.production.yaml exec php php artisan migrate --force
```

The production stack serves HTTP on port 80 for `jmgaming.site` and `www.jmgaming.site`. It intentionally does not seed the sample accounts, because their default passwords are unsuitable for a public server.
