# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

### [2.14.1](https://github.com/qbitartifacts/rec-api/compare/v2.14.0...v2.14.1) (2020-03-20)


### Bug Fixes

* **dependencies:** updated dependencies and removed deprecated bundle ([b0846d5](https://github.com/qbitartifacts/rec-api/commit/b0846d5fb6bcf8cbf0408fa179665e57b6727e91))
* **documents:** fixes [#250](https://github.com/qbitartifacts/rec-api/issues/250) ([e309ebb](https://github.com/qbitartifacts/rec-api/commit/e309ebbeb384f6aeaffd36fe98292628bb2fcb18))

## [2.14.0](https://github.com/qbitartifacts/rec-api/compare/v2.13.4...v2.14.0) (2020-03-20)


### Features

* **lw_docs:** if status is auto_fetched and conent is modified, set automatically status based on lemon_status ([e2b7743](https://github.com/qbitartifacts/rec-api/commit/e2b774300424ab6062af9097cc524ef5ced62aff))
* **stateful:** added multiple initial statuses ([b170964](https://github.com/qbitartifacts/rec-api/commit/b1709645f3e8addffb5b010707121e2e35836ff7))


### Bug Fixes

* **lw_docs:** set initial status auto_fetched ([bd8af70](https://github.com/qbitartifacts/rec-api/commit/bd8af7078ff3707b5a32e40205d4e3351d0723bb))

### [2.13.4](https://github.com/qbitartifacts/rec-api/compare/v2.13.3...v2.13.4) (2020-03-20)


### Bug Fixes

* **lemon_doctypes:** flushed db for every lemon doctype created to avoid duplicates ([e44f730](https://github.com/qbitartifacts/rec-api/commit/e44f730540f3f68e6265308820e9bacce6f937db))
* **tiers:** fixed document_kinds was empty, fixes [#248](https://github.com/qbitartifacts/rec-api/issues/248) ([81e084f](https://github.com/qbitartifacts/rec-api/commit/81e084f86813085ad129e5bdca30cf2b476baa19))

### [2.13.3](https://github.com/qbitartifacts/rec-api/compare/v2.13.2...v2.13.3) (2020-03-20)


### Bug Fixes

* **lemonway:** fixed bug in lemon uploader ([61bce3d](https://github.com/qbitartifacts/rec-api/commit/61bce3d53bc4f9f3a6422ba4f5b29b37dd0721b3))

### [2.13.2](https://github.com/qbitartifacts/rec-api/compare/v2.13.1...v2.13.2) (2020-03-20)


### Bug Fixes

* **sync:** dummy fix, repository was wrong ([6536a7d](https://github.com/qbitartifacts/rec-api/commit/6536a7de0c549e4bf9ae1e8b61775e94545090a9))

### [2.13.1](https://github.com/qbitartifacts/rec-api/compare/v2.13.0...v2.13.1) (2020-03-20)


### Bug Fixes

* **lemonway:** only set iban status if exists in our database, to avoid errors ([7066348](https://github.com/qbitartifacts/rec-api/commit/7066348d92d7f423c8aebda68b1d35ac42ef05d3))

## [2.13.0](https://github.com/qbitartifacts/rec-api/compare/v2.12.1...v2.13.0) (2020-03-20)


### Features

* **lemonway:** implemented auto-fetch for lemon doctypes ([b3018c3](https://github.com/qbitartifacts/rec-api/commit/b3018c3130683f25527f70a9568b4330cf8da126))

### [2.12.1](https://github.com/qbitartifacts/rec-api/compare/v2.12.0...v2.12.1) (2020-03-20)


### Bug Fixes

* **documents:** sets document.content nullable to allow auto-fetch docs from lemon ([4dfe462](https://github.com/qbitartifacts/rec-api/commit/4dfe46200a3f3ad57ba9edbd11971fc039362489))

## [2.12.0](https://github.com/qbitartifacts/rec-api/compare/v2.11.6...v2.12.0) (2020-03-20)


### Features

* **documents:** set accepted status for non-lemonway documents ([38188b5](https://github.com/qbitartifacts/rec-api/commit/38188b55816c1c55996e404977cb7cbd0205108f))
* **migrations:** added migration for valid_until date in documents ([d0e322d](https://github.com/qbitartifacts/rec-api/commit/d0e322d97a553f166d5655d5514e3a78af743587))


### Bug Fixes

* **crud:** refactored more deleters ([987027f](https://github.com/qbitartifacts/rec-api/commit/987027fd08b66df15543282e79b7a2c59ed893ab))
* **db_relationship:** changed db relationship tier <-> documentkind to ManyToMany, fixes [#195](https://github.com/qbitartifacts/rec-api/issues/195) ([e71603a](https://github.com/qbitartifacts/rec-api/commit/e71603a77c9af106d49abc263214e99304591c14))
* **issue:** added valid_until date to documents, fixes [#194](https://github.com/qbitartifacts/rec-api/issues/194) ([0e43d3d](https://github.com/qbitartifacts/rec-api/commit/0e43d3d17aa27450c63d7058579317b670688798))
* **issue:** fixes [#230](https://github.com/qbitartifacts/rec-api/issues/230) ([3f9da13](https://github.com/qbitartifacts/rec-api/commit/3f9da13a00d419875adc15e865ee59a9ff3ffb1a))
* **migrations:** added migration to set all accounts to country=ESP, fixes [#237](https://github.com/qbitartifacts/rec-api/issues/237) ([7e81af9](https://github.com/qbitartifacts/rec-api/commit/7e81af99b27d6ca6a58a87b12bcd4f0d85c2a6c5))
* **migrations:** added ondelete set null to allow tier deletions ([16815e2](https://github.com/qbitartifacts/rec-api/commit/16815e2e89f8ab327febf29fc1a506237fdd0f90))
* **testing:** refactored tests and fixed delete status codes ([0c06314](https://github.com/qbitartifacts/rec-api/commit/0c0631480616759a60730639410a4abbd5535509))
* **tests:** refactored more tests ([c7b0887](https://github.com/qbitartifacts/rec-api/commit/c7b0887c9b72ac2ab38679e3873dda0f79ddba70))
* **tiers:** added ondelete set null to allow tier deletions ([eef61ec](https://github.com/qbitartifacts/rec-api/commit/eef61ecdca80ac9b50db22040e023da473e6d4dd))
* **tiers:** changed tier-tier relationship to 1-n ([50e1a9c](https://github.com/qbitartifacts/rec-api/commit/50e1a9c05f38d3c1edb98f6a82f055c7fd7312c4))
* **tiers:** introduced migration to allow delete self-referencing tiers ([7ce07b2](https://github.com/qbitartifacts/rec-api/commit/7ce07b2f6f4495776728a3271f9fe43ac115dcc8))
* **tiers:** removed next from tiers, use previous instead ([cf107c8](https://github.com/qbitartifacts/rec-api/commit/cf107c833d96373d4e48bd2760c10f5f4f1566ac))
* **valid_until:** fixed setter ([08e8eea](https://github.com/qbitartifacts/rec-api/commit/08e8eea664306b859e33d40946e28f70e9eaf5be))

### [2.11.6](https://github.com/qbitartifacts/rec-api/compare/v2.11.4-1...v2.11.6) (2020-03-12)


### Bug Fixes

* **max_depth:** added more max depth checks to Group.php ([e2f348b](https://github.com/qbitartifacts/rec-api/commit/e2f348bfd0a098b59202d449838142f61be00d7c))
* **max_depth:** added more max depth checks to Group.php ([a4254d8](https://github.com/qbitartifacts/rec-api/commit/a4254d80e55324b7dc27a633f4c8d2b837bbffd4))
* **max_depth:** fixed bug when creating context ([b40bffb](https://github.com/qbitartifacts/rec-api/commit/b40bffb8d8e98696ff44a1aaf6c9080d0d555ad8))

### [2.11.5](https://github.com/qbitartifacts/rec-api/compare/v2.11.4...v2.11.5) (2020-03-11)


### Bug Fixes

* **issue:** fixes [#221](https://github.com/qbitartifacts/rec-api/issues/221) ([985bc09](https://github.com/qbitartifacts/rec-api/commit/985bc09b4216302596cdf0346fe0c39d9a580e84))

### [2.11.4](https://github.com/qbitartifacts/rec-api/compare/v2.11.3...v2.11.4) (2020-03-10)


### Bug Fixes

* **ibans:** fixed lemon iban parse response ([b905846](https://github.com/qbitartifacts/rec-api/commit/b905846edc42c6d1d15eb22517c73d03f5fb43d4))
* **tests:** fixed tests for lemon iban parse response ([5d95484](https://github.com/qbitartifacts/rec-api/commit/5d95484946b2474f5455eb1c4911a5cf8685f21e))

### [2.11.3](https://github.com/qbitartifacts/rec-api/compare/v2.11.2...v2.11.3) (2020-03-09)


### Bug Fixes

* **lemonway:** added nullability to lemon data and external data ([1b52c32](https://github.com/qbitartifacts/rec-api/commit/1b52c32d6fe1a2c0143323b6d2f8e0d7ae2ac29a))
* **lemonway:** auto-fetched documents can't be uploaded again ([2aa3b49](https://github.com/qbitartifacts/rec-api/commit/2aa3b490c82e8bf3b2440d17256932f6ba427036))

### [2.11.2](https://github.com/qbitartifacts/rec-api/compare/v2.11.1...v2.11.2) (2020-03-09)


### Bug Fixes

* **lemonway:** changed type for setExternalInfo to allow objects and not only arrays ([a34fecd](https://github.com/qbitartifacts/rec-api/commit/a34fecdcf0feb68d783e0da5e2e542d420fc35e5))

### [2.11.1](https://github.com/qbitartifacts/rec-api/compare/v2.11.0...v2.11.1) (2020-03-09)


### Bug Fixes

* **ibans:** missed iban in lemonway call ([058cd7c](https://github.com/qbitartifacts/rec-api/commit/058cd7cdfef8cc523a2da45b7e13d398531ae0c2))

## [2.11.0](https://github.com/qbitartifacts/rec-api/compare/v2.10.0...v2.11.0) (2020-03-09)


### Features

* **testing:** implemented mongodb testing ([956e00e](https://github.com/qbitartifacts/rec-api/commit/956e00eadcdc63f86aa4b236a3b0970d5de60708))


### Bug Fixes

* **dependencies:** changed abandoned package sensio/generator-bundle to symfony/maker-bundle ([9ccc331](https://github.com/qbitartifacts/rec-api/commit/9ccc3318b0718be741101eec021ffe6a4854385b))
* **dummy:** added max depth checks ([3ec709a](https://github.com/qbitartifacts/rec-api/commit/3ec709a8360e7ce118c3b80b238d0b23a7030fb7))
* **gitignore:** ignored core dumps ([2d7d028](https://github.com/qbitartifacts/rec-api/commit/2d7d0286de8de3029ac21a0a09f2d9acf346653c))
* **ibans:** minor change in error message from LW ([f4163ca](https://github.com/qbitartifacts/rec-api/commit/f4163cafaeb6e517e850120581eb1df776e373a6))
* **lemonway:** added auto-naming for lw documentation ([1552a44](https://github.com/qbitartifacts/rec-api/commit/1552a443f0400e6e1a2e8afd8da36e09fcfbf3e8))
* **lemonway:** fixed lemonway documents synchronization ([027a277](https://github.com/qbitartifacts/rec-api/commit/027a277281d501790f9fe769f37dba894491aa8d))
* **lemonway:** fixes [#190](https://github.com/qbitartifacts/rec-api/issues/190) ([1224398](https://github.com/qbitartifacts/rec-api/commit/1224398ddc13db0bdd173f57312f08c9611bc8b4))
* **lemonway:** renamed findby ([8dce8e3](https://github.com/qbitartifacts/rec-api/commit/8dce8e3e422b9fa13a1c98d78f8f3e64cd45446f))
* **testing:** refactored tests ([63ec34e](https://github.com/qbitartifacts/rec-api/commit/63ec34ea7f5e2c35ed1278fb2425774d08054268))
* **testing:** working mongodb tests ([f086682](https://github.com/qbitartifacts/rec-api/commit/f08668284d5969a2fbc89a29cced8894a72a7dca))
* **tests:** added .gitkeep to var/db folder to ensure directory exists when running the tests ([e2cb9e5](https://github.com/qbitartifacts/rec-api/commit/e2cb9e51711572fb8f275f5c4364af2149a15d6b))
* **tests:** added missed deps to render PDFs in Dockerfiles ([59af43f](https://github.com/qbitartifacts/rec-api/commit/59af43fc11e53e82570c87e7069c2bd8533c2b87))
* **tests:** removed --rm to run tests from docker to allow debugging better ([8cc7daf](https://github.com/qbitartifacts/rec-api/commit/8cc7daf2f0cd3c448103cf4fc054d65d84413d8c))

## [2.10.0](https://github.com/qbitartifacts/rec-api/compare/v2.9.1...v2.10.0) (2020-03-04)


### Features

* adding issue templates ([ba3f051](https://github.com/qbitartifacts/rec-api/commit/ba3f0510a1656bc1d6f99dff30aab73924dc924b))


### Bug Fixes

* **dependencies:** updated dependencies ([32ca5fe](https://github.com/qbitartifacts/rec-api/commit/32ca5fe02e8e38f8f6cb709f8e4d3c0ab103e00d))

### [2.9.1](https://github.com/qbitartifacts/rec-api/compare/v2.9.0...v2.9.1) (2020-02-07)


### Bug Fixes

* **credit_cards:** fixed credit cards listing error ([c30593a](https://github.com/qbitartifacts/rec-api/commit/c30593a98f29d54f5762602e6c77d8e07e354c08))

## [2.9.0](https://github.com/qbitartifacts/rec-api/compare/v2.1.0...v2.9.0) (2020-01-09)


### Features

* **ci/cd:** changed coverage name ([16a2baf](https://github.com/qbitartifacts/rec-api/commit/16a2bafb008e48b8d88e15455e272a98027cb82c))
* **cron:** added cron to synchronize ibans with lemonway ([d26ec33](https://github.com/qbitartifacts/rec-api/commit/d26ec335cbc74451c1e4f15474e7a3abe1b82f90))
* **entities:** implemented iban entity ([76678ab](https://github.com/qbitartifacts/rec-api/commit/76678ab4ed964d24bc4daeb3fcaf238fac27ea76))
* **lemonway:** added event subscriber to upload ibans to lemonway ([f81f972](https://github.com/qbitartifacts/rec-api/commit/f81f972e59624b47c254cb9b559498f5be0031e8))
* **lemonway:** implemented cron to sync lemonway documentation ([9d5020e](https://github.com/qbitartifacts/rec-api/commit/9d5020ed04b58c275ccec0bb329e6555e6607de5))
* **migrations:** added lemon stuff to iban table ([adc69a8](https://github.com/qbitartifacts/rec-api/commit/adc69a8c7cdb4823a2e48edde5ce3c4a548bb610))
* **migrations:** added lemon stuff to iban table ([a4f2966](https://github.com/qbitartifacts/rec-api/commit/a4f2966ba45e362a1b3ce3f9766ff895e7420ffe))
* **migrations:** added migration to persist lemon document status ([904ffda](https://github.com/qbitartifacts/rec-api/commit/904ffda8895e25580e83cfc27d657fb8a4107eba))
* **migrations:** added more iban fields ([02ddc1a](https://github.com/qbitartifacts/rec-api/commit/02ddc1aab2ddb044b66c7d7f74d3699dd8187ea6))
* **migrations:** added more iban fields ([012ac38](https://github.com/qbitartifacts/rec-api/commit/012ac387ca7d963950494abc0b8816e46f418459))
* **migrations:** added table iban ([508f67f](https://github.com/qbitartifacts/rec-api/commit/508f67fb15f1e832037d5de44dc72f197b0de08e))
* **migrations:** added table iban ([b45c13d](https://github.com/qbitartifacts/rec-api/commit/b45c13d631836fd10fb43f1cb663de0a49a1a742))
* **tiers:**  now documents are uploaded to lemonway  ([#155](https://github.com/qbitartifacts/rec-api/issues/155)) ([02c9680](https://github.com/qbitartifacts/rec-api/commit/02c9680ddc4aea3cd5c9ad03959d5554bc8e4ecd))


### Bug Fixes

* **accounts:** avoiding show active account when it is not active ([e43ed07](https://github.com/qbitartifacts/rec-api/commit/e43ed07ab5d9449f25fcd4cd122cf6d7956b5e65))
* **ci:** fixed coverage tests ([#162](https://github.com/qbitartifacts/rec-api/issues/162)) ([6961b0f](https://github.com/qbitartifacts/rec-api/commit/6961b0f5ca2397a0fa3273b0dbdad499b50ab993))
* **ci:** removed filter .md from test action ([fda716c](https://github.com/qbitartifacts/rec-api/commit/fda716cdbb305708a31b2031127d380115a7e560))
* **ci/cd:** set deploy to prod env on published release ([30c826f](https://github.com/qbitartifacts/rec-api/commit/30c826f42e362be6f39d6b1872124003de944832))
* **dependencies:** updated composer dependencies ([ee9ee24](https://github.com/qbitartifacts/rec-api/commit/ee9ee2414156aaa1dd34fb745c0d6925b0f23c67))
* **dependencies:** updated composer deps ([599c010](https://github.com/qbitartifacts/rec-api/commit/599c0103191a5a54461afd39c1da54a242e5104a))
* **dependencies:** updated composer deps ([17ca295](https://github.com/qbitartifacts/rec-api/commit/17ca295f7abaac237e7634c030fd8dc5ccd14fdf))
* **lemonway:** cron update lemon ([#159](https://github.com/qbitartifacts/rec-api/issues/159)) ([0ef69a2](https://github.com/qbitartifacts/rec-api/commit/0ef69a2fc7f662db2fc88e3a625eb711a07ef704))
* **lemonway:** fixed 500 error when creating lemonway document ([abfc6fd](https://github.com/qbitartifacts/rec-api/commit/abfc6fd5eda11d96cdf17bf7319271b479e198dd))
* **lemonway:** fixed lemonway sync cron ([b233a97](https://github.com/qbitartifacts/rec-api/commit/b233a972c90d16cee3b83202d71eeaf9bf07bed1))
* **lemonway:** fixes lemonway document upload ([f2ad2b8](https://github.com/qbitartifacts/rec-api/commit/f2ad2b8331a8b0124bf6c2d2dca8fd6c9d61e72f))
* **lemonway:** refactored lemonobject and added lemon id and status ([44acde7](https://github.com/qbitartifacts/rec-api/commit/44acde7530f347477a9ae3338f4371feb0307007))
* **tests:** mark test as incomplete due to lack of mocks ([4c81814](https://github.com/qbitartifacts/rec-api/commit/4c818142844e174e47c6d16ba4bedb6b246ed051))
* :bug: extending from wrong interface ([615ae34](https://github.com/qbitartifacts/rec-api/commit/615ae34e2058b48e8751fa2b2df5fd4745bca618))
* bad parameters in lemon call ([0ce0843](https://github.com/qbitartifacts/rec-api/commit/0ce084387d1928a80ab23a8aa46508aff3a13c24))
* lw uploads ([#158](https://github.com/qbitartifacts/rec-api/issues/158)) ([531b244](https://github.com/qbitartifacts/rec-api/commit/531b244188e82b117b9394ee21641f50a664ea06))
* **migrations:** removes documents without content before migrate ([721a1d8](https://github.com/qbitartifacts/rec-api/commit/721a1d8269b1d5e6aed78e4b55ae9832e42ae32d))
* **refactor:** removed old migrations ([f52e07b](https://github.com/qbitartifacts/rec-api/commit/f52e07bba3c701fed794ec4fb2972fea17774bca))
* **refactor:** removed unused code ([348e78e](https://github.com/qbitartifacts/rec-api/commit/348e78e55c7259b01d4b860038cfdc5700d0d80f))

## [2.8.0](https://github.com/qbitartifacts/rec-api/compare/v2.1.0...v2.8.0) (2019-12-18)


### Features

* **ci/cd:** changed coverage name ([16a2baf](https://github.com/qbitartifacts/rec-api/commit/16a2bafb008e48b8d88e15455e272a98027cb82c))
* **lemonway:** implemented cron to sync lemonway documentation ([9d5020e](https://github.com/qbitartifacts/rec-api/commit/9d5020ed04b58c275ccec0bb329e6555e6607de5))
* **migrations:** added lemon stuff to iban table ([a4f2966](https://github.com/qbitartifacts/rec-api/commit/a4f2966ba45e362a1b3ce3f9766ff895e7420ffe))
* **migrations:** added migration to persist lemon document status ([904ffda](https://github.com/qbitartifacts/rec-api/commit/904ffda8895e25580e83cfc27d657fb8a4107eba))
* **migrations:** added more iban fields ([54e35bb](https://github.com/qbitartifacts/rec-api/commit/54e35bbed95257b441ca604e4065b4452dc9e7b6))
* **migrations:** added table iban ([b45c13d](https://github.com/qbitartifacts/rec-api/commit/b45c13d631836fd10fb43f1cb663de0a49a1a742))
* **tiers:**  now documents are uploaded to lemonway  ([#155](https://github.com/qbitartifacts/rec-api/issues/155)) ([02c9680](https://github.com/qbitartifacts/rec-api/commit/02c9680ddc4aea3cd5c9ad03959d5554bc8e4ecd))


### Bug Fixes

* **ci:** fixed coverage tests ([#162](https://github.com/qbitartifacts/rec-api/issues/162)) ([6961b0f](https://github.com/qbitartifacts/rec-api/commit/6961b0f5ca2397a0fa3273b0dbdad499b50ab993))
* **ci:** removed filter .md from test action ([fda716c](https://github.com/qbitartifacts/rec-api/commit/fda716cdbb305708a31b2031127d380115a7e560))
* **dependencies:** updated composer deps ([599c010](https://github.com/qbitartifacts/rec-api/commit/599c0103191a5a54461afd39c1da54a242e5104a))
* **lemonway:** fixed lemonway sync cron ([b233a97](https://github.com/qbitartifacts/rec-api/commit/b233a972c90d16cee3b83202d71eeaf9bf07bed1))
* lw uploads ([#158](https://github.com/qbitartifacts/rec-api/issues/158)) ([531b244](https://github.com/qbitartifacts/rec-api/commit/531b244188e82b117b9394ee21641f50a664ea06))
* **lemonway:** cron update lemon ([#159](https://github.com/qbitartifacts/rec-api/issues/159)) ([0ef69a2](https://github.com/qbitartifacts/rec-api/commit/0ef69a2fc7f662db2fc88e3a625eb711a07ef704))
* :bug: extending from wrong interface ([615ae34](https://github.com/qbitartifacts/rec-api/commit/615ae34e2058b48e8751fa2b2df5fd4745bca618))
* **ci/cd:** set deploy to prod env on published release ([30c826f](https://github.com/qbitartifacts/rec-api/commit/30c826f42e362be6f39d6b1872124003de944832))
* **dependencies:** updated composer dependencies ([ee9ee24](https://github.com/qbitartifacts/rec-api/commit/ee9ee2414156aaa1dd34fb745c0d6925b0f23c67))
* **lemonway:** fixed 500 error when creating lemonway document ([abfc6fd](https://github.com/qbitartifacts/rec-api/commit/abfc6fd5eda11d96cdf17bf7319271b479e198dd))
* **lemonway:** fixes lemonway document upload ([f2ad2b8](https://github.com/qbitartifacts/rec-api/commit/f2ad2b8331a8b0124bf6c2d2dca8fd6c9d61e72f))
* **migrations:** removes documents without content before migrate ([721a1d8](https://github.com/qbitartifacts/rec-api/commit/721a1d8269b1d5e6aed78e4b55ae9832e42ae32d))
* **refactor:** removed old migrations ([f52e07b](https://github.com/qbitartifacts/rec-api/commit/f52e07bba3c701fed794ec4fb2972fea17774bca))
* **refactor:** removed unused code ([348e78e](https://github.com/qbitartifacts/rec-api/commit/348e78e55c7259b01d4b860038cfdc5700d0d80f))

## [2.7.0](https://github.com/qbitartifacts/rec-api/compare/v2.1.0...v2.7.0) (2019-12-18)


### Features

* **ci/cd:** changed coverage name ([16a2baf](https://github.com/qbitartifacts/rec-api/commit/16a2bafb008e48b8d88e15455e272a98027cb82c))
* **lemonway:** implemented cron to sync lemonway documentation ([9d5020e](https://github.com/qbitartifacts/rec-api/commit/9d5020ed04b58c275ccec0bb329e6555e6607de5))
* **migrations:** added lemon stuff to iban table ([41ebf26](https://github.com/qbitartifacts/rec-api/commit/41ebf2686871978179aa83f89bf8afcb31faa6ea))
* **migrations:** added migration to persist lemon document status ([904ffda](https://github.com/qbitartifacts/rec-api/commit/904ffda8895e25580e83cfc27d657fb8a4107eba))
* **migrations:** added table iban ([b45c13d](https://github.com/qbitartifacts/rec-api/commit/b45c13d631836fd10fb43f1cb663de0a49a1a742))
* **tiers:**  now documents are uploaded to lemonway  ([#155](https://github.com/qbitartifacts/rec-api/issues/155)) ([02c9680](https://github.com/qbitartifacts/rec-api/commit/02c9680ddc4aea3cd5c9ad03959d5554bc8e4ecd))


### Bug Fixes

* **ci:** fixed coverage tests ([#162](https://github.com/qbitartifacts/rec-api/issues/162)) ([6961b0f](https://github.com/qbitartifacts/rec-api/commit/6961b0f5ca2397a0fa3273b0dbdad499b50ab993))
* **ci:** removed filter .md from test action ([fda716c](https://github.com/qbitartifacts/rec-api/commit/fda716cdbb305708a31b2031127d380115a7e560))
* **dependencies:** updated composer deps ([599c010](https://github.com/qbitartifacts/rec-api/commit/599c0103191a5a54461afd39c1da54a242e5104a))
* **lemonway:** fixed lemonway sync cron ([b233a97](https://github.com/qbitartifacts/rec-api/commit/b233a972c90d16cee3b83202d71eeaf9bf07bed1))
* lw uploads ([#158](https://github.com/qbitartifacts/rec-api/issues/158)) ([531b244](https://github.com/qbitartifacts/rec-api/commit/531b244188e82b117b9394ee21641f50a664ea06))
* **lemonway:** cron update lemon ([#159](https://github.com/qbitartifacts/rec-api/issues/159)) ([0ef69a2](https://github.com/qbitartifacts/rec-api/commit/0ef69a2fc7f662db2fc88e3a625eb711a07ef704))
* :bug: extending from wrong interface ([615ae34](https://github.com/qbitartifacts/rec-api/commit/615ae34e2058b48e8751fa2b2df5fd4745bca618))
* **ci/cd:** set deploy to prod env on published release ([30c826f](https://github.com/qbitartifacts/rec-api/commit/30c826f42e362be6f39d6b1872124003de944832))
* **dependencies:** updated composer dependencies ([ee9ee24](https://github.com/qbitartifacts/rec-api/commit/ee9ee2414156aaa1dd34fb745c0d6925b0f23c67))
* **lemonway:** fixed 500 error when creating lemonway document ([abfc6fd](https://github.com/qbitartifacts/rec-api/commit/abfc6fd5eda11d96cdf17bf7319271b479e198dd))
* **lemonway:** fixes lemonway document upload ([f2ad2b8](https://github.com/qbitartifacts/rec-api/commit/f2ad2b8331a8b0124bf6c2d2dca8fd6c9d61e72f))
* **migrations:** removes documents without content before migrate ([721a1d8](https://github.com/qbitartifacts/rec-api/commit/721a1d8269b1d5e6aed78e4b55ae9832e42ae32d))
* **refactor:** removed old migrations ([f52e07b](https://github.com/qbitartifacts/rec-api/commit/f52e07bba3c701fed794ec4fb2972fea17774bca))
* **refactor:** removed unused code ([348e78e](https://github.com/qbitartifacts/rec-api/commit/348e78e55c7259b01d4b860038cfdc5700d0d80f))

## [2.6.0](https://github.com/qbitartifacts/rec-api/compare/v2.1.0...v2.6.0) (2019-12-18)


### Features

* **ci/cd:** changed coverage name ([16a2baf](https://github.com/qbitartifacts/rec-api/commit/16a2bafb008e48b8d88e15455e272a98027cb82c))
* **lemonway:** implemented cron to sync lemonway documentation ([9d5020e](https://github.com/qbitartifacts/rec-api/commit/9d5020ed04b58c275ccec0bb329e6555e6607de5))
* **migrations:** added migration to persist lemon document status ([904ffda](https://github.com/qbitartifacts/rec-api/commit/904ffda8895e25580e83cfc27d657fb8a4107eba))
* **migrations:** added table iban ([9af95f1](https://github.com/qbitartifacts/rec-api/commit/9af95f198297dc800a39d35e142b849598accd46))
* **tiers:**  now documents are uploaded to lemonway  ([#155](https://github.com/qbitartifacts/rec-api/issues/155)) ([02c9680](https://github.com/qbitartifacts/rec-api/commit/02c9680ddc4aea3cd5c9ad03959d5554bc8e4ecd))


### Bug Fixes

* **ci:** fixed coverage tests ([#162](https://github.com/qbitartifacts/rec-api/issues/162)) ([6961b0f](https://github.com/qbitartifacts/rec-api/commit/6961b0f5ca2397a0fa3273b0dbdad499b50ab993))
* **ci:** removed filter .md from test action ([fda716c](https://github.com/qbitartifacts/rec-api/commit/fda716cdbb305708a31b2031127d380115a7e560))
* **dependencies:** updated composer deps ([17ca295](https://github.com/qbitartifacts/rec-api/commit/17ca295f7abaac237e7634c030fd8dc5ccd14fdf))
* **lemonway:** fixed lemonway sync cron ([b233a97](https://github.com/qbitartifacts/rec-api/commit/b233a972c90d16cee3b83202d71eeaf9bf07bed1))
* lw uploads ([#158](https://github.com/qbitartifacts/rec-api/issues/158)) ([531b244](https://github.com/qbitartifacts/rec-api/commit/531b244188e82b117b9394ee21641f50a664ea06))
* **lemonway:** cron update lemon ([#159](https://github.com/qbitartifacts/rec-api/issues/159)) ([0ef69a2](https://github.com/qbitartifacts/rec-api/commit/0ef69a2fc7f662db2fc88e3a625eb711a07ef704))
* :bug: extending from wrong interface ([615ae34](https://github.com/qbitartifacts/rec-api/commit/615ae34e2058b48e8751fa2b2df5fd4745bca618))
* **ci/cd:** set deploy to prod env on published release ([30c826f](https://github.com/qbitartifacts/rec-api/commit/30c826f42e362be6f39d6b1872124003de944832))
* **dependencies:** updated composer dependencies ([ee9ee24](https://github.com/qbitartifacts/rec-api/commit/ee9ee2414156aaa1dd34fb745c0d6925b0f23c67))
* **lemonway:** fixed 500 error when creating lemonway document ([abfc6fd](https://github.com/qbitartifacts/rec-api/commit/abfc6fd5eda11d96cdf17bf7319271b479e198dd))
* **lemonway:** fixes lemonway document upload ([f2ad2b8](https://github.com/qbitartifacts/rec-api/commit/f2ad2b8331a8b0124bf6c2d2dca8fd6c9d61e72f))
* **migrations:** removes documents without content before migrate ([721a1d8](https://github.com/qbitartifacts/rec-api/commit/721a1d8269b1d5e6aed78e4b55ae9832e42ae32d))
* **refactor:** removed old migrations ([f52e07b](https://github.com/qbitartifacts/rec-api/commit/f52e07bba3c701fed794ec4fb2972fea17774bca))
* **refactor:** removed unused code ([348e78e](https://github.com/qbitartifacts/rec-api/commit/348e78e55c7259b01d4b860038cfdc5700d0d80f))

### [2.5.1](https://github.com/qbitartifacts/rec-api/compare/v2.1.0...v2.5.1) (2019-12-17)


### Features

* **ci/cd:** changed coverage name ([16a2baf](https://github.com/qbitartifacts/rec-api/commit/16a2bafb008e48b8d88e15455e272a98027cb82c))
* **lemonway:** implemented cron to sync lemonway documentation ([9d5020e](https://github.com/qbitartifacts/rec-api/commit/9d5020ed04b58c275ccec0bb329e6555e6607de5))
* **migrations:** added migration to persist lemon document status ([904ffda](https://github.com/qbitartifacts/rec-api/commit/904ffda8895e25580e83cfc27d657fb8a4107eba))
* **tiers:**  now documents are uploaded to lemonway  ([#155](https://github.com/qbitartifacts/rec-api/issues/155)) ([02c9680](https://github.com/qbitartifacts/rec-api/commit/02c9680ddc4aea3cd5c9ad03959d5554bc8e4ecd))


### Bug Fixes

* **ci:** fixed coverage tests ([#162](https://github.com/qbitartifacts/rec-api/issues/162)) ([6961b0f](https://github.com/qbitartifacts/rec-api/commit/6961b0f5ca2397a0fa3273b0dbdad499b50ab993))
* **dependencies:** updated composer dependencies ([ee9ee24](https://github.com/qbitartifacts/rec-api/commit/ee9ee2414156aaa1dd34fb745c0d6925b0f23c67))
* **lemonway:** cron update lemon ([#159](https://github.com/qbitartifacts/rec-api/issues/159)) ([0ef69a2](https://github.com/qbitartifacts/rec-api/commit/0ef69a2fc7f662db2fc88e3a625eb711a07ef704))
* **lemonway:** fixed lemonway sync cron ([b233a97](https://github.com/qbitartifacts/rec-api/commit/b233a972c90d16cee3b83202d71eeaf9bf07bed1))
* :bug: extending from wrong interface ([615ae34](https://github.com/qbitartifacts/rec-api/commit/615ae34e2058b48e8751fa2b2df5fd4745bca618))
* lw uploads ([#158](https://github.com/qbitartifacts/rec-api/issues/158)) ([531b244](https://github.com/qbitartifacts/rec-api/commit/531b244188e82b117b9394ee21641f50a664ea06))
* **ci/cd:** set deploy to prod env on published release ([30c826f](https://github.com/qbitartifacts/rec-api/commit/30c826f42e362be6f39d6b1872124003de944832))
* **lemonway:** fixed 500 error when creating lemonway document ([abfc6fd](https://github.com/qbitartifacts/rec-api/commit/abfc6fd5eda11d96cdf17bf7319271b479e198dd))
* **lemonway:** fixes lemonway document upload ([f2ad2b8](https://github.com/qbitartifacts/rec-api/commit/f2ad2b8331a8b0124bf6c2d2dca8fd6c9d61e72f))
* **migrations:** removes documents without content before migrate ([721a1d8](https://github.com/qbitartifacts/rec-api/commit/721a1d8269b1d5e6aed78e4b55ae9832e42ae32d))
* **refactor:** removed old migrations ([f52e07b](https://github.com/qbitartifacts/rec-api/commit/f52e07bba3c701fed794ec4fb2972fea17774bca))
* **refactor:** removed unused code ([348e78e](https://github.com/qbitartifacts/rec-api/commit/348e78e55c7259b01d4b860038cfdc5700d0d80f))

## [2.5.0](https://github.com/qbitartifacts/rec-api/compare/v2.1.0...v2.5.0) (2019-12-17)


### Features

* **ci/cd:** changed coverage name ([16a2baf](https://github.com/qbitartifacts/rec-api/commit/16a2bafb008e48b8d88e15455e272a98027cb82c))
* **migrations:** added migration to persist lemon document status ([deb8fa7](https://github.com/qbitartifacts/rec-api/commit/deb8fa77b9743f969f18f83fcaa1c8fdbf7612be))
* **tiers:**  now documents are uploaded to lemonway  ([#155](https://github.com/qbitartifacts/rec-api/issues/155)) ([02c9680](https://github.com/qbitartifacts/rec-api/commit/02c9680ddc4aea3cd5c9ad03959d5554bc8e4ecd))


### Bug Fixes

* **ci:** fixed coverage tests ([#162](https://github.com/qbitartifacts/rec-api/issues/162)) ([6961b0f](https://github.com/qbitartifacts/rec-api/commit/6961b0f5ca2397a0fa3273b0dbdad499b50ab993))
* lw uploads ([#158](https://github.com/qbitartifacts/rec-api/issues/158)) ([531b244](https://github.com/qbitartifacts/rec-api/commit/531b244188e82b117b9394ee21641f50a664ea06))
* **ci/cd:** set deploy to prod env on published release ([30c826f](https://github.com/qbitartifacts/rec-api/commit/30c826f42e362be6f39d6b1872124003de944832))
* **dependencies:** updated composer dependencies ([ee9ee24](https://github.com/qbitartifacts/rec-api/commit/ee9ee2414156aaa1dd34fb745c0d6925b0f23c67))
* **lemonway:** cron update lemon ([#159](https://github.com/qbitartifacts/rec-api/issues/159)) ([0ef69a2](https://github.com/qbitartifacts/rec-api/commit/0ef69a2fc7f662db2fc88e3a625eb711a07ef704))
* :bug: extending from wrong interface ([615ae34](https://github.com/qbitartifacts/rec-api/commit/615ae34e2058b48e8751fa2b2df5fd4745bca618))
* **lemonway:** fixed 500 error when creating lemonway document ([abfc6fd](https://github.com/qbitartifacts/rec-api/commit/abfc6fd5eda11d96cdf17bf7319271b479e198dd))
* **lemonway:** fixes lemonway document upload ([f2ad2b8](https://github.com/qbitartifacts/rec-api/commit/f2ad2b8331a8b0124bf6c2d2dca8fd6c9d61e72f))
* **migrations:** removes documents without content before migrate ([721a1d8](https://github.com/qbitartifacts/rec-api/commit/721a1d8269b1d5e6aed78e4b55ae9832e42ae32d))
* **refactor:** removed old migrations ([f52e07b](https://github.com/qbitartifacts/rec-api/commit/f52e07bba3c701fed794ec4fb2972fea17774bca))
* **refactor:** removed unused code ([348e78e](https://github.com/qbitartifacts/rec-api/commit/348e78e55c7259b01d4b860038cfdc5700d0d80f))

### [2.4.1](https://github.com/qbitartifacts/rec-api/compare/v2.1.0...v2.4.1) (2019-12-16)


### Features

* **ci/cd:** changed coverage name ([16a2baf](https://github.com/qbitartifacts/rec-api/commit/16a2bafb008e48b8d88e15455e272a98027cb82c))
* **tiers:**  now documents are uploaded to lemonway  ([#155](https://github.com/qbitartifacts/rec-api/issues/155)) ([02c9680](https://github.com/qbitartifacts/rec-api/commit/02c9680ddc4aea3cd5c9ad03959d5554bc8e4ecd))


### Bug Fixes

* **ci:** fixed coverage tests ([#162](https://github.com/qbitartifacts/rec-api/issues/162)) ([6961b0f](https://github.com/qbitartifacts/rec-api/commit/6961b0f5ca2397a0fa3273b0dbdad499b50ab993))
* lw uploads ([#158](https://github.com/qbitartifacts/rec-api/issues/158)) ([531b244](https://github.com/qbitartifacts/rec-api/commit/531b244188e82b117b9394ee21641f50a664ea06))
* **ci/cd:** set deploy to prod env on published release ([30c826f](https://github.com/qbitartifacts/rec-api/commit/30c826f42e362be6f39d6b1872124003de944832))
* **dependencies:** updated composer dependencies ([ee9ee24](https://github.com/qbitartifacts/rec-api/commit/ee9ee2414156aaa1dd34fb745c0d6925b0f23c67))
* **lemonway:** cron update lemon ([#159](https://github.com/qbitartifacts/rec-api/issues/159)) ([0ef69a2](https://github.com/qbitartifacts/rec-api/commit/0ef69a2fc7f662db2fc88e3a625eb711a07ef704))
* :bug: extending from wrong interface ([615ae34](https://github.com/qbitartifacts/rec-api/commit/615ae34e2058b48e8751fa2b2df5fd4745bca618))
* **lemonway:** fixed 500 error when creating lemonway document ([abfc6fd](https://github.com/qbitartifacts/rec-api/commit/abfc6fd5eda11d96cdf17bf7319271b479e198dd))
* **lemonway:** fixes lemonway document upload ([f2ad2b8](https://github.com/qbitartifacts/rec-api/commit/f2ad2b8331a8b0124bf6c2d2dca8fd6c9d61e72f))
* **migrations:** removes documents without content before migrate ([721a1d8](https://github.com/qbitartifacts/rec-api/commit/721a1d8269b1d5e6aed78e4b55ae9832e42ae32d))
* **refactor:** removed old migrations ([f52e07b](https://github.com/qbitartifacts/rec-api/commit/f52e07bba3c701fed794ec4fb2972fea17774bca))
* **refactor:** removed unused code ([348e78e](https://github.com/qbitartifacts/rec-api/commit/348e78e55c7259b01d4b860038cfdc5700d0d80f))

## [2.4.0](https://github.com/qbitartifacts/rec-api/compare/v2.1.0...v2.4.0) (2019-12-12)


### Features

* **ci/cd:** changed coverage name ([16a2baf](https://github.com/qbitartifacts/rec-api/commit/16a2bafb008e48b8d88e15455e272a98027cb82c))
* **tiers:**  now documents are uploaded to lemonway  ([#155](https://github.com/qbitartifacts/rec-api/issues/155)) ([02c9680](https://github.com/qbitartifacts/rec-api/commit/02c9680ddc4aea3cd5c9ad03959d5554bc8e4ecd))


### Bug Fixes

* **ci:** fixed coverage tests ([cd8a096](https://github.com/qbitartifacts/rec-api/commit/cd8a0967549a26035b68942a4657779cff34316e))
* **ci:** fixed coverage tests ([#162](https://github.com/qbitartifacts/rec-api/issues/162)) ([6961b0f](https://github.com/qbitartifacts/rec-api/commit/6961b0f5ca2397a0fa3273b0dbdad499b50ab993))
* :bug: extending from wrong interface ([615ae34](https://github.com/qbitartifacts/rec-api/commit/615ae34e2058b48e8751fa2b2df5fd4745bca618))
* lw uploads ([#158](https://github.com/qbitartifacts/rec-api/issues/158)) ([531b244](https://github.com/qbitartifacts/rec-api/commit/531b244188e82b117b9394ee21641f50a664ea06))
* **ci/cd:** set deploy to prod env on published release ([30c826f](https://github.com/qbitartifacts/rec-api/commit/30c826f42e362be6f39d6b1872124003de944832))
* **dependencies:** updated composer dependencies ([ee9ee24](https://github.com/qbitartifacts/rec-api/commit/ee9ee2414156aaa1dd34fb745c0d6925b0f23c67))
* **lemonway:** cron update lemon ([#159](https://github.com/qbitartifacts/rec-api/issues/159)) ([0ef69a2](https://github.com/qbitartifacts/rec-api/commit/0ef69a2fc7f662db2fc88e3a625eb711a07ef704))
* **lemonway:** fixed 500 error when creating lemonway document ([abfc6fd](https://github.com/qbitartifacts/rec-api/commit/abfc6fd5eda11d96cdf17bf7319271b479e198dd))
* **lemonway:** fixes lemonway document upload ([f2ad2b8](https://github.com/qbitartifacts/rec-api/commit/f2ad2b8331a8b0124bf6c2d2dca8fd6c9d61e72f))
* **migrations:** removes documents without content before migrate ([721a1d8](https://github.com/qbitartifacts/rec-api/commit/721a1d8269b1d5e6aed78e4b55ae9832e42ae32d))
* **refactor:** removed old migrations ([f52e07b](https://github.com/qbitartifacts/rec-api/commit/f52e07bba3c701fed794ec4fb2972fea17774bca))
* **refactor:** removed unused code ([348e78e](https://github.com/qbitartifacts/rec-api/commit/348e78e55c7259b01d4b860038cfdc5700d0d80f))

## [2.3.0](https://github.com/qbitartifacts/rec-api/compare/v2.1.0...v2.2.0) (2019-12-12)


### Features

* **ci/cd:** changed coverage name ([16a2baf](https://github.com/qbitartifacts/rec-api/commit/16a2bafb008e48b8d88e15455e272a98027cb82c))
* **tiers:**  now documents are uploaded to lemonway  ([#155](https://github.com/qbitartifacts/rec-api/issues/155)) ([02c9680](https://github.com/qbitartifacts/rec-api/commit/02c9680ddc4aea3cd5c9ad03959d5554bc8e4ecd))


### Bug Fixes

* **ci:** fixed coverage tests ([cd8a096](https://github.com/qbitartifacts/rec-api/commit/cd8a0967549a26035b68942a4657779cff34316e))
* lw uploads ([#158](https://github.com/qbitartifacts/rec-api/issues/158)) ([531b244](https://github.com/qbitartifacts/rec-api/commit/531b244188e82b117b9394ee21641f50a664ea06))
* **ci/cd:** set deploy to prod env on published release ([30c826f](https://github.com/qbitartifacts/rec-api/commit/30c826f42e362be6f39d6b1872124003de944832))
* **dependencies:** updated composer dependencies ([ee9ee24](https://github.com/qbitartifacts/rec-api/commit/ee9ee2414156aaa1dd34fb745c0d6925b0f23c67))
* **lemonway:** cron update lemon ([#159](https://github.com/qbitartifacts/rec-api/issues/159)) ([0ef69a2](https://github.com/qbitartifacts/rec-api/commit/0ef69a2fc7f662db2fc88e3a625eb711a07ef704))
* :bug: extending from wrong interface ([615ae34](https://github.com/qbitartifacts/rec-api/commit/615ae34e2058b48e8751fa2b2df5fd4745bca618))
* **lemonway:** fixed 500 error when creating lemonway document ([abfc6fd](https://github.com/qbitartifacts/rec-api/commit/abfc6fd5eda11d96cdf17bf7319271b479e198dd))
* **lemonway:** fixes lemonway document upload ([f2ad2b8](https://github.com/qbitartifacts/rec-api/commit/f2ad2b8331a8b0124bf6c2d2dca8fd6c9d61e72f))
* **migrations:** removes documents without content before migrate ([721a1d8](https://github.com/qbitartifacts/rec-api/commit/721a1d8269b1d5e6aed78e4b55ae9832e42ae32d))
* **refactor:** removed old migrations ([f52e07b](https://github.com/qbitartifacts/rec-api/commit/f52e07bba3c701fed794ec4fb2972fea17774bca))
* **refactor:** removed unused code ([348e78e](https://github.com/qbitartifacts/rec-api/commit/348e78e55c7259b01d4b860038cfdc5700d0d80f))

## [2.1.0](https://github.com/qbitartifacts/rec-api/compare/v2.0.4...v2.1.0) (2019-12-10)


### Features

* **ci:** set prod build only on github release ([c5142f9](https://github.com/qbitartifacts/rec-api/commit/c5142f9fa72373f0090846b3d93d2458fb210e60))

### [2.0.5](https://github.com/qbitartifacts/rec-api/compare/v2.0.2...v2.0.5) (2019-12-10)


### Bug Fixes

* **dashboard:** dashboard neighbourhoods ([#151](https://github.com/qbitartifacts/rec-api/issues/151)) ([b98ae63](https://github.com/qbitartifacts/rec-api/commit/b98ae63383b0d4376ce531ed804d297ee280a5e0))

### [2.0.4](https://github.com/qbitartifacts/rec-api/compare/v2.0.2...v2.0.4) (2019-12-10)


### Bug Fixes

* **dashboard:** dashboard neighbourhoods ([#151](https://github.com/qbitartifacts/rec-api/issues/151)) ([b98ae63](https://github.com/qbitartifacts/rec-api/commit/b98ae63383b0d4376ce531ed804d297ee280a5e0))

### [2.0.3](https://github.com/qbitartifacts/rec-api/compare/v2.0.2...v2.0.3) (2019-12-10)


### Bug Fixes

* **dashboard:** dashboard neighbourhoods ([#151](https://github.com/qbitartifacts/rec-api/issues/151)) ([b98ae63](https://github.com/qbitartifacts/rec-api/commit/b98ae63383b0d4376ce531ed804d297ee280a5e0))

### [2.0.2](https://github.com/qbitartifacts/rec-api/compare/v2.0.1...v2.0.2) (2019-12-10)


### Bug Fixes

* **ci:** changed webhooks for releases ([7b03b41](https://github.com/qbitartifacts/rec-api/commit/7b03b41010edfa9567063996c0cbbb545f4e2855))

### [2.0.1](https://github.com/qbitartifacts/rec-api/compare/v2.0.0...v2.0.1) (2019-12-10)


### Bug Fixes

* **ci:** set build prod on create tags ([299c5d2](https://github.com/qbitartifacts/rec-api/commit/299c5d235bc447436e299baa2cd34895d37f97d8))

## [2.0.0](https://github.com/qbitartifacts/rec-api/compare/v1.7.0...v2.0.0) (2019-12-10)


### ⚠ BREAKING CHANGES

* **newline:** this blank line breaks the code

### Features

* **ci:** removed testing in master branch, only pull requests ([78db575](https://github.com/qbitartifacts/rec-api/commit/78db575f4b7f6d3c49b04d70cf50f70d48e8e9c9))
* **migrations:** fixed migrations ([38720a3](https://github.com/qbitartifacts/rec-api/commit/38720a38c60b633c93f30604ffc6622c14f2be8d))
* implemented moneyout, needs test ([5f4e229](https://github.com/qbitartifacts/rec-api/commit/5f4e229fc564b2dee4f7bb3d735efe1607a51388))
* **migrations:** added migrations to refactor treasurewithdrawals and changed User code to support it (can run migrations safely) ([bbed1fc](https://github.com/qbitartifacts/rec-api/commit/bbed1fcb436e6fd81c21c91774cd043e367d7791))
* **migrations:** adds self-referencing tier migration ([eac2e34](https://github.com/qbitartifacts/rec-api/commit/eac2e3452db82d2f4db1f6bc15fbb57f6449c893))
* **migrations:** implemented migration to add next tier ([e331312](https://github.com/qbitartifacts/rec-api/commit/e331312c0cbebcfc14a0076679337ac581e1120e))
* **migrations:** implemented migration to relate tiers with accounts ([bbfede5](https://github.com/qbitartifacts/rec-api/commit/bbfede5bcc7c1b42314674dac6a2edd4fb3ad114))
* **migrations:** removed validator, needed to run safely migrations ([2879051](https://github.com/qbitartifacts/rec-api/commit/2879051317b570b358a1a82a377271ed6f1554b1))
* **migrations:** Renaming table for treasure withdrawals and adding lemonway stuff to documents ([b9d2e4b](https://github.com/qbitartifacts/rec-api/commit/b9d2e4b861395d72df9ea0857648163a208d867a))
* added more statuses to documents ([aa8a60b](https://github.com/qbitartifacts/rec-api/commit/aa8a60bffec2ab1f0bd3cced6e22d30913dc77c1))
* added next field to tiers ([491a6aa](https://github.com/qbitartifacts/rec-api/commit/491a6aa2ce8c864bd971bb8dd30a1ec2f254a39d))
* added previous tier to tiers ([b6667b9](https://github.com/qbitartifacts/rec-api/commit/b6667b92c669dd376d4f057beba10bcef67bfde2))
* related tiers with accounts ([82c1013](https://github.com/qbitartifacts/rec-api/commit/82c1013d63c1c2f209a7a0139f14c98eae511610))
* **refactor:** renamed odm service to have all with the same name ([66ce11c](https://github.com/qbitartifacts/rec-api/commit/66ce11c857cc7907e4fb58a993be56397f1ef9cb))
* **testing:** created first test for transactions (giant step for mankind) ([d1ea438](https://github.com/qbitartifacts/rec-api/commit/d1ea438dfaf7398c7a70f38c0a02f72e23688d82))


### Bug Fixes

* **dashboard:** filtered neihgbourhoods by company only ([bbb881d](https://github.com/qbitartifacts/rec-api/commit/bbb881d2fd87eced02f79233afe7b2ce4a2fefbd))
* added migration to relate tiers and accounts ([589e339](https://github.com/qbitartifacts/rec-api/commit/589e3398adb7534c1e1abc5c8f826e06a3f5e4ce))
* fixed relationship management from API ([3b2f80f](https://github.com/qbitartifacts/rec-api/commit/3b2f80f8a3e551f45daa71f0456cb0eb61377210))
* fixed test requiring treasure validations to user ([4e8085c](https://github.com/qbitartifacts/rec-api/commit/4e8085cfd5bdc3ff353caa5338bbe9d2c5652295))
* fixes qbitartifacts/rec-api[#142](https://github.com/qbitartifacts/rec-api/issues/142) ([809b1e2](https://github.com/qbitartifacts/rec-api/commit/809b1e2c44e94aaf8414372d8ea2d603e85b4c8c))
* returned contents in documents ([5f98403](https://github.com/qbitartifacts/rec-api/commit/5f984036ca704cd24cb2fa545dad7d93fca3864a))
* tests ([187fb07](https://github.com/qbitartifacts/rec-api/commit/187fb07cc716fa2909c47e98baa4d97a79adb399))
* **dependencies:** removed unused dependency (old html2pdf lib) ([a0c4996](https://github.com/qbitartifacts/rec-api/commit/a0c4996ca8a84b6b01ecd4b28b56479cdb71632b))
* **Entity:Document:** Fixed entity .setContent, false checking  ([b8899f6](https://github.com/qbitartifacts/rec-api/commit/b8899f64bb88bfe12925798718a9de823f564474))
* **migrations:** added missed relationship between tiers and documentkinds ([5d3ed4a](https://github.com/qbitartifacts/rec-api/commit/5d3ed4af53b4ae5c4733f751d5f8c3e68f0ee672))
* **refactor:** renamed Uploadable interface ([846a2f6](https://github.com/qbitartifacts/rec-api/commit/846a2f6c1aa4d037722180663d9d3730c4b7c3ab))
* updated status changes in documents ([7bd2db2](https://github.com/qbitartifacts/rec-api/commit/7bd2db2cbb2db9d1ccb21cdb6543489ba1476bff))
* **newline:** test breaking change ([082fd7d](https://github.com/qbitartifacts/rec-api/commit/082fd7d0f8ea39de82efa1b55ce20036fce00708))

## [1.7.0](https://github.com/qbitartifacts/rec-api/compare/v1.6.0...v1.7.0) (2019-11-21)


### Features

* **cron:** added cron to sync with lemonway every minute ([b1c4f60](https://github.com/qbitartifacts/rec-api/commit/b1c4f60888365ca82897f40db05a5a52db0fe43b))
* **dependencies:** updated composer dependencies ([a335eac](https://github.com/qbitartifacts/rec-api/commit/a335eac2fdde920bf9a8ca8f036b2e5e2b365b8c))
* **dependencies:** updated composer dependencies ([c72d46d](https://github.com/qbitartifacts/rec-api/commit/c72d46d9f3e5a81af4d61ae13fb898ac943a126f))
* **kyc:** added newer implementation for kyc and tiers ([d56b4de](https://github.com/qbitartifacts/rec-api/commit/d56b4dea7f1e8fa2641466b4f46ad8acdaf0f582))
* **lemonway:** added detailed lw error ([ffcd1c1](https://github.com/qbitartifacts/rec-api/commit/ffcd1c1a8fdddbb1cf4a578c8de7824cc3e12f4d))
* **lemonway:** added detailed lw error ([395f7df](https://github.com/qbitartifacts/rec-api/commit/395f7df364d208fc7b234278e49da889b5f327e8))
* **lemonway:** added log to detect account anomalies ([9168949](https://github.com/qbitartifacts/rec-api/commit/9168949f6c163d251d8febd6fe11fd9021f642fd))
* **lemonway:** implemented lw gateway ([a6195d3](https://github.com/qbitartifacts/rec-api/commit/a6195d328387587b80944f956c1c4f0e61beb20e))
* **tiers:** added db migration for tier management ([4f4e2b1](https://github.com/qbitartifacts/rec-api/commit/4f4e2b16f59a0fcdf9474d73d5af90152482076d))
* added lw_balance to accounts ([548b60d](https://github.com/qbitartifacts/rec-api/commit/548b60da0631d37fa3de027bff202fdfff630e04))
* implemented command to synchronize balances with lemonway ([9ee70b4](https://github.com/qbitartifacts/rec-api/commit/9ee70b42b824b0fa9190d0f52a4dd8e89c4eb041))
* **migrations:** adding migration to support lemonway balance in accounts ([7a35533](https://github.com/qbitartifacts/rec-api/commit/7a3553376b1598da6fbdff31ffec9ddfed567a6c))


### Bug Fixes

* **b2b_report:** Removed total from list-title ([8121a31](https://github.com/qbitartifacts/rec-api/commit/8121a31faea5234621bed406b44b044be8802ad2))
* **lemonway:** error with case sensitiveness ([5b72993](https://github.com/qbitartifacts/rec-api/commit/5b72993cb9c23cb110a065c342370ebcf93d72e8))
* **lemonway:** set balance in cents ([0827d70](https://github.com/qbitartifacts/rec-api/commit/0827d70692dcc8dda19abe7697c1d4552e84cc1c))
* **mailing:** added constraint to not allow sending mails without any recipient ([500d9d8](https://github.com/qbitartifacts/rec-api/commit/500d9d8de6d00c1cea6219f468ece1e82d3e3cb8))
* added validation for setting user locale ([6ec570e](https://github.com/qbitartifacts/rec-api/commit/6ec570e7851f0f7394c746780152fa59337320c0))
* fixed translated attachments into e-mails ([ec8facd](https://github.com/qbitartifacts/rec-api/commit/ec8facd5536fbb5304a31a26237d92f000713e05))
* inherited constant from stateful instead of defining in every subclass ([a5d48ba](https://github.com/qbitartifacts/rec-api/commit/a5d48ba104f563673316ca52232bd4166e9e0316))
* refactored test about clients and providers report ([058e74b](https://github.com/qbitartifacts/rec-api/commit/058e74bdb3c8ef9e131d8417303a3e0c3ee62efd))
* set lw_balance in cents ([6d1cae2](https://github.com/qbitartifacts/rec-api/commit/6d1cae28f7fff64c64fbc00efdb030975ed4d667))
* **error_handling:** fixes error handling if data is not a validationconstraint ([fa8a4fe](https://github.com/qbitartifacts/rec-api/commit/fa8a4fe98e00a3f83c4fbf2e6067ee08b5e69610))
* **lemonway:** added error-controlling checks ([026f9f5](https://github.com/qbitartifacts/rec-api/commit/026f9f5699bd7cc089206a1ce409bc703736e408))
* **lemonway:** changed lemon error http code to 503 ([62d6c72](https://github.com/qbitartifacts/rec-api/commit/62d6c72944c1659f39a5f4702107d0dcac55f2f4))
* **lemonway:** dummy error testing providers ([58fbceb](https://github.com/qbitartifacts/rec-api/commit/58fbceb789bb68a135f7e8e27806806376bb9a2f))
* **lemonway:** fixed send and receive money between accounts routes ([385c7ab](https://github.com/qbitartifacts/rec-api/commit/385c7ab5a53d2c351d4e89652b9967d3709e344a))
* **mailing:** mails are now sent with the account's locale ([1d0bcac](https://github.com/qbitartifacts/rec-api/commit/1d0bcac82eb339676a695a83417ed3fc698452a6))

## [1.6.0](https://github.com/qbitartifacts/rec-api/compare/v1.5.0...v1.6.0) (2019-11-15)


### Features

* **lemonway:** added ability to send and receive money between lw wallets ([27cdd4f](https://github.com/qbitartifacts/rec-api/commit/27cdd4f55686dc873bc61b81d65bb8cc29be6768))

## [1.5.0](https://github.com/qbitartifacts/rec-api/compare/v1.4.3...v1.5.0) (2019-11-15)


### Features

* **docs:** updated how-to deal with db changes ([ddcedb0](https://github.com/qbitartifacts/rec-api/commit/ddcedb0fbb14fa66f80412ff1a3ebd31164ba80b))

### [1.4.3](https://github.com/qbitartifacts/rec-api/compare/v1.4.2...v1.4.3) (2019-11-15)


### Bug Fixes

* removed version config ([c9a9e2d](https://github.com/qbitartifacts/rec-api/commit/c9a9e2d8d66879e4efdaa851f7f202b50034f688))

### [1.4.2](https://github.com/qbitartifacts/rec-api/compare/v1.4.1...v1.4.2) (2019-11-15)

### [1.4.1](https://github.com/qbitartifacts/rec-api/compare/v1.4.0...v1.4.1) (2019-11-15)

## [1.4.0](https://github.com/qbitartifacts/rec-api/compare/v1.3.20...v1.4.0) (2019-11-15)
