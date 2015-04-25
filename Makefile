
all: clean test doc

install:
	bash symfony_install.sh

test:
	phpunit

clean:
	rm -rf build

doc: clean-doc doc-api
	cp -r doc/web/* build/doc/

doc-api:
    bash doc_install.sh
	cd build/tmp && apidoc -i public -o ../build/doc/public
	cd build/tmp && apidoc -i private -o ../build/doc/private
	cp build/tmp/web/favicon.ico build/doc/public/img
	cp build/tmp/web/favicon.ico build/doc/private/img
	cp build/tmp/private/.htaccess build/doc/private
	cp build/tmp/web/js/* build/doc/public/vendor
	sed -i '11 i\  <script src="vendor/MagicAccessToken.js"></script>' build/doc/public/index.html
	cp build/tmp/web/js/* build/doc/private/vendor
	sed -i '11 i\  <script src="vendor/MagicAccessToken.js"></script>' build/doc/private/index.html

clean-doc:
	rm -rf build/doc