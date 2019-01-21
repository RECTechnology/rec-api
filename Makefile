all: build

build:
	docker build . -t rec-api


deploy: build
	docker tag rec-api reg.rallf.com:8443/rec-api

dev:
	docker-compose up
