@echo off
chcp 65001 >nul
title Servidor do Jogo "Toque na Cor"

echo.
echo ============================================================
echo 🎮 INICIANDO SERVIDOR DO JOGO "TOQUE NA COR"
echo ============================================================
echo.

REM Verificar se Python está instalado
python --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Python não encontrado!
    echo.
    echo 📋 Para instalar o Python:
    echo    1. Acesse: https://www.python.org/downloads/
    echo    2. Baixe e instale o Python 3.x
    echo    3. Execute este arquivo novamente
    echo.
    pause
    exit /b 1
)

echo ✅ Python encontrado!
echo 🚀 Iniciando servidor...
echo.

REM Executar o servidor
python server.py

echo.
echo 🛑 Servidor parado.
pause 