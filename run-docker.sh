#!/bin/sh

docker-compose kill && \
docker-compose build && \
docker-compose up -d --remove-orphans && \
docker-compose logs --follow web
