@echo off
docker compose -p nossa-plataforma exec -T ^
  -e APP_ENV=testing ^
  -e DB_CONNECTION=sqlite ^
  -e DB_DATABASE=:memory: ^
  -e DB_URL= ^
  backend ^
  php -d memory_limit=512M artisan test %*

exit /b %ERRORLEVEL%