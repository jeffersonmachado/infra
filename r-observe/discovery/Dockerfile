FROM node:20-alpine

RUN apk add --no-cache wget iputils bind-tools busybox-extras

WORKDIR /app

COPY package*.json ./
RUN npm ci --omit=dev && npm cache clean --force

COPY src/     ./src/
COPY tests/   ./tests/
COPY public/  ./public/

RUN addgroup -S observe && adduser -S observe -G observe \
  && mkdir -p /app/data \
  && chown -R observe:observe /app/data /app/src /app/tests package*.json

USER observe

EXPOSE 3010

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=5 \
  CMD wget -qO- http://127.0.0.1:3010/health | grep -q '"status":"ok"' || exit 1

CMD ["node", "--require", "./src/telemetry/tracing.js", "src/index.js"]
