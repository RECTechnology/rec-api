# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## [2.18.0](https://github.com/qbitartifacts/rec-api/compare/v2.17.0...v2.18.0) (2020-06-11)


### Features

* **cron:** added missed cron ([5970443](https://github.com/qbitartifacts/rec-api/commit/5970443170f4f2e3010f93118afc6316abcd50f6))
* **documentation:** added POS urls to documentation ,fixes QbitArtifacts/rec-pos[#1](https://github.com/qbitartifacts/rec-api/issues/1) ([03d6155](https://github.com/qbitartifacts/rec-api/commit/03d6155c1362649ecbb79701f5f53983dda9d577))
* **migrations:** added migration for save the refunded tx in the paymentorder ([1ff0699](https://github.com/qbitartifacts/rec-api/commit/1ff069948150e223e60c0077e0e01690d1c43f3b))
* **pos:** added ip address and payment address to payment orders ([60327e6](https://github.com/qbitartifacts/rec-api/commit/60327e64b878e385360caba2b0a2846929963dd5))
* **pos:** added migration to save transaction_id into PaymentOrder ([766dd3e](https://github.com/qbitartifacts/rec-api/commit/766dd3eef3d79a5029de376bdab9777f4fc02fcb))
* **pos:** added missed configurations to POS module ([263f797](https://github.com/qbitartifacts/rec-api/commit/263f797ccff1a990f805e91b5108db290db3fd6e))
* **pos:** added payment url to payment orders ([66e8a42](https://github.com/qbitartifacts/rec-api/commit/66e8a4229ed1a709271c7629f920b6103f23a176))
* **pos:** implemented migration for the POS and Payment Orders ([8361a53](https://github.com/qbitartifacts/rec-api/commit/8361a5322782aec2f2639aee42f73cc8f58e355f))
* **pos:** implemented pos expire pos expire command ([8129548](https://github.com/qbitartifacts/rec-api/commit/812954843488a1a84fb73c18e98e8ff16d37898f)), closes [#294](https://github.com/qbitartifacts/rec-api/issues/294)
* **pos:** implemented pos notifications entities ([dc8b187](https://github.com/qbitartifacts/rec-api/commit/dc8b1875cf31fd90319660e68d7c4428cb1353c9)), closes [#280](https://github.com/qbitartifacts/rec-api/issues/280)
* **pos:** implemented public endpoint for payment_orders ([6bb74ef](https://github.com/qbitartifacts/rec-api/commit/6bb74ef6f76c5108c6f6591773c40e4fc6fe8f4e))
* **pos:** implemented receive payment order, closes [#276](https://github.com/qbitartifacts/rec-api/issues/276) ([1a2b8d7](https://github.com/qbitartifacts/rec-api/commit/1a2b8d72ff140ab3ad38b75f2eac0ce786dd16f6))
* **pos:** implemented refund payment orders ([8add24c](https://github.com/qbitartifacts/rec-api/commit/8add24c70e0ed5e808ed7370a4e32184b87ff7fb)), closes [#279](https://github.com/qbitartifacts/rec-api/issues/279)
* **security:** changed int ids to guid in payments to avoid bruteforce listing ([c0c127a](https://github.com/qbitartifacts/rec-api/commit/c0c127ae21d3a784ac5d8c4d06a4d222f882d917))
* added Order entity ([2744d12](https://github.com/qbitartifacts/rec-api/commit/2744d12c3bb75c700de99a71f390720b8b91ee44))
* added POS entity ([57500df](https://github.com/qbitartifacts/rec-api/commit/57500df926860dc70ef6529787ebcece93758241))
* added pos relation to Group ([f39e660](https://github.com/qbitartifacts/rec-api/commit/f39e660171f5dc90dcc885871dfd4443fdd5733c))


### Bug Fixes

* **cron_retries:** fixed bug in cron for retries ([3234dce](https://github.com/qbitartifacts/rec-api/commit/3234dce749bdc5284f5d44af8f62341d8e8f600f))
* **dependencies:** updated dependencies ([c9ae684](https://github.com/qbitartifacts/rec-api/commit/c9ae6845319eb167d8b966f8605f755cccda24a2))
* **dependencies:** updated dependencies ([18ac42c](https://github.com/qbitartifacts/rec-api/commit/18ac42c54a51a519a4450eaa8e8663664ed298dd))
* **lemonway:** added check for existing lw_balance before querying to lw ([d429faa](https://github.com/qbitartifacts/rec-api/commit/d429faab7aa8d3bd813fa0af9ceb47a94291f471)), closes [#305](https://github.com/qbitartifacts/rec-api/issues/305)
* **migrations:** fixed migrations error ([b68cf1a](https://github.com/qbitartifacts/rec-api/commit/b68cf1aadf4db45eaa8331347cec8aabc8360a38))
* **migrations:** fixed migrations error (by second time) ([28db095](https://github.com/qbitartifacts/rec-api/commit/28db0957a0285f6016fc4f751fc1feaecad989aa))
* **notifications:** added check to not notify when notification url is not present ([846e926](https://github.com/qbitartifacts/rec-api/commit/846e926b5100aee777e1f5c203b62d131baf705c)), closes [#300](https://github.com/qbitartifacts/rec-api/issues/300)
* **notifications:** added expire check to notifications after 24h ([ebebf7d](https://github.com/qbitartifacts/rec-api/commit/ebebf7dc1cfa98e34dadb13cea6c3c09a3487c47))
* **notifications:** fixed bug in http notifier ([2e11eaa](https://github.com/qbitartifacts/rec-api/commit/2e11eaa8c8d96676dac3dbf4a178c7d8e69d321f))
* **notifications:** fixed notification order ([b574f25](https://github.com/qbitartifacts/rec-api/commit/b574f25bc208a55d423284459e87612c8035936c)), closes [#281](https://github.com/qbitartifacts/rec-api/issues/281)
* **notifications:** fixes notifications issues ([#299](https://github.com/qbitartifacts/rec-api/issues/299)) ([a315de1](https://github.com/qbitartifacts/rec-api/commit/a315de132a8e79415abf4936078f782d2f3d9684))
* **pos:** added public account information to payment order ([75b723e](https://github.com/qbitartifacts/rec-api/commit/75b723e49f1c5053cd507ca719e6538a4c789a61)), closes [#289](https://github.com/qbitartifacts/rec-api/issues/289)
* **pos:** added signature_version to signing parameters ([#304](https://github.com/qbitartifacts/rec-api/issues/304)) ([3a3d654](https://github.com/qbitartifacts/rec-api/commit/3a3d654c1755863b5f5b42109f3ef92b23bb663f))
* **pos:** allowed order payment addresses into legacy call /transaction/v1/vendor ([af710ff](https://github.com/qbitartifacts/rec-api/commit/af710ff09d39a80854574642df9618812d10ca25)), closes [#288](https://github.com/qbitartifacts/rec-api/issues/288)
* **pos:** checking pin against logged-in user instead of sender (works only for admin) ([ac9673d](https://github.com/qbitartifacts/rec-api/commit/ac9673d838c27627e6c72820757a01dc7321bf5d))
* **pos:** extended POS expire time to 10min and added check for order expired addresses ([#308](https://github.com/qbitartifacts/rec-api/issues/308)) ([6d80512](https://github.com/qbitartifacts/rec-api/commit/6d805129f9865b0946255793280a95a1b947df46))
* **pos:** fixed bug with base64_decode ([d615201](https://github.com/qbitartifacts/rec-api/commit/d61520190a8c12917ef60f015d704ab6692addf3))
* **pos:** fixed error when creating paymentorder, fixes [#283](https://github.com/qbitartifacts/rec-api/issues/283) ([27a24c7](https://github.com/qbitartifacts/rec-api/commit/27a24c7ae3530ca22b15e28cf8349023fa61acb6))
* **pos:** fixed index payment orders of a pos and added testing ([27c2387](https://github.com/qbitartifacts/rec-api/commit/27c23870ed6439ce0f7729c4183f1f82d0a86ff1))
* **pos:** fixed pos expire command ([d0b8b30](https://github.com/qbitartifacts/rec-api/commit/d0b8b305be6f8ffae2219c11637446be06444df9))
* **pos:** implemented test and command to retry ([d279014](https://github.com/qbitartifacts/rec-api/commit/d2790142caaeee9122027c09cbf4301f21024e66)), closes [#281](https://github.com/qbitartifacts/rec-api/issues/281)
* **pos:** now payment orders validates either the amount is sent between quotes or not ([8f8da18](https://github.com/qbitartifacts/rec-api/commit/8f8da1856217e8e75f7e7af8ffe48d6078b8dd1d))
* **pos:** restricted only to admins index pos transactions ([a29850d](https://github.com/qbitartifacts/rec-api/commit/a29850d4da9b4173a1788a74745ba890d5f47667))
* **pos:** set notification_url not required ([02b8d95](https://github.com/qbitartifacts/rec-api/commit/02b8d950211193f44b4c2aa9fbb976b70bbab5c0))
* **pos:** set pos active by default at creation ([79fb1f7](https://github.com/qbitartifacts/rec-api/commit/79fb1f74057f9b2a257b63bc2c8738423821c019))
* **public_phone_list:** public phones and dependency updates ([#310](https://github.com/qbitartifacts/rec-api/issues/310)) ([0f54268](https://github.com/qbitartifacts/rec-api/commit/0f542686d02b2d1d3a58e4868ba9e4e4db220962))
* **refactor:** refactored tx tests ([5e240a5](https://github.com/qbitartifacts/rec-api/commit/5e240a5e6adc4e467fd4869c33709753b3ffcf54))
* **refactor:** removed unused code ([727a8e1](https://github.com/qbitartifacts/rec-api/commit/727a8e1d8155e4972c0afdc705b8d2fd17fad5c5))
* **refund:** changed pin to otp code ([44cf2fe](https://github.com/qbitartifacts/rec-api/commit/44cf2fe64dd6272334255fd66c00558126456a68)), closes [#297](https://github.com/qbitartifacts/rec-api/issues/297)
* **rest:** set created and updated public for all entities ([fc7c577](https://github.com/qbitartifacts/rec-api/commit/fc7c5770986fbf3b2fc78a03e2786a4875cf83f2))
* **rest:** set created and updated public for all entities, fixes [#277](https://github.com/qbitartifacts/rec-api/issues/277) ([fa431a4](https://github.com/qbitartifacts/rec-api/commit/fa431a4da5968a479ae12a4e0aa892898dce4e87))
* **routing:** fix order id pattern ([9203c34](https://github.com/qbitartifacts/rec-api/commit/9203c34327ef37f706938d713e4e290371bd6e6b))
* **tests:** removed old test for create payment orders ([0626dab](https://github.com/qbitartifacts/rec-api/commit/0626dabb8adb303313592971aa849c7c0a51b41e))
* **tests:** removed unused test ([e898e1a](https://github.com/qbitartifacts/rec-api/commit/e898e1a414f0a46f112ceecaaf24a8814eb87f61))
* **transactions:** fixed bug with checking limits in creating transactions ([46d27b0](https://github.com/qbitartifacts/rec-api/commit/46d27b0802ad7efc9a5acc2848481f5ab575798b))
* **version:** returned exact git abbreviated version instead of only the last tag ([71f32a9](https://github.com/qbitartifacts/rec-api/commit/71f32a99548777bf801942d0e85405ee0ac9152e))
* added status consts to Order entity ([9e93a13](https://github.com/qbitartifacts/rec-api/commit/9e93a13dd9464202381c972a933580b409f06b2a))
* added url_ok and url_ko to order ([98586a0](https://github.com/qbitartifacts/rec-api/commit/98586a0143a4a978d88a764d38d275b6473dd9ad))

## [2.17.0](https://github.com/qbitartifacts/rec-api/compare/v2.16.0...v2.17.0) (2020-03-25)


### Features

* **importing:** implemented importing for all entities ([9b3163d](https://github.com/qbitartifacts/rec-api/commit/9b3163dd82fb4b4eb922873f896e94360d1d129b))
* **importing:** implemented tests for importing ([63adb9c](https://github.com/qbitartifacts/rec-api/commit/63adb9c19b655dc8de8c8f5bd5b6502c6cc597be))
* **versioning:** created call for get the API version at /public/v1/info, fixes [#180](https://github.com/qbitartifacts/rec-api/issues/180) ([a25a368](https://github.com/qbitartifacts/rec-api/commit/a25a3683a701bfbc1aec901ff2ee41c9ca86e753))


### Bug Fixes

* **lemon_integration:** concept submitted to lemon is now the concept from the request ([40ba3e1](https://github.com/qbitartifacts/rec-api/commit/40ba3e11a6ae9fec42cb9dd4767dd3a923d4672a))
* **tests:** added test to change my user's locale ([739e911](https://github.com/qbitartifacts/rec-api/commit/739e911030cc09ccb92b307e557763ad30d11dd8))

## [2.16.0](https://github.com/qbitartifacts/rec-api/compare/v2.15.0...v2.16.0) (2020-03-24)


### Features

* **stateful:** added ability for skip status checks on stateful objects ([387fe06](https://github.com/qbitartifacts/rec-api/commit/387fe063ac48a0c05285b3831c79f4548914fb84))


### Bug Fixes

* **ibans:** missed use statefultrait ([6d9ca88](https://github.com/qbitartifacts/rec-api/commit/6d9ca882fe792918b0966617cb7e2fefaca1f0b2))
* **ibans:** missed use statefultrait (again) ([552120c](https://github.com/qbitartifacts/rec-api/commit/552120c4fd2404825238a4acc03b571815188008))
* **migrations:** changed object accessor to array accessor ([1b926f3](https://github.com/qbitartifacts/rec-api/commit/1b926f3f097e847bf559a6d2618d7af184718026))
* **migrations:** set the corresponding lemon status ([ee4e3d9](https://github.com/qbitartifacts/rec-api/commit/ee4e3d92b928d2c3fdc5fc60762640b0d19e9d1c))
* **sync:** improved lemonway synchronization to allow multi-account sync to lemon ([e87ffc5](https://github.com/qbitartifacts/rec-api/commit/e87ffc592cda9111745bb67df12c1f5139b677c9))

## [2.15.0](https://github.com/qbitartifacts/rec-api/compare/v2.14.1...v2.15.0) (2020-03-24)


### Features

* **documents:** added auto_fetched field to ibans and documents ([d02a845](https://github.com/qbitartifacts/rec-api/commit/d02a845bdb791db0cd50b55ee60029a03398ef2b))
* **documents:** added lw docs list of statuses to our documents ([840d3cf](https://github.com/qbitartifacts/rec-api/commit/840d3cf7f9f500df14b0f0b66b122e3ce71bb50f))
* **documents:** added migration to pass auto_fetched from status to flag ([092676a](https://github.com/qbitartifacts/rec-api/commit/092676a55c9ea90f9a0cba7f1a10d8a47978e315))
* **documents:** removed lemon_status from documents ([055ce2f](https://github.com/qbitartifacts/rec-api/commit/055ce2fd26f2cf29f78d3b947c98152b17e326b7))


### Bug Fixes

* **dependencies:** updated dependencies ([e1c6249](https://github.com/qbitartifacts/rec-api/commit/e1c62498531d33f320f97130320e9908baefbc8b))
* **documents:** fixed bug with auto-fetched, was not initialized ([0a4bfe7](https://github.com/qbitartifacts/rec-api/commit/0a4bfe7acfa1671e5ba507d5cea5f2ee410ed168))
* **documents:** removed auto-fetched status logic from event subscriber ([f4f8321](https://github.com/qbitartifacts/rec-api/commit/f4f83213ceb2ba2b4265d21013b1b2770528223e))
* **documents:** removed null check from document.auto_fetched ([4b107ac](https://github.com/qbitartifacts/rec-api/commit/4b107ac854c3130f79e9580387dbdb91a8397312))
* **lw_sync:** improved synchronizer ([60c0afd](https://github.com/qbitartifacts/rec-api/commit/60c0afdaa67d74e30a518fb0328b9a298acdad8c))

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
