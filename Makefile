DOC_TMP_DIR=build/doc/tmp

all: clean test doc

install:
	bash symfony_install.sh

reload:
	bash symfony_reload.sh

test:
	phpunit

clean:
	rm -rf build

doc: clean-doc doc-api
	cp -r $(DOC_TMP_DIR)/web/* build/doc/

doc-api:
	bash doc_install.sh
	cd $(DOC_TMP_DIR) && apidoc -i public -o ../../doc/public
	cd $(DOC_TMP_DIR) && apidoc -i private -o ../../doc/private
	rm build/doc/public/img/favicon.*
	cp $(DOC_TMP_DIR)/private/.htaccess build/doc/private
	cp $(DOC_TMP_DIR)/web/js/* build/doc/public/vendor
	sed -i '11 i\  <script src="vendor/MagicAccessToken.js"></script>' build/doc/public/index.html
	cp $(DOC_TMP_DIR)/web/js/* build/doc/private/vendor
	sed -i '11 i\  <script src="vendor/MagicAccessToken.js"></script>' build/doc/private/index.html

clean-doc:
	rm -rf build/doc