FROM node:20-alpine

# curl para SSL checks; openssl para inspeção de cert
RUN apk add --no-cache wget curl openssl

WORKDIR /app

COPY package*.json ./
RUN npm ci --omit=dev && npm cache clean --force

COPY src/ ./src/

RUN addgroup -S observe && adduser -S observe -G observe \
    && chown -R observe:observe /app

# Usuário com permissão para ler docker.sock (GID 999 = docker group padrão)
# O volume /var/run/docker.sock é montado como :ro no compose
USER observe

EXPOSE 3000

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=5 \
  CMD wget -qO- http://127.0.0.1:3000/health | grep -q '"status":"ok"' || exit 1

CMD ["node", "--require", "./src/tracing.js", "src/index.js"]
