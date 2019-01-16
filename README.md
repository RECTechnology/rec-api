REC Barcelona API
========================

Welcome to the REC Barcelona API 


# Development
## Install Docker
Install **docker** and **docker-compose** using the [official documentation](https://docker.com)

## Run API image only
```
docker build . -f Dockerfile.dev -t rec-api-dev
docker run -it -v `pwd`:/api -u $UID:$UID rec-api-dev <command>
```

## Run API with dependencies (MySQL, Mongodb, Node)
### Start services
```
docker-compose up
```
### Run some command like `cache:clear`
```
docker run -it -v `pwd`:/api -u $UID:$UID rec-api-dev <command>
```
### Admin databases and test
`PhpMyAdmin` instance is running on `localhost:8080` and the `API` is running on `localhost:8000`.

# Testing
Testing process ...

# Production
Production...
