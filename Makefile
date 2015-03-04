
all: clean test doc

test:
	phpunit

clean:
	rm -rf build

doc: clean-doc doc-api
	cp -r doc/web/* build/doc/

doc-api:
	cd doc && apidoc -i . -o ../build/doc/api
	cp web/favicon.ico build/doc/api/img

clean-doc:
	rm -rf build/doc