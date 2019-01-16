REC Barcelona API
========================

Welcome to the REC Barcelona API 


# Development
## Setup
### Install Docker
Install **docker** and **docker-compose** using the [official documentation](https://docker.com)

### Run API image only
```
docker build . -f Dockerfile.dev -t rec-api-dev
docker run -it -v `pwd`:/api -u $UID:$UID rec-api-dev <command>
```

### Run API with dependencies (MySQL, Mongodb, Node)
#### Start services
```
docker-compose up
```
#### Run some command like `cache:clear`
to see the list of running containers use `docker ps`

to run any command in the `api` container
```
docker exec -it api-api_api_1 bash
```

#### Admin databases and test
`PhpMyAdmin` instance is running on `localhost:8080` and the `API` is running on `localhost:8000`.

# Deployment
## Stage Environment
Stage environment is a [docker stack](https://docs.docker.com/get-started/part5/) using images from
`reg.rallf.com:8443/rec-*` using the `latest` tag. This environment is intended to be tested by the developers before
deploying the code to production.

* Testing stage for REC API is `https://api.rec.stage.qbitartifacts.com`
* Testing stage for REC Admin is `https://admin.rec.stage.qbitartifacts.com`
* Testing stage for REC Status is `https://status.rec.stage.qbitartifacts.com`
* Testing stage for REC Explorer is `https://explorer.rec.stage.qbitartifacts.com`
* Testing stage for REC User Panel is `https://my.rec.stage.qbitartifacts.com`

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
