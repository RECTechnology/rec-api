
all: test doc

test:
	phpunit

doc: doc-management doc-services doc-wallet

doc-management:
	cd doc/management && apidoc -i ../../src/ -o ../../build/doc/management

doc-services:
	cd doc/services && apidoc -i ../../src/ -o ../../build/doc/services

doc-wallet:
	cd doc/wallet && apidoc -i ../../src/ -o ../../build/doc/wallet
