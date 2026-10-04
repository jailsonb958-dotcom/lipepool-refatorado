@echo off
cd /d "%~dp0"
docker compose exec app composer test
pause
