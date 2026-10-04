@echo off
cd /d "%~dp0"
docker compose exec app php bin/create-admin.php
pause
