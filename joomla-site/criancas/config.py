# Configurações do servidor do jogo "Toque na Cor"
# Modifique estas configurações conforme necessário

# Configurações do servidor
SERVER_CONFIG = {
    'port': 8100,                    # Porta do servidor
    'host': '',                      # Host (vazio = todos os IPs)
    'auto_open_browser': True,       # Abrir navegador automaticamente
    'show_ip_info': True,           # Mostrar informações de IP
}

# Configurações de desenvolvimento
DEV_CONFIG = {
    'debug': False,                  # Modo debug
    'log_requests': True,           # Log de requisições
    'cache_control': True,          # Headers de cache
}

# Configurações de rede
NETWORK_CONFIG = {
    'timeout': 30,                   # Timeout da conexão
    'max_connections': 10,          # Máximo de conexões simultâneas
}

# Mensagens personalizadas
MESSAGES = {
    'server_start': "🎮 SERVIDOR DO JOGO 'TOQUE NA COR' INICIADO!",
    'server_stop': "🛑 Servidor parado pelo usuário",
    'port_in_use': "❌ Erro: Porta {port} já está em uso!",
    'python_not_found': "❌ Python não encontrado!",
    'browser_open_failed': "⚠️  Não foi possível abrir o navegador automaticamente",
}

# Cores para o terminal (Windows)
TERMINAL_COLORS = {
    'success': 'green',
    'error': 'red',
    'warning': 'yellow',
    'info': 'cyan',
    'title': 'magenta',
} 