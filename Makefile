DOCKER_REGISTRY := reg.rallf.com:8443
DOCKER_IMAGE := rec-api
DOCKER_TAG := master
BUILD_DIR := .
DOCKERFILE_DIR := .

all: login build push deploy
dev: run

login:
	docker login -u $(DOCKER_USERNAME) -p $(DOCKER_PASSWORD) $(DOCKER_REGISTRY)

build:
	cd $(BUILD_DIR) && docker build . -f $(DOCKERFILE_DIR)/Dockerfile -t $(DOCKER_REGISTRY)/$(DOCKER_IMAGE):$(DOCKER_TAG)

push:
	docker push $(DOCKER_REGISTRY)/$(DOCKER_IMAGE):$(DOCKER_TAG)

deploy:
	WEBHOOK=$(WEBHOOK) ./deploy.sh

run:
	docker-compose -f docker/dev/docker-compose.yml up --build

ps:
	docker-compose -f docker/dev/docker-compose.yml ps

stop:
	docker-compose -f docker/dev/docker-compose.yml stop

exec:
	docker exec -it `docker-compose -f docker/dev/docker-compose.yml ps | grep "api" | awk '{print $$1}'` bash

down:
	docker-compose -f docker/dev/docker-compose.yml down