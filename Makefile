
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
	cp doc/web/favicon.ico build/doc/public/img
	cp doc/web/favicon.ico build/doc/private/img
	cp doc/private/.htaccess build/doc/private
	cp doc/web/js/* build/doc/public/vendor
	sed -i '11 i\  <script src="vendor/MagicAccessToken.js"></script>' build/doc/public/index.html
	cp doc/web/js/* build/doc/private/vendor
	sed -i '11 i\  <script src="vendor/MagicAccessToken.js"></script>' build/doc/private/index.html

clean-doc:
	rm -rf build/doc