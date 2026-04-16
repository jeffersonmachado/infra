# Script PowerShell para iniciar o servidor do jogo "Toque na Cor"
# Execute este script no PowerShell

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "🎮 INICIANDO SERVIDOR DO JOGO 'TOQUE NA COR'" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Verificar se Python está instalado
try {
    $pythonVersion = python --version 2>&1
    Write-Host "✅ Python encontrado: $pythonVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Python não encontrado!" -ForegroundColor Red
    Write-Host ""
    Write-Host "📋 Para instalar o Python:" -ForegroundColor Yellow
    Write-Host "   1. Acesse: https://www.python.org/downloads/" -ForegroundColor White
    Write-Host "   2. Baixe e instale o Python 3.x" -ForegroundColor White
    Write-Host "   3. Execute este script novamente" -ForegroundColor White
    Write-Host ""
    Read-Host "Pressione Enter para sair"
    exit 1
}

Write-Host "🚀 Iniciando servidor..." -ForegroundColor Green
Write-Host ""

# Executar o servidor
try {
    python server.py
} catch {
    Write-Host "❌ Erro ao executar o servidor: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "🛑 Servidor parado." -ForegroundColor Yellow
Read-Host "Pressione Enter para sair" 