# 🎨 Toque na Cor - HTML Puro

Jogo educativo para crianças aprenderem cores através do toque, funcionando **100% no navegador** sem necessidade de servidor!

## 🚀 Como Usar (Método Simples)

### **Opção 1: Arquivo Direto (Mais Fácil)**
1. **Baixe todos os arquivos** para uma pasta
2. **Clique duas vezes** no arquivo `index.html`
3. **Pronto!** O jogo abre no navegador

### **Opção 2: Servidor Local (Recomendado para PWA)**
1. **Instale o Python**: https://www.python.org/downloads/
2. **Execute**: `python -m http.server 8000`
3. **Acesse**: http://localhost:8000

### **Opção 3: Live Server (VS Code)**
1. **Instale a extensão** "Live Server" no VS Code
2. **Clique com botão direito** no `index.html`
3. **Selecione** "Open with Live Server"

## 📱 Funcionalidades

### **🎮 Jogo Principal:**
- **Toque nas cores** conforme solicitado
- **Áudio falado** em português
- **Progressão automática** de dificuldade
- **Comemoração completa** ao acertar

### **🎵 Sons de Comemoração:**
- Som de acerto
- Som de festa de aniversário
- Som de aplausos
- Fala "Parabéns!"

### **📱 PWA (Progressive Web App):**
- **Instalável** como app nativo
- **Funciona offline** após primeiro acesso
- **Ícones personalizados**
- **Tela de início** com instruções

## 📁 Estrutura de Arquivos

```
📦 Toque na Cor/
├── 📄 index.html              # Página principal
├── 🎨 style.css               # Estilos e animações
├── ⚙️ script.js               # Lógica do jogo
├── 📋 manifest.json           # Configuração PWA
├── 🔧 sw.js                   # Service Worker (cache)
├── 🎨 generate_icons.py       # Gerador de ícones
├── 📱 icons/                  # Ícones PWA
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
└── 🎵 sounds/                 # Arquivos de áudio
    ├── acerto.mp3
    ├── tente_novamente.mp3
    ├── festa_aniversario.mp3
    └── aplausos.mp3
```

## 🎯 Como Jogar

1. **Abra o jogo** no navegador
2. **Clique em "Começar Jogo"**
3. **Escute** a instrução falada
4. **Toque** na cor correta
5. **Celebre** os acertos!

## 🎨 Cores Disponíveis

- 🔴 **Vermelho**
- 🔵 **Azul**
- 🟢 **Verde**
- 🟡 **Amarelo**
- 🟣 **Roxo**
- 🟠 **Laranja**

## 📱 Compatibilidade

### **✅ Navegadores Suportados:**
- Chrome (recomendado)
- Firefox
- Safari
- Edge

### **✅ Dispositivos:**
- Smartphones (Android/iOS)
- Tablets
- Computadores

### **✅ Funcionalidades:**
- Web Speech API (áudio)
- Touch events (toque)
- PWA (instalação)
- Cache offline

## 🎵 Arquivos de Áudio

**Importante:** Substitua os arquivos placeholder por MP3s reais:

- `sounds/acerto.mp3` - Som de comemoração
- `sounds/tente_novamente.mp3` - Som de erro
- `sounds/festa_aniversario.mp3` - Som de festa
- `sounds/aplausos.mp3` - Som de aplausos

### **Sites para Baixar Sons Gratuitos:**
- [Freesound](https://freesound.org/)
- [Mixkit](https://mixkit.co/free-sound-effects/)
- [Zapsplat](https://www.zapsplat.com/)

## 🔧 Personalização

### **Cores do Jogo:**
Edite o arquivo `script.js` na linha com `this.allColors`

### **Tempo de Repetição:**
Edite `this.audioSettings.repeatDelay` no `script.js`

### **Ícones:**
Execute `python3 generate_icons.py` para gerar novos ícones

## 🐛 Solução de Problemas

### **Áudio não funciona:**
- Verifique se o navegador suporta Web Speech API
- Clique no botão "🔊 Testar Áudio"
- Permita o uso de áudio se solicitado

### **PWA não instala:**
- Use HTTPS ou localhost
- Clique nos botões de instalação
- Verifique se o Service Worker está registrado

### **Toque não funciona:**
- Use um dispositivo com tela de toque
- Verifique se o navegador suporta touch events

## 📄 Licença

Este projeto é de código aberto e pode ser usado livremente para fins educacionais.

## 🎉 Recursos Educacionais

- **Desenvolvimento cognitivo** de cores
- **Coordenação motora** através do toque
- **Aprendizado auditivo** com síntese de voz
- **Feedback positivo** com comemorações
- **Progressão gradual** de dificuldade

---

**Divirta-se aprendendo cores! 🎨👶** 