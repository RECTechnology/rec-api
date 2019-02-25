PROJECT_NAME := rec-api
BRANCH_NAME  := $(shell git rev-parse --abbrev-ref HEAD)

all: build-all
deploy: deploy-all

build-all: build-cron build-api
deploy-all: deploy-api deploy-cron

build-api:
	docker build . -f docker/prod/api/Dockerfile -t reg.rallf.com:8443/$(PROJECT_NAME):$(BRANCH_NAME)
build-cron:
	docker build . -f docker/prod/cron/Dockerfile -t reg.rallf.com:8443/rec-cron:$(BRANCH_NAME)


deploy-api: build-api
	docker push reg.rallf.com:8443/$(PROJECT_NAME):$(BRANCH_NAME)
deploy-cron: build-cron
	docker push reg.rallf.com:8443/rec-cron:$(BRANCH_NAME)

dev:
	docker-compose up --build
status:
	docker-compose ps
stop:
	docker-compose stop
