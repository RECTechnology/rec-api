REC Barcelona API
========================

Welcome to the REC Barcelona API [api.rec.barcelona](https://api.rec.barcelona)

![CircleCI](https://circleci.com/gh/QbitArtifacts/rec-api/tree/containerize.svg?style=svg&circle-token=bd8b3154a46945cc5e5a128c12618beb70ba2e81)

|Installation|Web|API|User|Docs|WIki|Monitor|Admin|Explorer|
|------------|---|---|---|---|---|---|---|---|
|**Production**|[![Website](https://img.shields.io/website-up-down-green-red/https/rec.barcelona.svg?label=web)](https://rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/api.rec.barcelona/public/v1/status.svg?label=api)](https://api.rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/my.rec.barcelona.svg?label=user)](https://my.rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/dev.rec.barcelona.svg?label=dev)](https://dev.rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/wiki.rec.barcelona.svg?label=wiki)](https://wiki.rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/status.rec.barcelona.svg?label=monitor)](https://status.rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/admin.rec.barcelona.svg?label=admin)](https://admin.rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/explorer.rec.barcelona.svg?label=explorer)](https://explorer.rec.barcelona)|
|**Stage**|[![Website](https://img.shields.io/website-up-down-green-red/https/rec.qbitartifacts.com.svg?label=web)](https://rec.barcelona)|[![Website](https://img.shields.io/website-up-down-green-red/https/api.rec.qbitartifacts.com/public/v1/status.svg?label=api)](https://api.rec.qbitartifacts.com)|[![Website](https://img.shields.io/website-up-down-green-red/https/my.rec.qbitartifacts.com.svg?label=user)](https://my.rec.qbitartifacts.com)|[![Website](https://img.shields.io/website-up-down-green-red/https/dev.rec.qbitartifacts.com.svg?label=dev)](https://dev.rec.qbitartifacts.com)|[![Website](https://img.shields.io/website-up-down-green-red/https/wiki.rec.qbitartifacts.com.svg?label=wiki)](https://wiki.rec.qbitartifacts.com)|[![Website](https://img.shields.io/website-up-down-green-red/https/status.rec.qbitartifacts.com.svg?label=monitor)](https://status.rec.qbitartifacts.com)|[![Website](https://img.shields.io/website-up-down-green-red/https/admin.rec.qbitartifacts.com.svg?label=admin)](https://admin.rec.qbitartifacts.com)|[![Website](https://img.shields.io/website-up-down-green-red/https/explorer.rec.qbitartifacts.com.svg?label=explorer)](https://explorer.rec.qbitartifacts.com)|

# Development
## Setup
### Install Docker
Install **docker** and **docker-compose** using the [official documentation](https://docker.com)

### Run API image only
```
docker build . -f Dockerfile.dev -t rec-api-dev
docker run -it -v `pwd`:/api -u $UID:$UID rec-api-dev <command>
```
note that this method launches a new container with the code mounted, so it will only work if the command affects only to filesystem, if it has something to do with the database it will fail because the container cannot communicate with the database (for example command `app/console doctrine:schema:update --force` will not work)

### Run API with dependencies (MySQL, Mongodb, Node)
#### Start services
```
docker-compose up
```
#### Run some command in the `api` container
to see the list of running containers use `docker ps`

to run any command in the `api` container
```
docker exec -it api-api_api_1 bash
```

#### Admin databases and test
the services started with docker-compose are available at localhost in different ports
* `API` is running on `localhost:8000`
* `PhpMyAdmin` instance is running on `localhost:8080`
* `MongoAdmin` instance is running on `localhost:8081`

the rest of services haven't any port exposed to outside, but available inside the containers
* `mariadb` instance opens port `3306` to the container network
  - empty `root` password
  - database `api`
  - user `api`
  - password `api`
* `mongodb` instance opens port `27017` to the container network
* `rec node` instance opens port `17711` to the container network
  - rpcuser `rec`
  - rpcpassword `rec`

# Deployment
## Stage Environment
Stage environment is a [docker stack](https://docs.docker.com/get-started/part5/) using images from
`reg.rallf.com:8443/rec-*` using the `latest` tag. This environment is intended to be tested by the developers before
deploying the code to production.

* Testing stage for REC API is `https://api.rec.qbitartifacts.com`
* Testing stage for REC Admin is `https://admin.rec.qbitartifacts.com`
* Testing stage for REC Status is `https://status.rec.qbitartifacts.com`
* Testing stage for REC Explorer is `https://explorer.rec.qbitartifacts.com`
* Testing stage for REC User Panel is `https://my.rec.qbitartifacts.com`
* Testing stage for REC Developers documentation is `https://dev.rec.qbitartifacts.com`
* Testing stage for REC User documentation is `https://wiki.rec.qbitartifacts.com`

## Beta Environment
Not available yet.

## Production Environment
Prod environment is a [docker stack](https://docs.docker.com/get-started/part5/) using images from
`reg.rallf.com:8443/rec-*` using the `stable` tag. This environment is the final version, open to internet.

* Production for REC API is `https://api.rec.barcelona`
* Production for REC Admin is `https://admin.rec.barcelona`
* Production for REC Status is `https://status.rec.barcelona`
* Production for REC Explorer is `https://explorer.rec.barcelona`
* Production for REC User Panel is `https://my.rec.barcelona`
* Production for REC Developers documentation is `https://dev.rec.barcelona`
* Production for REC User documentation is `https://wiki.rec.barcelona`
