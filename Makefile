#!make

# import environnement file
DOCKER_PATH = ./docker
include .env
-include .env.*.local

CONTAINER_APP = ${PROJET_NAME}-${APP_NAME}
CONTAINER_DB = ${PROJET_NAME}-${DB_NAME}
DB_USER = ${DB_USER}
DB_PWD = ${DB_PASSWORD}
DB_NAME = ${DB_NAME}

DOCKER_EXEC_APP = docker exec -it $(CONTAINER_APP)
DOCKER_EXEC_DB = docker exec -i $(CONTAINER_DB)
DOCKER_COMPOSE_ARGS = -f $(DOCKER_PATH)/docker-compose.yml
DOCKER_ENV_ARGS = --env-file=.env --env-file=.env.${APP_ENV}.local
DOCKER_COMPOSE_FULL_COMMAND = docker compose ${DOCKER_COMPOSE_ARGS} ${DOCKER_ENV_ARGS}

help:                 ## Show this help.
	@fgrep -h "##" $(firstword $(MAKEFILE_LIST)) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'
.PHONY: help

# # Docker

docker-build:         ## build images
	${DOCKER_COMPOSE_FULL_COMMAND} build
.PHONY: docker-build

docker-up:            ## up containers
	${DOCKER_COMPOSE_FULL_COMMAND} up -d
.PHONY: docker-up

docker-down:          ## down containers
	${DOCKER_COMPOSE_FULL_COMMAND} down
.PHONY: docker-down

docker-exec-app:      ## enter app container
	$(DOCKER_EXEC_APP) bash
.PHONY: docker-exec

docker-exec-db:       ## enter db container
	$(DOCKER_EXEC_DB) bash
.PHONY: docker-exec

ci:                   ## composer install
	$(DOCKER_EXEC_APP) composer install
.PHONY: ci

c-me:                 ## bin/console make:entity
	$(DOCKER_EXEC_APP) php bin/console m:e
.PHONY: entity

c-mm:                 ## bin/console make:migration
	$(DOCKER_EXEC_APP) php bin/console make:migration
.PHONY: c-mm

c-dmm:                ## bin/console doctrine:migration:migrate
	$(DOCKER_EXEC_APP) php bin/console d:m:m
.PHONY: c-dmm

c-dsu:                ## bin/console doctrine:schema:update
	$(DOCKER_EXEC_APP) symfony console d:s:u
.PHONY: c-dsu

c-cc:                 ## bin/console doctrine:schema:update
	$(DOCKER_EXEC_APP) symfony console cache:clear
.PHONY: c-cc

s-server-start:       ## symfony serve:start -d
	$(DOCKER_EXEC_APP) symfony serve:start  --port=80 -d
.PHONY: s-server-start

server-stop:          ## symfony serve:stop
	$(DOCKER_EXEC_APP) symfony serve:stop
.PHONY: server-stop

dump-file:            ## make a dump of database
	$(DOCKER_EXEC_DB) mysql -u $(USER_DB) -p$(PWD_DB) $(DB_NAME)
.PHONY: dump-file