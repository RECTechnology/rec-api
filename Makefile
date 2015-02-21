
all: test doc

test:
	phpunit

doc:
	apidoc -i src/ -o build/doc/services
