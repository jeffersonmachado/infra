#!/usr/bin/env python3
"""
Script para gerar ícones PWA para o jogo "Toque na Cor"
Gera ícones em diferentes tamanhos com design simples e colorido
"""

from PIL import Image, ImageDraw, ImageFont
import os

def create_icon(size, filename):
    """Criar ícone com círculos coloridos representando o jogo"""
    
    # Criar imagem com fundo branco
    img = Image.new('RGB', (size, size), 'white')
    draw = ImageDraw.Draw(img)
    
    # Calcular posições dos círculos
    margin = size // 8
    circle_size = size // 4
    
    # Cores do jogo
    colors = ['#ff4444', '#4444ff', '#44ff44', '#ffff44']  # vermelho, azul, verde, amarelo
    
    # Desenhar círculos coloridos
    positions = [
        (margin + circle_size//2, margin + circle_size//2),  # superior esquerdo
        (size - margin - circle_size//2, margin + circle_size//2),  # superior direito
        (margin + circle_size//2, size - margin - circle_size//2),  # inferior esquerdo
        (size - margin - circle_size//2, size - margin - circle_size//2)  # inferior direito
    ]
    
    for i, (x, y) in enumerate(positions):
        color = colors[i % len(colors)]
        draw.ellipse([
            x - circle_size//2, 
            y - circle_size//2, 
            x + circle_size//2, 
            y + circle_size//2
        ], fill=color, outline='#333333', width=max(1, size//100))
    
    # Adicionar borda
    draw.rectangle([0, 0, size-1, size-1], outline='#4CAF50', width=max(2, size//50))
    
    # Salvar ícone
    img.save(filename, 'PNG')
    print(f"✅ Ícone criado: {filename} ({size}x{size})")

def main():
    """Função principal para gerar todos os ícones"""
    
    # Criar diretório de ícones se não existir
    icons_dir = 'icons'
    if not os.path.exists(icons_dir):
        os.makedirs(icons_dir)
        print(f"📁 Diretório criado: {icons_dir}")
    
    # Tamanhos de ícones necessários para PWA
    sizes = [16, 32, 72, 96, 128, 144, 152, 192, 384, 512]
    
    print("🎨 Gerando ícones PWA...")
    
    for size in sizes:
        filename = f"{icons_dir}/icon-{size}x{size}.png"
        create_icon(size, filename)
    
    print("\n🎉 Todos os ícones foram gerados com sucesso!")
    print("📱 O jogo agora está pronto para ser instalado como PWA")

if __name__ == "__main__":
    main() 