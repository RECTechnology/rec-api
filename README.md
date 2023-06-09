REC Barcelona API
========================

Welcome to the REC Barcelona API [api.rec.barcelona](https://api.rec.barcelona)

![Functional Tests](https://github.com/QbitArtifacts/rec-api/workflows/Functional%20Tests/badge.svg)
[![codecov](https://codecov.io/gh/QbitArtifacts/rec-api/branch/master/graph/badge.svg?token=IXN0HR8A68)](https://codecov.io/gh/QbitArtifacts/rec-api)


|Installation|Web|API|POS|Docs|Admin|Backups|
|------------|---|---|---|----|-----|-------|
|**Production**|[![Website](https://img.shields.io/website-up-down-green-red/https/rec.barcelona.svg?label=web)](https://rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/api.rec.barcelona/public/v1/status.svg?label=api)](https://api.rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/pos.rec.barcelona.svg?label=pos)](https://pos.rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/dev.rec.barcelona.svg?label=dev)](https://dev.rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/admin.rec.barcelona.svg?label=admin)](https://admin.rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/backups.rec.barcelona.svg?label=backups)](https://backups.rec.barcelona)|
|**Stage**|[![Website](https://img.shields.io/website-up-down-green-red/https/rec.qbitartifacts.com.svg?label=web)](https://rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/api.rec.qbitartifacts.com/public/v1/status.svg?label=api)](https://api.rec.qbitartifacts.com)|[![Website](https://img.shields.io/website-up-down-green-red/https/pos.rec.qbitartifacts.com.svg?label=pos)](https://pos.rec.barcelona.qbitartifacts.com)|[![Website](https://img.shields.io/website-up-down-green-red/https/dev.rec.qbitartifacts.com.svg?label=dev)](https://dev.rec.qbitartifacts.com)|[![Website](https://img.shields.io/website-up-down-green-red/https/admin.rec.qbitartifacts.com.svg?label=admin)](https://admin.rec.qbitartifacts.com)|[![Website](https://img.shields.io/website-up-down-green-red/https/backups.rec.qbitartifacts.com.svg?label=backups)](https://backups.rec.qbitartifacts.com)|

# Development

[![Conventional Commits](https://img.shields.io/badge/Conventional%20Commits-1.0.0-yellow.svg)](https://conventionalcommits.org)

## Setup
### Install Docker
Install **docker** and **docker-compose** using the [official documentation](https://docs.docker.com/install/).
Make sure having added your user to the `docker` group to avoid trouble with permissions, see [docs](https://docs.docker.com/install/linux/linux-postinstall/)

### Run API with dependencies (MySQL, Mongodb, Node)
#### Start services
Using **GNU Make** (need `autotools` installed, recommended)
```
make dev
```
Using docker directly
```
docker-compose -f docker/dev/docker-compose.yml up --build
```
#### Stop/Down services
Using **GNU Make** (recommended)
```
make down
```

Using docker directly
```
docker-compose -f docker/dev/docker-compose.yml down
```

#### Run some command in the `api` container
to see the list of running containers use `make ps`

to run any command in the `api` container (recommended)
```
make shell
```

Or using docker

to see the list of running containers use `docker ps`

to run any command in the `api` container
```
docker exec -it rec-api_api_1 bash
```

### Run API image only (troubleshooting)
Using **GNU Make** (recommended)
```
make debug 
```

Using docker directly
```
docker build . -f docker/dev/Dockerfile -t rec-api-dev
docker run -it -v `pwd`:/api -u $UID:$UID rec-api-dev <command>
```
note that this method launches a new container with the code mounted, so it will only work if the command affects only to filesystem, if it has something to do with the database it will fail because the container cannot communicate with the database (for example command `app/console doctrine:schema:update --force` will not work)

#### Admin databases and test
the services started with docker-compose are available at localhost in different ports
* `API` is running on `localhost:8000`
* `API Docs` is running on `localhost:8001`
* `PhpMyAdmin` instance is running on `localhost:8080`
* `MongoAdmin` instance is running on `localhost:8081`

the rest of services haven't got any port exposed to outside, but available inside the containers
* `mariadb` instance opens port `3306` to the container network
  - empty `root` password
  - database `api`
  - user `api`
  - password `api`
* `mongodb` instance opens port `27017` to the container network
  - root user `api`
  - root password `api`
  - database and collections not created.
* `rec node` instance opens port `17711` to the container network
  - rpcuser `rec`
  - rpcpassword `rec`

#### Troubleshooting
to deal with troubleshooting is possible to acces to the runningcontainer's shell using 
`docker exec -it dev_api_1 bash` (explained [above](#run-some-command-in-the-api-container)),
but if the container doesn't start (ie. missing dependencies), the method must be to executing
directly the built image using `docker run -it -v `pwd`:/api -u $UID:$UID rec-api-dev bash` (also
explained [above](#run-api-image-only)).

##### Known Issues
* mongodb-odm fail to composer install/update: solution here https://stackoverflow.com/a/36987971


# Deployment
## Stage Environment
Stage environment is a [docker stack](https://docs.docker.com/get-started/part5/) using images from
`reg.rallf.com:8443/rec-*` using the `latest` tag. This environment is intended to be tested by the developers before
deploying the code to production.

* Testing stage for REC API is `https://api.rec.qbitartifacts.com`
* Testing stage for REC Admin is `https://admin.rec.qbitartifacts.com`
* Production for REC POS is `https://pos.rec.qbitartifacts.com`
* Testing stage for REC Developers documentation is `https://dev.rec.qbitartifacts.com`

## Beta Environment
Not available yet.

## Production Environment
Prod environment is a [docker stack](https://docs.docker.com/get-started/part5/) using images from
`reg.rallf.com:8443/rec-*` using the `stable` tag. This environment is the final version, open to internet.

* Production for REC API is `https://api.rec.barcelona`
* Production for REC Admin is `https://admin.rec.barcelona`
* Production for REC POS is `https://pos.rec.barcelona`
* Production for REC Developers documentation is `https://dev.rec.barcelona`
