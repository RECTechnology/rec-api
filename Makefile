DOCKER_REGISTRY := reg.rallf.com:8443
DOCKER_IMAGE := rec-api
DOCKER_TAG := master
BUILD_DIR := .
DOCKERFILE_DIR := .
STACK_NAME := $(DOCKER_IMAGE)

all: login build push deploy
dev: run
shell: exec

login:
	if ! test -d $$HOME/.docker;then mkdir -p $$HOME/.docker; fi
	if ! test -f $$HOME/.docker/config.json;then echo '{"max-concurrent-uploads": 1}' > $$HOME/.docker/config.json; fi
	echo "$(DOCKER_PASSWORD)" | docker login -u $(DOCKER_USERNAME) --password-stdin $(DOCKER_REGISTRY)

build:
	cd $(BUILD_DIR) && docker build . -f $(DOCKERFILE_DIR)/Dockerfile -t $(DOCKER_REGISTRY)/$(DOCKER_IMAGE):$(DOCKER_TAG)

test:
	docker run `docker build -q . -f docker/test/Dockerfile` test

coverage:
	docker run `docker build -q . -f docker/test/Dockerfile` coverage

debug:
	docker-compose -f docker/dev/docker-compose.yml -p $(STACK_NAME) run api bash

push:
	docker push $(DOCKER_REGISTRY)/$(DOCKER_IMAGE):$(DOCKER_TAG)

deploy:
	WEBHOOK=$(WEBHOOK) ./deploy.sh

run:
	docker-compose -f docker/dev/docker-compose.yml -p $(STACK_NAME) up --build

ps:
	docker-compose -f docker/dev/docker-compose.yml -p $(STACK_NAME) ps

stop:
	docker-compose -f docker/dev/docker-compose.yml -p $(STACK_NAME) stop

exec:
	docker exec -it `docker-compose -f docker/dev/docker-compose.yml -p $(STACK_NAME) ps | grep "$(STACK_NAME)_api" | awk '{print $$1}'` bash

version:
	docker-compose -f docker/dev/docker-compose.yml -p $(STACK_NAME) run api standard-version

down:
	docker-compose -f docker/dev/docker-compose.yml -p $(STACK_NAME) down