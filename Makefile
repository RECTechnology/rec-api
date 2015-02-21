
all: test doc

test:
	phpunit

clean:
	rm -rf build

doc: clean-doc doc-management doc-services doc-wallet
	cp -r doc/web/* build/doc/

doc-management:
	cd doc/management && apidoc -i ../../src/ -o ../../build/doc/api/management
	cp web/favicon.ico build/doc/api/management/img

doc-services:
	cd doc/services && apidoc -i ../../src/ -o ../../build/doc/api/services
	cp web/favicon.ico build/doc/api/services/img

doc-wallet:
	cd doc/wallet && apidoc -i ../../src/ -o ../../build/doc/api/wallet
	cp web/favicon.ico build/doc/api/wallet/img

clean-doc:
	rm -rf build/doc