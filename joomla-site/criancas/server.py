#!/usr/bin/env python3
"""
Servidor HTTP simples para o jogo "Toque na Cor"
Execute este arquivo para iniciar o servidor local
"""

import http.server
import socketserver
import os
import webbrowser
from urllib.parse import urlparse

# Configurações do servidor
try:
    from config import SERVER_CONFIG, DEV_CONFIG, MESSAGES
    PORT = SERVER_CONFIG['port']
    AUTO_OPEN_BROWSER = SERVER_CONFIG['auto_open_browser']
    SHOW_IP_INFO = SERVER_CONFIG['show_ip_info']
    LOG_REQUESTS = DEV_CONFIG['log_requests']
    CACHE_CONTROL = DEV_CONFIG['cache_control']
except ImportError:
    # Configurações padrão se config.py não existir
    PORT = 8000
    AUTO_OPEN_BROWSER = True
    SHOW_IP_INFO = True
    LOG_REQUESTS = True
    CACHE_CONTROL = True
    MESSAGES = {
        'server_start': "🎮 SERVIDOR DO JOGO 'TOQUE NA COR' INICIADO!",
        'server_stop': "🛑 Servidor parado pelo usuário",
        'port_in_use': "❌ Erro: Porta {port} já está em uso!",
        'browser_open_failed': "⚠️  Não foi possível abrir o navegador automaticamente",
    }

DIRECTORY = os.path.dirname(os.path.abspath(__file__))

class CustomHTTPRequestHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=DIRECTORY, **kwargs)
    
    def end_headers(self):
        # Adicionar headers para melhor compatibilidade
        self.send_header('Access-Control-Allow-Origin', '*')
        if CACHE_CONTROL:
            self.send_header('Cache-Control', 'no-cache, no-store, must-revalidate')
            self.send_header('Pragma', 'no-cache')
            self.send_header('Expires', '0')
        super().end_headers()
    
    def log_message(self, format, *args):
        # Log personalizado
        if LOG_REQUESTS:
            print(f"[{self.log_date_time_string()}] {format % args}")

def start_server():
    """Inicia o servidor HTTP"""
    try:
        with socketserver.TCPServer(("", PORT), CustomHTTPRequestHandler) as httpd:
            print("=" * 60)
            print(MESSAGES['server_start'])
            print("=" * 60)
            print(f"📁 Diretório: {DIRECTORY}")
            print(f"🌐 URL Local: http://localhost:{PORT}")
            print(f"📱 URL Mobile: http://[SEU-IP]:{PORT}")
            print("=" * 60)
            print("📋 Instruções:")
            print("   • Abra http://localhost:8000 no navegador")
            print("   • Para acessar do celular, use o IP da sua rede")
            print("   • Pressione Ctrl+C para parar o servidor")
            print("=" * 60)
            
            # Tentar abrir o navegador automaticamente
            if AUTO_OPEN_BROWSER:
                try:
                    webbrowser.open(f'http://localhost:{PORT}')
                    print("🌐 Navegador aberto automaticamente!")
                except:
                    print(MESSAGES['browser_open_failed'])
                    print("   Abra manualmente: http://localhost:8000")
            
            print("\n🚀 Servidor rodando... Pressione Ctrl+C para parar")
            httpd.serve_forever()
            
    except KeyboardInterrupt:
        print(f"\n\n{MESSAGES['server_stop']}")
    except OSError as e:
        if e.errno == 48:  # Porta já em uso
            print(MESSAGES['port_in_use'].format(port=PORT))
            print("   Tente parar outros servidores ou mude a porta no config.py")
        else:
            print(f"❌ Erro ao iniciar servidor: {e}")
    except Exception as e:
        print(f"❌ Erro inesperado: {e}")

def get_local_ip():
    """Obtém o IP local da máquina"""
    import socket
    try:
        # Conecta a um endereço externo para descobrir o IP local
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except:
        return "127.0.0.1"

if __name__ == "__main__":
    # Mostrar informações de rede
    if SHOW_IP_INFO:
        local_ip = get_local_ip()
        print(f"🔍 IP Local: {local_ip}")
        print(f"📱 Para acessar do celular: http://{local_ip}:{PORT}")
        print()
    
    start_server() 