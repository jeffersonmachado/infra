FROM python:3.12-alpine

RUN apk add --no-cache curl && pip install --no-cache-dir flask requests ldap3 pymysql cryptography gunicorn

RUN mkdir -p /app
COPY app.py /app/
COPY templates/ /app/templates/

WORKDIR /app
EXPOSE 5000

USER nobody
CMD ["gunicorn", "--bind", "0.0.0.0:5000", "--workers", "2", "--timeout", "60", "--access-logfile", "-", "app:app"]
