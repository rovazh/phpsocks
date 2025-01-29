FROM alpine:latest

RUN apk add --no-cache dante-server

RUN apk add --no-cache php

RUN apk add --no-cache nodejs npm

COPY danted.conf /etc/sockd.conf

CMD ["sockd", "-f", "/etc/sockd.conf"]
