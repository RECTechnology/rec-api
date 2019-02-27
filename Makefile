PROJECT_NAME := rec-api
BRANCH_NAME  := $(shell git rev-parse --abbrev-ref HEAD)

all: build-all
push: push-all

build-all: build-cron build-api
push-all: push-api push-cron

build-api:
	docker build . -f docker/prod/api/Dockerfile -t reg.rallf.com:8443/$(PROJECT_NAME):$(BRANCH_NAME)
build-cron:
	docker build . -f docker/prod/cron/Dockerfile -t reg.rallf.com:8443/rec-cron:$(BRANCH_NAME)


push-api: build-api
	docker push reg.rallf.com:8443/$(PROJECT_NAME):$(BRANCH_NAME)
push-cron: build-cron
	docker push reg.rallf.com:8443/rec-cron:$(BRANCH_NAME)

dev:
	docker-compose up --build
status:
	docker-compose ps
stop:
	docker-compose stop

deploy: push-all
	./deployer.sh