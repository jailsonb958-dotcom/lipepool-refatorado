@echo off
cd /d "%~dp0"
if not exist .env copy /Y docker.env.example .env >nul
docker compose up --build
