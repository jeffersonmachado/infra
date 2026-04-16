class ColorGame {
    constructor() {
        this.score = 0;
        this.currentColors = ['red', 'blue'];
        this.allColors = ['red', 'blue', 'green', 'yellow', 'purple', 'orange'];
        this.targetColor = '';
        this.gameArea = document.getElementById('game-area');
        this.scoreElement = document.getElementById('score');
        this.targetColorElement = document.getElementById('target-color');
        this.instructionElement = document.getElementById('instruction');
        this.confettiContainer = document.getElementById('confetti-container');
        this.touchIndicator = document.getElementById('touch-indicator');
        this.audioTimer = document.getElementById('audio-timer');
        this.timerCountdown = document.getElementById('timer-countdown');
        
        // Timer para repetir áudio
        this.audioRepeatTimer = null;
        this.countdownTimer = null;
        this.audioRepeatDelay = 10000; // 10 segundos (configurável)
        this.lastInteractionTime = Date.now();
        
        // Configurações de repetição de áudio
        this.audioSettings = {
            repeatDelay: 5000,  // 5 segundos
            showTimer: true,    // Mostrar timer visual
            warningTime: 2      // Aviso visual nos últimos 2 segundos
        };
        
        // Elementos de áudio
        this.acertoSound = new Audio('sounds/acerto.mp3');
        this.tenteNovamenteSound = new Audio('sounds/tente_novamente.mp3');
        this.festaSound = new Audio('sounds/festa_aniversario.mp3');
        this.aplausosSound = new Audio('sounds/aplausos.mp3');
        
        // Configurar Web Speech API
        this.speechSynthesis = window.speechSynthesis;
        this.utterance = new SpeechSynthesisUtterance();
        this.utterance.lang = 'pt-BR';
        this.utterance.rate = 0.8;
        this.utterance.pitch = 1.2;
        
        this.init();
    }
    
    init() {
        this.createColorCircles();
        this.setNewTarget();
        
        // Falar a primeira instrução imediatamente
        this.speakInstruction();
        
        // Garantir que a primeira instrução seja falada após o carregamento completo
        setTimeout(() => {
            if (!this.speechSynthesis.speaking) {
                console.log('🔄 Tentando falar primeira instrução novamente...');
                this.speakInstruction();
            }
        }, 500);
        
        // Mostrar indicador de toque se dispositivo suporta
        const isTouchDevice = (
            'ontouchstart' in window || 
            navigator.maxTouchPoints > 0 || 
            navigator.msMaxTouchPoints > 0 ||
            'onmsgesturechange' in window
        );
        
        if (isTouchDevice && this.touchIndicator) {
            this.touchIndicator.style.display = 'block';
        } else if (this.touchIndicator) {
            this.touchIndicator.style.display = 'none';
        }
    }
    
    createColorCircles() {
        this.gameArea.innerHTML = '';
        
        // Embaralhar as cores atuais
        const shuffledColors = [...this.currentColors].sort(() => Math.random() - 0.5);
        
        shuffledColors.forEach(color => {
            const circle = document.createElement('div');
            circle.className = `color-circle ${color}`;
            circle.dataset.color = color;
            
            // Eventos de toque completos
            circle.addEventListener('touchstart', (e) => this.handleColorClick(e), { passive: false });
            circle.addEventListener('touchend', (e) => e.preventDefault(), { passive: false });
            circle.addEventListener('touchmove', (e) => e.preventDefault(), { passive: false });
            
            // Prevenir cliques do mouse em dispositivos touch
            circle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
            
            // Prevenir eventos de mouse em dispositivos touch
            circle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
            
            this.gameArea.appendChild(circle);
        });
    }
    
    setNewTarget() {
        this.targetColor = this.currentColors[Math.floor(Math.random() * this.currentColors.length)];
        this.targetColorElement.textContent = this.getColorName(this.targetColor);
    }
    
    getColorName(color) {
        const colorNames = {
            'red': 'vermelho',
            'blue': 'azul',
            'green': 'verde',
            'yellow': 'amarelo',
            'purple': 'roxo',
            'orange': 'laranja'
        };
        return colorNames[color] || color;
    }
    
    speakInstruction() {
        // Verificar se a Web Speech API está disponível
        if (!this.speechSynthesis) {
            console.log('❌ Web Speech API não disponível');
            return;
        }
        
        // Verificar se já está falando
        if (this.speechSynthesis.speaking) {
            console.log('🔄 Já está falando, aguardando...');
            // Aguardar um pouco e tentar novamente
            setTimeout(() => {
                this.speakInstruction();
            }, 500);
            return;
        }
        
        this.utterance.text = `Toque no ${this.getColorName(this.targetColor)}!`;
        
        // Adicionar logs para debug
        console.log('🗣️ Falando:', this.utterance.text);
        
        this.speechSynthesis.speak(this.utterance);
        this.startAudioRepeatTimer();
    }
    
    speakCongratulations() {
        // Verificar se a Web Speech API está disponível
        if (!this.speechSynthesis) {
            return;
        }
        
        // Tocar som de festa de aniversário
        this.festaSound.play().catch(e => console.log('Erro ao tocar som de festa:', e));
        
        // Tocar som de aplausos com um pequeno delay
        setTimeout(() => {
            this.aplausosSound.play().catch(e => console.log('Erro ao tocar som de aplausos:', e));
        }, 500);
        
        // Criar uma nova utterance para "Parabéns!"
        const congratulationsUtterance = new SpeechSynthesisUtterance();
        congratulationsUtterance.text = 'Parabéns!';
        congratulationsUtterance.lang = 'pt-BR';
        congratulationsUtterance.rate = 0.8;
        congratulationsUtterance.pitch = 1.3; // Tom mais alto para comemoração
        
        console.log('🎉 Falando: Parabéns! + Som de festa + Aplausos!');
        
        this.speechSynthesis.speak(congratulationsUtterance);
    }
    
    startAudioRepeatTimer() {
        // Limpar timers anteriores
        if (this.audioRepeatTimer) {
            clearTimeout(this.audioRepeatTimer);
        }
        if (this.countdownTimer) {
            clearInterval(this.countdownTimer);
        }
        
        // Mostrar timer visual se habilitado
        if (this.audioSettings.showTimer && this.audioTimer) {
            this.audioTimer.style.display = 'block';
        }
        
        // Iniciar countdown visual
        let timeLeft = this.audioSettings.repeatDelay / 1000;
        if (this.timerCountdown) {
            this.timerCountdown.textContent = timeLeft;
        }
        
        this.countdownTimer = setInterval(() => {
            timeLeft--;
            if (this.timerCountdown) {
                this.timerCountdown.textContent = timeLeft;
                
                // Adicionar aviso visual nos últimos segundos
                if (timeLeft <= this.audioSettings.warningTime && this.audioTimer) {
                    this.audioTimer.classList.add('warning');
                }
            }
            
            if (timeLeft <= 0) {
                clearInterval(this.countdownTimer);
            }
        }, 1000);
        
        // Iniciar timer de repetição
        this.audioRepeatTimer = setTimeout(() => {
            console.log('🔄 Repetindo instrução por inatividade...');
            this.speakInstruction();
        }, this.audioSettings.repeatDelay);
    }
    
    resetAudioRepeatTimer() {
        this.lastInteractionTime = Date.now();
        
        // Limpar timers atuais
        if (this.audioRepeatTimer) {
            clearTimeout(this.audioRepeatTimer);
        }
        if (this.countdownTimer) {
            clearInterval(this.countdownTimer);
        }
        
        // Esconder timer visual
        if (this.audioTimer) {
            this.audioTimer.style.display = 'none';
            this.audioTimer.classList.remove('warning');
        }
        
        // Reiniciar timer
        this.startAudioRepeatTimer();
    }
    
    handleColorClick(event) {
        event.preventDefault();
        const circle = event.currentTarget;
        const clickedColor = circle.dataset.color;
        
        console.log('👆 Toque detectado:', {
            color: clickedColor,
            target: this.targetColor,
            correct: clickedColor === this.targetColor,
            eventType: event.type
        });
        
        // Resetar timer de repetição de áudio
        this.resetAudioRepeatTimer();
        
        // Feedback visual de toque
        circle.classList.add('touched');
        setTimeout(() => {
            circle.classList.remove('touched');
        }, 150);
        
        if (clickedColor === this.targetColor) {
            this.handleCorrectAnswer();
        } else {
            this.handleWrongAnswer(circle);
        }
    }
    
    handleCorrectAnswer() {
        // Tocar som de acerto
        this.acertoSound.play().catch(e => console.log('Erro ao tocar som:', e));
        
        // Mostrar confete
        this.showConfetti();
        
        // Atualizar pontuação
        this.score++;
        this.scoreElement.textContent = this.score;
        
        // Falar "Parabéns!" quando acertar
        this.speakCongratulations();
        
        // Verificar se deve adicionar nova cor
        if (this.score % 5 === 0 && this.currentColors.length < 4) {
            this.addNewColor();
        }
        
        // Verificar se deve reiniciar após 20 acertos
        if (this.score % 20 === 0) {
            this.resetColors();
        }
        
        // Criar nova rodada após um tempo
        setTimeout(() => {
            this.createColorCircles();
            this.setNewTarget();
            this.speakInstruction();
        }, 1500); // Aumentado para dar tempo de falar "Parabéns!"
    }
    
    handleWrongAnswer(circle) {
        // Tocar som de tentar novamente
        this.tenteNovamenteSound.play().catch(e => console.log('Erro ao tocar som:', e));
        
        // Animação de shake
        circle.classList.add('shake');
        setTimeout(() => {
            circle.classList.remove('shake');
        }, 500);
        
        // Repetir instrução
        setTimeout(() => {
            this.speakInstruction();
        }, 300);
    }
    
    addNewColor() {
        const availableColors = this.allColors.filter(color => !this.currentColors.includes(color));
        if (availableColors.length > 0) {
            const newColor = availableColors[Math.floor(Math.random() * availableColors.length)];
            this.currentColors.push(newColor);
        }
    }
    
    resetColors() {
        this.currentColors = ['red', 'blue'];
    }
    
    showConfetti() {
        const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff', '#ff8800'];
        
        for (let i = 0; i < 50; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
                
                this.confettiContainer.appendChild(confetti);
                
                // Remover confete após animação
                setTimeout(() => {
                    if (confetti.parentNode) {
                        confetti.parentNode.removeChild(confetti);
                    }
                }, 2000);
            }, i * 20);
        }
    }
}

// Inicializar o jogo quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    // Adicionar indicador de status offline
    const offlineIndicator = document.createElement('div');
    offlineIndicator.className = 'offline-indicator';
    offlineIndicator.textContent = '📡 Você está offline - Jogo funcionando localmente';
    document.body.appendChild(offlineIndicator);
    
    // Monitorar status de conectividade
    window.addEventListener('online', () => {
        offlineIndicator.classList.remove('show');
    });
    
    window.addEventListener('offline', () => {
        offlineIndicator.classList.add('show');
    });
    
    // Verificar se o dispositivo suporta toque (múltiplas formas)
    const isTouchDevice = (
        'ontouchstart' in window || 
        navigator.maxTouchPoints > 0 || 
        navigator.msMaxTouchPoints > 0 ||
        'onmsgesturechange' in window
    );
    
    console.log('🔍 Detecção de dispositivo:');
    console.log('  - ontouchstart:', 'ontouchstart' in window);
    console.log('  - maxTouchPoints:', navigator.maxTouchPoints);
    console.log('  - msMaxTouchPoints:', navigator.msMaxTouchPoints);
    console.log('  - É dispositivo touch:', isTouchDevice);
    
    if (!isTouchDevice) {
        // Mostrar aviso para dispositivos sem toque
        const warning = document.createElement('div');
        warning.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #ff6b6b;
            color: white;
            padding: 10px;
            text-align: center;
            font-size: 16px;
            z-index: 10000;
            font-weight: bold;
        `;
        warning.textContent = '⚠️ Este jogo é otimizado para dispositivos com tela de toque. Use um smartphone ou tablet para melhor experiência.';
        document.body.appendChild(warning);
        
        // Remover aviso após 5 segundos
        setTimeout(() => {
            if (warning.parentNode) {
                warning.parentNode.removeChild(warning);
            }
        }, 5000);
    } else {
        console.log('✅ Dispositivo touch detectado - toque habilitado!');
    }
    
    // Inicializar o jogo apenas após clicar no botão de início
    const startGameBtn = document.getElementById('start-game-btn');
    const startScreen = document.getElementById('start-screen');
    const gameScreen = document.getElementById('game-screen');
    
    if (startGameBtn) {
        startGameBtn.addEventListener('click', () => {
            console.log('🎮 Iniciando jogo...');
            
            // Esconder tela de início e mostrar jogo
            startScreen.style.display = 'none';
            gameScreen.style.display = 'block';
            
            // Inicializar o jogo após a interação do usuário
            if ('speechSynthesis' in window) {
                window.gameInstance = new ColorGame();
            } else {
                alert('Seu navegador não suporta síntese de voz. O jogo funcionará sem áudio.');
                window.gameInstance = new ColorGame();
            }
        });
    }
});

// Pausar fala quando a página perder foco
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        window.speechSynthesis.cancel();
    }
});

// Botão de teste de áudio
document.addEventListener('DOMContentLoaded', () => {
    const audioTestBtn = document.getElementById('audio-test-btn');
    if (audioTestBtn) {
        audioTestBtn.addEventListener('click', () => {
            console.log('🔊 Testando áudio...');
            if (window.gameInstance) {
                window.gameInstance.speakInstruction();
            }
        });
    }
}); 