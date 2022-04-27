
STACK := rec-api
DOCKER_IMAGE := rec-api
DOCKER_TAG := master
BUILD_DIR := .
DOCKERFILE_DIR := .

dev:
	@docker-compose -f docker/dev/docker-compose.yml -p $(STACK) up --build

down:
	@docker-compose -f docker/dev/docker-compose.yml -p $(STACK) down

ps:
	@docker-compose -f docker/dev/docker-compose.yml -p $(STACK) ps

shell:
	@docker-compose -f docker/dev/docker-compose.yml -p $(STACK) exec $(word 2,$(MAKECMDGOALS)) bash

debug:
	@docker-compose -f docker/dev/docker-compose.yml -p $(STACK) run $(word 2,$(MAKECMDGOALS)) bash

login:
	if ! test -d $$HOME/.docker;then mkdir -p $$HOME/.docker; fi
	if ! test -f $$HOME/.docker/config.json;then echo '{"max-concurrent-uploads": 1}' > $$HOME/.docker/config.json; fi
	echo "$(DOCKER_PASSWORD)" | docker login -u $(DOCKER_USERNAME) --password-stdin $(DOCKER_REGISTRY)

build:
	cd $(BUILD_DIR) && docker build . -f $(DOCKERFILE_DIR)/Dockerfile -t $(DOCKER_REGISTRY)/$(DOCKER_IMAGE):$(DOCKER_TAG)

push:
	docker push $(DOCKER_REGISTRY)/$(DOCKER_IMAGE):$(DOCKER_TAG)

build-test:
	docker build -q . -f docker/test/Dockerfile -t $(STACK):test

test: build-test
	docker run $(STACK):test test

coverage: build-test
	docker run --name $(STACK)-test $(STACK):test coverage
	docker cp $(STACK)-test:/api/coverage.xml .

%:
	@:
