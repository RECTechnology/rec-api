
all: clean test doc

install:
	bash install.sh

test:
	phpunit

clean:
	rm -rf build

doc: clean-doc doc-api
	cp -r doc/web/* build/doc/

doc-api:
	cd doc && apidoc -i public -o ../build/doc/public
	cd doc && apidoc -i private -o ../build/doc/private
	cp web/favicon.ico build/doc/public/img
	cp web/favicon.ico build/doc/private/img
	cp doc/private/.htaccess build/doc/private

clean-doc:
	rm -rf build/doc