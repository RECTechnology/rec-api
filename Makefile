
all: check-deps doc

check-deps:
	which apidoc

doc:
	apidoc -i src/ -o build/doc/services
