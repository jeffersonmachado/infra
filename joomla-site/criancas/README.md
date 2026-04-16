# Toque na Cor - Jogo para Crianças

Um mini-jogo web educativo para crianças de até 3 anos aprenderem a reconhecer cores.

## 🎯 Objetivo

O jogo ajuda as crianças a desenvolverem o reconhecimento de cores através de uma experiência interativa e divertida.

## 📱 PWA (Progressive Web App)

Este jogo é uma **PWA completa** que pode ser instalada como um app nativo no seu dispositivo!

### ✨ Funcionalidades PWA:
- **📱 Instalável**: Pode ser instalado na tela inicial como um app
- **🔄 Offline**: Funciona completamente sem internet após o primeiro acesso
- **⚡ Rápido**: Carregamento instantâneo graças ao cache inteligente
- **🎨 Ícones**: Ícones personalizados em todos os tamanhos
- **📲 Responsivo**: Otimizado para todos os dispositivos
- **🔄 Atualizações**: Notifica sobre novas versões automaticamente

### 📥 Como Instalar:
1. **Chrome/Edge**: Clique no ícone de instalação na barra de endereços
2. **Safari**: Toque em "Compartilhar" → "Adicionar à Tela Inicial"
3. **Firefox**: Toque no ícone de instalação na barra de endereços
4. **Android**: O banner de instalação aparecerá automaticamente

## 🎮 Como Jogar

1. **Carregue o jogo**: Abra o arquivo `index.html` em qualquer navegador moderno
2. **Escute a instrução**: O jogo falará "Toque no [cor]!" usando a Web Speech API
3. **Toque na cor correta**: **Apenas toque** no círculo da cor solicitada (sem cliques do mouse)
4. **Acertos**: 
   - Som de comemoração
   - Som de festa de aniversário
   - Som de aplausos
   - Fala "Parabéns!"
   - Animação de confete
   - Contador de acertos aumenta
   - A cada 5 acertos, uma nova cor é adicionada (até 4 cores)
5. **Erros**: 
   - Som suave de "tente novamente"
   - Animação de shake no círculo tocado
   - Instrução é repetida em voz
6. **Repetição automática**: 
   - Após 10 segundos sem toque, o áudio repete automaticamente
   - Timer visual mostra quando o áudio vai repetir
   - Aviso visual nos últimos 3 segundos

**📱 Otimizado para toque**: O jogo é especialmente projetado para dispositivos com tela de toque (smartphones e tablets).

## 📁 Estrutura de Arquivos

```
crianas/
├── index.html              # Página principal do jogo
├── style.css               # Estilos e animações
├── script.js               # Lógica do jogo
├── server.py               # Servidor HTTP Python
├── manifest.json           # Configuração PWA
├── sw.js                   # Service Worker (cache offline)
├── generate_icons.py       # Script para gerar ícones
├── iniciar_servidor.bat    # Script para Windows
├── iniciar_servidor.ps1    # Script PowerShell
├── README.md               # Este arquivo
├── icons/                  # Ícones PWA
│   ├── icon-16x16.png
│   ├── icon-32x32.png
│   ├── icon-72x72.png
│   ├── icon-96x96.png
│   ├── icon-128x128.png
│   ├── icon-144x144.png
│   ├── icon-152x152.png
│   ├── icon-192x192.png
│   ├── icon-384x384.png
│   └── icon-512x512.png
└── sounds/
    ├── acerto.mp3              # Som de acerto (placeholder)
    ├── tente_novamente.mp3     # Som de erro (placeholder)
    ├── festa_aniversario.mp3   # Som de festa de aniversário (placeholder)
    └── aplausos.mp3            # Som de aplausos (placeholder)
```

## 🎨 Cores Disponíveis

- 🔴 Vermelho
- 🔵 Azul  
- 🟢 Verde
- 🟡 Amarelo
- 🟣 Roxo
- 🟠 Laranja

## 🚀 Como Executar

### Opção 1: Servidor Local (Recomendado)
1. **Instale o Python** (se não tiver): https://www.python.org/downloads/
2. **Execute o servidor**:
   - **Windows**: Clique duas vezes em `iniciar_servidor.bat`
   - **PowerShell**: Execute `.\iniciar_servidor.ps1`
   - **Terminal**: Execute `python server.py`
3. **Acesse**: http://localhost:8000 no navegador
4. **Para celular**: Use o IP mostrado no terminal (ex: http://192.168.1.100:8000)
5. **Instale como PWA**: Siga as instruções de instalação acima

### Opção 2: Arquivo Local
1. Baixe todos os arquivos para uma pasta
2. **Importante**: Substitua os arquivos de som por arquivos MP3 reais:
   - `sounds/acerto.mp3` - som de comemoração/positivo
   - `sounds/tente_novamente.mp3` - som suave de tentar novamente
   - `sounds/festa_aniversario.mp3` - som de festa de aniversário para comemoração
   - `sounds/aplausos.mp3` - som de aplausos para comemoração
3. Abra `index.html` no navegador
4. Permita o uso de áudio se solicitado

**💡 Dica**: Use o servidor local para melhor compatibilidade com áudio, Web Speech API e funcionalidades PWA!

## 📱 Compatibilidade

- ✅ Navegadores modernos (Chrome, Firefox, Safari, Edge)
- ✅ Dispositivos móveis (smartphones e tablets)
- ✅ Web Speech API para síntese de voz
- ✅ Touch events para dispositivos touchscreen
- ✅ PWA (Progressive Web App)
- ✅ Funcionamento offline
- ✅ Instalação como app nativo

## 🎵 Sons

Para obter arquivos de áudio gratuitos, visite:
- [Freesound](https://freesound.org/)
- [Mixkit](https://mixkit.co/free-sound-effects/)
- [Zapsplat](https://www.zapsplat.com/)

## 🔧 Características Técnicas

- **Sem dependências externas**: Apenas HTML, CSS e JavaScript puro
- **Otimizado para toque**: Apenas gestos de toque, sem cliques do mouse
- **Responsivo**: Adapta-se a diferentes tamanhos de tela
- **Acessível**: Alto contraste e fontes grandes
- **Performance**: Animações otimizadas para dispositivos móveis
- **Progressivo**: Dificuldade aumenta gradualmente
- **Área de toque otimizada**: Círculos com tamanho mínimo de 44px (padrão Apple)
- **PWA completa**: Cache offline, instalação, atualizações automáticas

## 🎯 Progressão do Jogo

1. **Início**: 2 cores (vermelho e azul)
2. **A cada 5 acertos**: Nova cor adicionada (até 4 cores)
3. **A cada 20 acertos**: Reinicia com 2 cores e mistura todas as cores

## 🐛 Solução de Problemas

- **Sem áudio**: Verifique se o navegador suporta Web Speech API
- **Sons não tocam**: Substitua os arquivos placeholder por MP3s reais
- **Não funciona no mobile**: Verifique se o dispositivo tem touchscreen
- **PWA não instala**: Verifique se está usando HTTPS ou localhost
- **Cache não funciona**: Verifique se o Service Worker está registrado

## 📄 Licença

Este projeto é de código aberto e pode ser usado livremente para fins educacionais. 