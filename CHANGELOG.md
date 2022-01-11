# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## [2.29.0](https://github.com/qbitartifacts/rec-api/compare/v2.28.1...v2.29.0) (2022-01-11)


### Features

* added bonus_enabled for ltab and tests ([2d690ab](https://github.com/qbitartifacts/rec-api/commit/2d690abe86840bf9fd9a996838d9e8980131040c))


### Bug Fixes

* **AccountController:** check whitespaces in DNI and CIF ([#789](https://github.com/qbitartifacts/rec-api/issues/789)) ([018a239](https://github.com/qbitartifacts/rec-api/commit/018a23943e4f9bc83b71da5b20a0514e38fcdaee))
* **campaign:** add bonus_enabled in campaign ([#809](https://github.com/qbitartifacts/rec-api/issues/809)) ([616f4bd](https://github.com/qbitartifacts/rec-api/commit/616f4bd376f1119982e15a9afd962693fd200003))
* **IncomingController2:** app_root_group cant be exchanger ([#808](https://github.com/qbitartifacts/rec-api/issues/808)) ([ba3e4c5](https://github.com/qbitartifacts/rec-api/commit/ba3e4c5826e968b3b6eafb9e1e3d25e1df831d0c))
* **IncomingController:** send all ltab funds in last campaign tx ([86d52f1](https://github.com/qbitartifacts/rec-api/commit/86d52f15499aedcc6fe98d1557119cde9bff7025))
* **lemonDocument:** add new status duplicated ([#812](https://github.com/qbitartifacts/rec-api/issues/812)) ([aaff40a](https://github.com/qbitartifacts/rec-api/commit/aaff40aad5aaa97daec73947f2e23fa34a2dc610))
* **pos:** transactions panel and notifications ([#791](https://github.com/qbitartifacts/rec-api/issues/791)) ([4e20218](https://github.com/qbitartifacts/rec-api/commit/4e20218e148dae3888767b78a8b7d3dd6538456d))

### [2.28.1](https://github.com/qbitartifacts/rec-api/compare/v2.28.0...v2.28.1) (2021-12-27)

## [2.28.0](https://github.com/qbitartifacts/rec-api/compare/v2.27.0...v2.28.0) (2021-12-27)


### Features

* **AccountController:** check if account cultures is created ([#712](https://github.com/qbitartifacts/rec-api/issues/712)) ([49b3877](https://github.com/qbitartifacts/rec-api/commit/49b3877def78ab5070180e3416d71038f38a8334))
* **AccountController:** update tos by campaign_code ([#718](https://github.com/qbitartifacts/rec-api/issues/718)) ([bf3a7f3](https://github.com/qbitartifacts/rec-api/commit/bf3a7f3b7b31b298cfe5623c290cb66b49dd7e9a))
* **AccountsController:** return array for campaigns ([#674](https://github.com/qbitartifacts/rec-api/issues/674)) ([eaf370f](https://github.com/qbitartifacts/rec-api/commit/eaf370fa90766da883da29385bd78a3f3cec3889))
* **activity:** migration for parent field to be self-referencing ([#687](https://github.com/qbitartifacts/rec-api/issues/687)) ([d7fdbb8](https://github.com/qbitartifacts/rec-api/commit/d7fdbb874e3437d84849e96dee60c7529cabf5af))
* **Activity:** code for parent self-referencing  ([#686](https://github.com/qbitartifacts/rec-api/issues/686)) ([9827943](https://github.com/qbitartifacts/rec-api/commit/98279430e4dca885ada03d794188b3a3db212275))
* **ActivityController:** admin v4 activity search ([#744](https://github.com/qbitartifacts/rec-api/issues/744)) ([e628055](https://github.com/qbitartifacts/rec-api/commit/e628055fb78ca51cedb16975f15a3a0b2623733a))
* **ActivityController:** created /public/v4/activities/search endpoint ([#714](https://github.com/qbitartifacts/rec-api/issues/714)) ([1881bae](https://github.com/qbitartifacts/rec-api/commit/1881bae2f8c4a981f0c655cb7524ac1c374be50a))
* **ActivityController:** make public GET activity ([#663](https://github.com/qbitartifacts/rec-api/issues/663)) ([ba8be88](https://github.com/qbitartifacts/rec-api/commit/ba8be8862b76c88c6e2c461d575d3cc0823d38c9))
* **campaign:** added url_tos ([#698](https://github.com/qbitartifacts/rec-api/issues/698)) ([2c918bf](https://github.com/qbitartifacts/rec-api/commit/2c918bfa08ca352729a3c206f47b8cd64043063c))
* **campaign:** migration file to add tos_url ([#704](https://github.com/qbitartifacts/rec-api/issues/704)) ([b81d11e](https://github.com/qbitartifacts/rec-api/commit/b81d11e73c49890e9f36276f8df0fd40aa890de5))
* **Campaign:** changes culture campaign name ([#729](https://github.com/qbitartifacts/rec-api/issues/729)) ([043736d](https://github.com/qbitartifacts/rec-api/commit/043736d3f3036ab07574c51526ce17b014bee0a4))
* **checkPhone:** allow only spanish phone number ([#730](https://github.com/qbitartifacts/rec-api/issues/730)) ([c59d940](https://github.com/qbitartifacts/rec-api/commit/c59d9401fc0558a09bf709b5af392cd84f16fc1a))
* **config:** reduced token TTL ([#775](https://github.com/qbitartifacts/rec-api/issues/775)) ([51144e6](https://github.com/qbitartifacts/rec-api/commit/51144e6cb36b27a7033d3c9463e519ca9e35ba11))
* **Config:** created config entity and fix translation test([#675](https://github.com/qbitartifacts/rec-api/issues/675)) ([1cdeb65](https://github.com/qbitartifacts/rec-api/commit/1cdeb656e98352894d863c36eb4035f66f4aafd8))
* **cron:** save logs in custom files ([#710](https://github.com/qbitartifacts/rec-api/issues/710)) ([ade97ba](https://github.com/qbitartifacts/rec-api/commit/ade97bacafd38543294cf4afa6968b10dfedb5ee))
* **crons:** save cron output in logs ([#653](https://github.com/qbitartifacts/rec-api/issues/653)) ([857e83b](https://github.com/qbitartifacts/rec-api/commit/857e83bb3adcc95c75f96c8fa6901c339e18d282))
* **culture campaign:** created campaign ([#659](https://github.com/qbitartifacts/rec-api/issues/659)) ([21f31a0](https://github.com/qbitartifacts/rec-api/commit/21f31a04b404388d01c7cacc3a1b4e219195b99f))
* **documents:** cron to check expired documents ([#694](https://github.com/qbitartifacts/rec-api/issues/694)) ([7ed05fb](https://github.com/qbitartifacts/rec-api/commit/7ed05fb016d87c5d715fa9721c8c2ec024ce3525))
* **IncomingController:** avoid sum ltab redeamable when culture money-in ([#741](https://github.com/qbitartifacts/rec-api/issues/741)) ([60b27c4](https://github.com/qbitartifacts/rec-api/commit/60b27c444ed4bbe2395ed8ddef4818e8107bb0e5))
* **IncomingController:** changed tx order to test culture bonus ([#708](https://github.com/qbitartifacts/rec-api/issues/708)) ([6c071d2](https://github.com/qbitartifacts/rec-api/commit/6c071d27ae5000b63bc9b3670aefa9b37bcaadb5))
* **IncomingController:** create culture tx in checkFiatCommand ([#716](https://github.com/qbitartifacts/rec-api/issues/716)) ([bd0a5f9](https://github.com/qbitartifacts/rec-api/commit/bd0a5f942d16161ad46cd54da1e4d87f57d1eea3))
* **IncomingController:** set auto. cultural exchanger and cultural circuit closed  ([#739](https://github.com/qbitartifacts/rec-api/issues/739)) ([2249285](https://github.com/qbitartifacts/rec-api/commit/224928565d6da8ed7cdf8124907984b59f1458fe))
* **kyc:** enable gender nullable ([#780](https://github.com/qbitartifacts/rec-api/issues/780)) ([6ebdc02](https://github.com/qbitartifacts/rec-api/commit/6ebdc0236cde94d91a2b52a9a58a16c77e43dc0e))
* **KYC:** restrict values for gender ([#754](https://github.com/qbitartifacts/rec-api/issues/754)) ([cb3909d](https://github.com/qbitartifacts/rec-api/commit/cb3909d08b26947d4a9fa5d284552afefdfa8112))
* **map_search:** returns only active accounts ([#740](https://github.com/qbitartifacts/rec-api/issues/740)) ([7ee77ea](https://github.com/qbitartifacts/rec-api/commit/7ee77eab8bfecb2874662bc33266f1f024016fb0))
* **MapController:** public map filter by active accounts ([#749](https://github.com/qbitartifacts/rec-api/issues/749)) ([be48bbb](https://github.com/qbitartifacts/rec-api/commit/be48bbbe2d351d210995c89af710e05c2fee2b16))
* **PublicMapSearch:** search by campaign code ([#709](https://github.com/qbitartifacts/rec-api/issues/709)) ([27ac04f](https://github.com/qbitartifacts/rec-api/commit/27ac04fbafca647d4afd1944be5d75c32b70c231))


### Bug Fixes

* **AccountController:** accept TOS error ([#723](https://github.com/qbitartifacts/rec-api/issues/723)) ([8360fa9](https://github.com/qbitartifacts/rec-api/commit/8360fa9e07e25f5ae52d73cc105166c3c0e6c54e))
* **AccountController:** fixed update user and run all tests after ([#726](https://github.com/qbitartifacts/rec-api/issues/726)) ([4e3ad4b](https://github.com/qbitartifacts/rec-api/commit/4e3ad4b9cd4f70e51f5100c83a00c89d330690d4))
* **accounts:** has_offers bug fixed ([#691](https://github.com/qbitartifacts/rec-api/issues/691)) ([bb0e7a0](https://github.com/qbitartifacts/rec-api/commit/bb0e7a08107e3c32734814802ed2786354da6f51))
* **AccountsController:** check activity_id null ([#667](https://github.com/qbitartifacts/rec-api/issues/667)) ([21a1439](https://github.com/qbitartifacts/rec-api/commit/21a1439c18b408999ce057d50d521171937c159b))
* **AccountsController:** not send has_offers true if it is expired ([#774](https://github.com/qbitartifacts/rec-api/issues/774)) ([5ab6b19](https://github.com/qbitartifacts/rec-api/commit/5ab6b19554d11c6a61ae85dd6b440275e153bfe4))
* **Activity:** exclude products ([#762](https://github.com/qbitartifacts/rec-api/issues/762)) ([56b3dc9](https://github.com/qbitartifacts/rec-api/commit/56b3dc93fcad40ac1145263b5232f5707c87d2ee))
* **ActivityController:** return only culture activities ([#673](https://github.com/qbitartifacts/rec-api/issues/673)) ([82b47b4](https://github.com/qbitartifacts/rec-api/commit/82b47b41183c404cfb8624c8b2d657c2cd156794))
* **ActivityController:** use secureOutput ([#757](https://github.com/qbitartifacts/rec-api/issues/757)) ([69c9078](https://github.com/qbitartifacts/rec-api/commit/69c9078007f4c5e5c578668e8311e2a9431c89bb))
* **campaign:** add code field to campaign ([#679](https://github.com/qbitartifacts/rec-api/issues/679)) ([62f668c](https://github.com/qbitartifacts/rec-api/commit/62f668cd5385b9785e3e36d1ab9e83ea92bde47d))
* **campaign:** added code in campaign table ([#681](https://github.com/qbitartifacts/rec-api/issues/681)) ([90b8039](https://github.com/qbitartifacts/rec-api/commit/90b8039ebbed0b9a0cd100538ac19e843ed9720f))
* **campaign:** check campaign name to create culture account ([#711](https://github.com/qbitartifacts/rec-api/issues/711)) ([c8ab890](https://github.com/qbitartifacts/rec-api/commit/c8ab89074bed29b6407171cc72f70bc5e19e03c5))
* **campaign:** delete migration create code not null([#684](https://github.com/qbitartifacts/rec-api/issues/684)) ([f253a2a](https://github.com/qbitartifacts/rec-api/commit/f253a2a56b6b2940c741f7d755fac5f316ca6442))
* **campaign:** make code nullable to avoid incompatibilities ([#682](https://github.com/qbitartifacts/rec-api/issues/682)) ([c65f7cd](https://github.com/qbitartifacts/rec-api/commit/c65f7cd5f9d7a87eb37c77872e0b68ef435dd0fc))
* **campaign:** migration to add code field in campaign ([#678](https://github.com/qbitartifacts/rec-api/issues/678)) ([1df02ce](https://github.com/qbitartifacts/rec-api/commit/1df02ce96c8b45fd8559cefe39423d16c6cb4292))
* **cron:** fix error saving logs in custom files ([#715](https://github.com/qbitartifacts/rec-api/issues/715)) ([9143d22](https://github.com/qbitartifacts/rec-api/commit/9143d22112440a35a2d62abc7d7f1a203a40f43d))
* **document:** allows from approved to expired ([#764](https://github.com/qbitartifacts/rec-api/issues/764)) ([bbfee37](https://github.com/qbitartifacts/rec-api/commit/bbfee37391f19da3dbf44e6737b08b2c56bce458))
* **IncomingController:** all culture constraints and tests ([#790](https://github.com/qbitartifacts/rec-api/issues/790)) ([0a31280](https://github.com/qbitartifacts/rec-api/commit/0a31280d6cf88613c4d8ecab1f13fa9b1816ed34))
* **IncomingController:** calculate tx amount when reduce redeamable ([#668](https://github.com/qbitartifacts/rec-api/issues/668)) ([2cc6021](https://github.com/qbitartifacts/rec-api/commit/2cc6021e64c583a165b35fa693301bef9cdb66b7))
* **IncomingController:** culture campaign constrains([#766](https://github.com/qbitartifacts/rec-api/issues/766)) ([fb3ebbe](https://github.com/qbitartifacts/rec-api/commit/fb3ebbe932323c8250a08e6803d36d452a528434))
* **IncomingController:** fix bug culture bonificaction ([#717](https://github.com/qbitartifacts/rec-api/issues/717)) ([aac5dc9](https://github.com/qbitartifacts/rec-api/commit/aac5dc9c64b8440fed7904821035fe0470278d78))
* **IncomingController:** get receiver_id from group ([#756](https://github.com/qbitartifacts/rec-api/issues/756)) ([f52b294](https://github.com/qbitartifacts/rec-api/commit/f52b2941c8dd4d0159d58b0c2f13dd47dd4af2c0))
* **kyc:** add zip to kyc ([#747](https://github.com/qbitartifacts/rec-api/issues/747)) ([4f71a54](https://github.com/qbitartifacts/rec-api/commit/4f71a54a74f284d3e2b39f7fc3c8cc6ce8bf0abc))
* **kyc:** created update kyc v3 for users and tests ([#750](https://github.com/qbitartifacts/rec-api/issues/750)) ([2433aa1](https://github.com/qbitartifacts/rec-api/commit/2433aa17abe9db4f10e6c9372e7d1d6173c0ce1d))
* **kyc:** null gender migrations file ([#786](https://github.com/qbitartifacts/rec-api/issues/786)) ([9e15fd7](https://github.com/qbitartifacts/rec-api/commit/9e15fd73014305c3e89d0ec6fa5b3da5c53314f4))
* **kyc:** table kyc add zip migrations file ([#746](https://github.com/qbitartifacts/rec-api/issues/746)) ([8271893](https://github.com/qbitartifacts/rec-api/commit/8271893d20dd4e09dc66d00a230d3d274f75c0bc))
* **login:** refactor and add new platforms ([#680](https://github.com/qbitartifacts/rec-api/issues/680)) ([4952b4d](https://github.com/qbitartifacts/rec-api/commit/4952b4d0c5e1e008e50fe1002d5b8428b10f6a8c))
* **LTAB constraint:** fixed testPayKycCheck for reduce reedemable ([#669](https://github.com/qbitartifacts/rec-api/issues/669)) ([6b1f220](https://github.com/qbitartifacts/rec-api/commit/6b1f2202bfe4308137e3b220103bf5ef71b1ea4f))
* **offers:** manage permissions and tests ([#763](https://github.com/qbitartifacts/rec-api/issues/763)) ([f45063f](https://github.com/qbitartifacts/rec-api/commit/f45063faa8cbe557e1efe6b57c916abd7706dbd2))
* **recover_password:** match by username and test ([#688](https://github.com/qbitartifacts/rec-api/issues/688)) ([193656f](https://github.com/qbitartifacts/rec-api/commit/193656fae116df8566844ddaa906f957ea9c0c4d))
* **tests:** disabled tests to avoid github errors ([#735](https://github.com/qbitartifacts/rec-api/issues/735)) ([8883922](https://github.com/qbitartifacts/rec-api/commit/888392235fa7dc40c3d94735ae29176036ab2af8))
* **tests:** fixing testing mongodb connection issues ([c8429dd](https://github.com/qbitartifacts/rec-api/commit/c8429ddfa94892ee526a02044874e29053c8fc73))
* **tests:** removed clear cache to speedup tests ([#731](https://github.com/qbitartifacts/rec-api/issues/731)) ([b1a1f88](https://github.com/qbitartifacts/rec-api/commit/b1a1f88774b6e6ab94abf98ff16f2d081f4c0e20))
* **token:** restore token lifetime ([#788](https://github.com/qbitartifacts/rec-api/issues/788)) ([f64bf90](https://github.com/qbitartifacts/rec-api/commit/f64bf9058a2edef0e3b64ccb8b6ee4856eb91526))
* **transaction:** fix pin validation ([#692](https://github.com/qbitartifacts/rec-api/issues/692)) ([d7bb5f6](https://github.com/qbitartifacts/rec-api/commit/d7bb5f6f459714311d5d1b9bd829954ea46d8581))
* **UsersGroupsController:** add user to account check by getId ([#782](https://github.com/qbitartifacts/rec-api/issues/782)) ([9bfd6c0](https://github.com/qbitartifacts/rec-api/commit/9bfd6c080dd480e429ff99d54f6bedc6c3564c05))

## [2.27.0](https://github.com/qbitartifacts/rec-api/compare/v2.26.2...v2.27.0) (2021-10-06)


### Features

* **WalletController:** returns only KYC2 exchangers ([#654](https://github.com/qbitartifacts/rec-api/issues/654)) ([7da57f2](https://github.com/qbitartifacts/rec-api/commit/7da57f248a2b6c2578ab57c524b87c365d49287d))
* added sub-activities ([#640](https://github.com/qbitartifacts/rec-api/issues/640)) ([00f8722](https://github.com/qbitartifacts/rec-api/commit/00f8722f6523fbf041625ec85d184ae0fd0c050c))


### Bug Fixes

* **AccountsController:** fix duplicated companies in map ([#657](https://github.com/qbitartifacts/rec-api/issues/657)) ([eed5693](https://github.com/qbitartifacts/rec-api/commit/eed5693ed829aaad5bceea7053b86d338934002b))
* **AccountsController:** fix null on getid ([#656](https://github.com/qbitartifacts/rec-api/issues/656)) ([dac5fb5](https://github.com/qbitartifacts/rec-api/commit/dac5fb5c748e26d462db47f4e0f5c32fadc6bf1d))
* **docs:** fixed docker base image ([950c902](https://github.com/qbitartifacts/rec-api/commit/950c902a0ac26082907e5c7eb64f96de3e1725e2))
* **offer:** test create, discount to decimal, more fixtures ([#639](https://github.com/qbitartifacts/rec-api/issues/639)) ([1a73c6a](https://github.com/qbitartifacts/rec-api/commit/1a73c6a85ea192bfcf1076e1f2b72729cd450005))
* **offers:** change discount to float ([#648](https://github.com/qbitartifacts/rec-api/issues/648)) ([999de8d](https://github.com/qbitartifacts/rec-api/commit/999de8d86ffe9382727e42e6162023fa518a3bf1))
* **offers:** check params before save and fix some tests ([#652](https://github.com/qbitartifacts/rec-api/issues/652)) ([f574a84](https://github.com/qbitartifacts/rec-api/commit/f574a84199df88d7acd589393b42915f227d19a1))
* **register:** accept nif registering a company ([#635](https://github.com/qbitartifacts/rec-api/issues/635)) ([8ddf70d](https://github.com/qbitartifacts/rec-api/commit/8ddf70df50fdfd788b163841bb534955074f962d))
* **tests:** account tests for suffix "_id" ([#615](https://github.com/qbitartifacts/rec-api/issues/615)) ([cac7a47](https://github.com/qbitartifacts/rec-api/commit/cac7a4717c5db172babf1c0f293348c028392ab6))
* **version:** login up to version 201 ([#660](https://github.com/qbitartifacts/rec-api/issues/660)) ([df99107](https://github.com/qbitartifacts/rec-api/commit/df991070c0abd067877aea7b57f79b367fbc7ced))

### [2.26.2](https://github.com/qbitartifacts/rec-api/compare/v2.26.1...v2.26.2) (2021-09-09)


### Bug Fixes

* **bonifications:** bonifications going to final user, not to commerce ([#641](https://github.com/qbitartifacts/rec-api/issues/641)) ([e4c7aa7](https://github.com/qbitartifacts/rec-api/commit/e4c7aa77f0a959a96ffa91dc3a251368121cc9dd))
* **offer:** Modified Offers, new types and logic for create and update ([#612](https://github.com/qbitartifacts/rec-api/issues/612)) ([e00979c](https://github.com/qbitartifacts/rec-api/commit/e00979cd534447bb6b5858c1bc23c035df28bfde))
* **offers:** changed discount from decimal to string ([#647](https://github.com/qbitartifacts/rec-api/issues/647)) ([e59b567](https://github.com/qbitartifacts/rec-api/commit/e59b567fe2f1c4a22e5a834cbc2cd460dba2742f))
* re-rollback  - bonifications goes to commerce, not to final user ([c965e3d](https://github.com/qbitartifacts/rec-api/commit/c965e3daecc7223028d595d534c285270a2e4b1a))
* save user in documents from app ([#634](https://github.com/qbitartifacts/rec-api/issues/634)) ([9a6ed82](https://github.com/qbitartifacts/rec-api/commit/9a6ed82a8c00a4d6c76089036642bf499dfafc3f))

### [2.26.1](https://github.com/qbitartifacts/rec-api/compare/v2.26.0...v2.26.1) (2021-08-05)


### Bug Fixes

* **delegated_exchange:** fix concept of internal tx ([#613](https://github.com/qbitartifacts/rec-api/issues/613)) ([c973fbd](https://github.com/qbitartifacts/rec-api/commit/c973fbdcfa5e338c4d7d52af0805bf735f685b55))

## [2.26.0](https://github.com/qbitartifacts/rec-api/compare/v2.25.1...v2.26.0) (2021-08-04)


### Features

* **DelegatedChange:** Added massive transactions ([#579](https://github.com/qbitartifacts/rec-api/issues/579)) ([d92330f](https://github.com/qbitartifacts/rec-api/commit/d92330fe46ef5cbabcb44a390e1ce7e46219b4d3))


### Bug Fixes

* **documents:** filtering by user_id and other fields finished in _id ([#604](https://github.com/qbitartifacts/rec-api/issues/604)) ([98dcb78](https://github.com/qbitartifacts/rec-api/commit/98dcb78e1bd57f952411c06d6b49da2f89ea4779))
* **documents:** relationship btw documents and users ([#609](https://github.com/qbitartifacts/rec-api/issues/609)) ([7d4f1c6](https://github.com/qbitartifacts/rec-api/commit/7d4f1c6097b3496d57e285c307973c73ce6da8af))
* **ltab:** avoid user pay in your own shop to get reward ([#602](https://github.com/qbitartifacts/rec-api/issues/602)) ([b30faff](https://github.com/qbitartifacts/rec-api/commit/b30fafff3132b63c0c09dcc609de88e60041c8b9))
* **reports:** added cif and id in the csv report ([#608](https://github.com/qbitartifacts/rec-api/issues/608)) ([e1805a1](https://github.com/qbitartifacts/rec-api/commit/e1805a1151d993c33eddb25dc7083038c1665501))

### [2.25.1](https://github.com/qbitartifacts/rec-api/compare/v2.25.0...v2.25.1) (2021-07-06)


### Bug Fixes

* **login:** change check login errors ([#598](https://github.com/qbitartifacts/rec-api/issues/598)) ([1c678c](https://github.com/qbitartifacts/rec-api/commit/1c678c6da3985dd79d11f560600fe68481ccd07b))

## [2.25.0](https://github.com/qbitartifacts/rec-api/compare/v2.24.0...v2.25.0) (2021-07-02)

### Features

* **AccountsController:** filter active offers ([#558](https://github.com/qbitartifacts/rec-api/issues/558)) ([2c1270f](https://github.com/qbitartifacts/rec-api/commit/2c1270f7ec38326f991391c656019d7e1c170726))
* **IncommingController:** reward LTAB rounded to 2 decimals([#566](https://github.com/qbitartifacts/rec-api/issues/566)) ([25e171b](https://github.com/qbitartifacts/rec-api/commit/25e171b7f7ba76d93407bf53a736aa7d6dfae66a))


### Bug Fixes

* **AccountController:** fixed unlockUser ([#576](https://github.com/qbitartifacts/rec-api/issues/576)) ([40bac80](https://github.com/qbitartifacts/rec-api/commit/40bac805d7fbe6c2f98d5f8151a873d3256089bd))
* **admin:** fix login k panel after be logged in app with personal account ([#585](https://github.com/qbitartifacts/rec-api/issues/585)) ([ca1aef2](https://github.com/qbitartifacts/rec-api/commit/ca1aef21046da3144bceafec50ed19da324ca27c))
* **admin:** return enabled field in show and index and added tests ([#596](https://github.com/qbitartifacts/rec-api/issues/596)) ([8b60e28](https://github.com/qbitartifacts/rec-api/commit/8b60e28b4282fe16edb6795fc0bfbbb0db093ed3))
* **IncomingController:** fixed attempts count ([#567](https://github.com/qbitartifacts/rec-api/issues/567)) ([51785f5](https://github.com/qbitartifacts/rec-api/commit/51785f581304dd3514a44841ee6537895542cbb7))
* **login:** catch more error cases and more tests ([#595](https://github.com/qbitartifacts/rec-api/issues/595)) ([6332e5e](https://github.com/qbitartifacts/rec-api/commit/6332e5e618c65090943afff402655a581db7376d))
* **login:** change security check order and fix messages ([#589](https://github.com/qbitartifacts/rec-api/issues/589)) ([b82cceb](https://github.com/qbitartifacts/rec-api/commit/b82ccebb566bc1cf00aea53ca6a2ddf7d33d0c11))
* **security:** limit sms attemps and tests ([#592](https://github.com/qbitartifacts/rec-api/issues/592)) ([494f3ac](https://github.com/qbitartifacts/rec-api/commit/494f3ac60c3badfd27858f27820c75d7d3a26b01))
* **tests:** create test to try export users call with params ([#593](https://github.com/qbitartifacts/rec-api/issues/593)) ([e360bde](https://github.com/qbitartifacts/rec-api/commit/e360bde1bdd7ab258b37f92e9e61c1d5e24107d0))
* **version:** bumped minimum version for android to 200 ([#580](https://github.com/qbitartifacts/rec-api/issues/580)) ([2a65429](https://github.com/qbitartifacts/rec-api/commit/2a65429a577296d807d281f1e76dd5f0ea286792))

## [2.24.0](https://github.com/qbitartifacts/rec-api/compare/v2.23.0...v2.24.0) (2021-06-07)


### Features

* **AccountController:** change pass and pin v4 ([#526](https://github.com/qbitartifacts/rec-api/issues/526)) ([3aa7d91](https://github.com/qbitartifacts/rec-api/commit/3aa7d91f39233aa150d1fa6997b42223fdc7a1ec))
* **AccountController:** changed  update document endpoint ([#548](https://github.com/qbitartifacts/rec-api/issues/548)) ([776705f](https://github.com/qbitartifacts/rec-api/commit/776705f9f80cfb047892b23c208d9014c8b88041))
* **AccountController:** changed document return data ([#547](https://github.com/qbitartifacts/rec-api/issues/547)) ([8fee444](https://github.com/qbitartifacts/rec-api/commit/8fee44452b84839c68d6ddbac6b2786a5397d320))
* **AccountController:** created /app/v4/recover-password endpoint ([a427cc0](https://github.com/qbitartifacts/rec-api/commit/a427cc09cf7bf5653353a899b893c9746a5e86d1))
* **AccountController:** validate phone ([#517](https://github.com/qbitartifacts/rec-api/issues/517)) ([45cfee1](https://github.com/qbitartifacts/rec-api/commit/45cfee15fd9d33088121f2ea0434b0178ac11261))
* **accounts:** added filter for inactive accounts ([#483](https://github.com/qbitartifacts/rec-api/issues/483)) ([2a9bba0](https://github.com/qbitartifacts/rec-api/commit/2a9bba0243e1a300e89137c66a5b5f74b9fba864))
* **accounts:** use secureOutput ([#488](https://github.com/qbitartifacts/rec-api/issues/488)) ([fe56256](https://github.com/qbitartifacts/rec-api/commit/fe56256e8cb7df17a139c87c554e55253f707e1e))
* **company account:** removed some params to create new account ([#557](https://github.com/qbitartifacts/rec-api/issues/557)) ([05f0c7b](https://github.com/qbitartifacts/rec-api/commit/05f0c7b7ba1d8aee9d407a03cc5b4274555207fc))
* **documents:** added nuew fields in Document.php and DocumentKind.php ([#536](https://github.com/qbitartifacts/rec-api/issues/536)) ([78b336f](https://github.com/qbitartifacts/rec-api/commit/78b336f288311986cb975e342a00635a5004e413))
* **Entities:** imports and duplicated definitions ([b2e2171](https://github.com/qbitartifacts/rec-api/commit/b2e2171fb7a6da8e42ed5d6d961a39dcbcc6347b))
* **IncomingController:** added in response extra_data object ([#550](https://github.com/qbitartifacts/rec-api/issues/550)) ([1ac7db6](https://github.com/qbitartifacts/rec-api/commit/1ac7db695c36f1ee4ee575aee5062ef0f84d133e))
* **kyc:**  changed sms code format to 6 digits ([#475](https://github.com/qbitartifacts/rec-api/issues/475)) ([2bd471e](https://github.com/qbitartifacts/rec-api/commit/2bd471eae8d492c86da7af41a940e84e1176176f))
* **LW_recharge:** dissable recharge ([#514](https://github.com/qbitartifacts/rec-api/issues/514)) ([daedffd](https://github.com/qbitartifacts/rec-api/commit/daedffdef9e86fcd93c553a3953390f26a64b38f))
* **map_v4:** info ([#497](https://github.com/qbitartifacts/rec-api/issues/497)) ([94e7481](https://github.com/qbitartifacts/rec-api/commit/94e74819bba3b72674300177c01ddb2c8ba59e34))
* **OfferController:** offer crud V4 ([#530](https://github.com/qbitartifacts/rec-api/issues/530)) ([4d9b933](https://github.com/qbitartifacts/rec-api/commit/4d9b93339e522fb2f453776fb5f1ae5c7e1b9ed8))
* **recharge:** allow recharge rec ([#525](https://github.com/qbitartifacts/rec-api/issues/525)) ([ef9938c](https://github.com/qbitartifacts/rec-api/commit/ef9938c0f7704f55f11b6ddaaeedcc22e3cdf02d))
* **register:** implemented v4 register ([#478](https://github.com/qbitartifacts/rec-api/issues/478)) ([8343076](https://github.com/qbitartifacts/rec-api/commit/83430767e59eec5cf19f7d1be20a135f08f64c16))
* **register_v4:** save sms logs ([#521](https://github.com/qbitartifacts/rec-api/issues/521)) ([068bf2f](https://github.com/qbitartifacts/rec-api/commit/068bf2f533e1f6204770a6fcfcd6342a3861421d))
* **sms_text:** changed sms text ([#477](https://github.com/qbitartifacts/rec-api/issues/477)) ([ad5e8f7](https://github.com/qbitartifacts/rec-api/commit/ad5e8f7b636f9771e482ec772763ba48c6388b6b))
* **sms-code:** recovery v4 ([#495](https://github.com/qbitartifacts/rec-api/issues/495)) ([40464e7](https://github.com/qbitartifacts/rec-api/commit/40464e7ea3755fe725edbcf600933a709ba7a717))
* **table_name:** use camelcase ([#513](https://github.com/qbitartifacts/rec-api/issues/513)) ([f5935ec](https://github.com/qbitartifacts/rec-api/commit/f5935ec38c20e70a969f1624d2e6765fd5f0090b))
* **transaction:** changed ([#500](https://github.com/qbitartifacts/rec-api/issues/500)) ([cfda4cc](https://github.com/qbitartifacts/rec-api/commit/cfda4cca8be5efe4b1261588db4e0f5d13529a3e))
* **transactions:** added concept to out transactions ([#480](https://github.com/qbitartifacts/rec-api/issues/480)) ([f5803ab](https://github.com/qbitartifacts/rec-api/commit/f5803ab33606d81d5559389c00c402238ddd928f))
* **User:** created log in test ([1359a8b](https://github.com/qbitartifacts/rec-api/commit/1359a8b5a1c8ccbbf0394b41df8cf7563bc3780c))
* **User:** created new variables ([592a57b](https://github.com/qbitartifacts/rec-api/commit/592a57bc7bc2f3edae629379a21a47545dde78a4))
* **User:** set status submitted on document update, security fields hidded ([#555](https://github.com/qbitartifacts/rec-api/issues/555)) ([2e917a6](https://github.com/qbitartifacts/rec-api/commit/2e917a6cee97234f4ccd45a3b30129d3ba046e32))
* **UserSecurityConfig:** created new entity ([821cf39](https://github.com/qbitartifacts/rec-api/commit/821cf3908d76dcee6589acbf8ff52499acdada34))
* **UserSecurityTest:** created testPasswordRecovery ([5621bc7](https://github.com/qbitartifacts/rec-api/commit/5621bc770703b04dedf19b811c8e80e4fe2c94f8))
* **UserSecurityTest:** implemented testLogIn ([2a43c0d](https://github.com/qbitartifacts/rec-api/commit/2a43c0d4df33a85873e7126455ab022babbbd85a))
* **UsersSmsLogs:** created new entity ([0e84c19](https://github.com/qbitartifacts/rec-api/commit/0e84c192a4787af343b6404d9163bcb8cf224cf6))
* **wallet:** return error_description in listCommerce ([#516](https://github.com/qbitartifacts/rec-api/issues/516)) ([5dd3732](https://github.com/qbitartifacts/rec-api/commit/5dd37324eebb5f73ec8e6940c984db2fc931f2e0))


### Bug Fixes

* **accounts:** fixed cif filter ([#490](https://github.com/qbitartifacts/rec-api/issues/490)) ([ef81e8b](https://github.com/qbitartifacts/rec-api/commit/ef81e8b37cad02fee0c67961ffbb0cb6cc3acd27))
* **Campaign:** made image_url and vide_promo_url public ([#551](https://github.com/qbitartifacts/rec-api/issues/551)) ([72c3f83](https://github.com/qbitartifacts/rec-api/commit/72c3f830d71fd5eaefbaa078f01e89badb85e480))
* **kyc:** fixed document null status_text ([#546](https://github.com/qbitartifacts/rec-api/issues/546)) ([ddc4cd6](https://github.com/qbitartifacts/rec-api/commit/ddc4cd63154516ac2bf774e811b466b246a9685b))
* **map:** optimize search map ([#494](https://github.com/qbitartifacts/rec-api/issues/494)) ([02505ea](https://github.com/qbitartifacts/rec-api/commit/02505ea106b9d9620d907c3510aa6ff5fbfaed36))
* **security:** added again /notification endpoint in app/security.yml ([#549](https://github.com/qbitartifacts/rec-api/issues/549)) ([61fa650](https://github.com/qbitartifacts/rec-api/commit/61fa650f2fa473c4f8e818ced1c15ca2a4b7c1ec))
* **security:** fixed wrong accesspoint url ([#501](https://github.com/qbitartifacts/rec-api/issues/501)) ([2af24a0](https://github.com/qbitartifacts/rec-api/commit/2af24a009077050af5063517830f8febd299c56e))
* **security:** fixed wrong accesspoint url ([#502](https://github.com/qbitartifacts/rec-api/issues/502)) ([973e490](https://github.com/qbitartifacts/rec-api/commit/973e49000dc0fdafb9b472ed6bacff66e81a1865))
* **user:** get users old_data from id instead of caller ([#471](https://github.com/qbitartifacts/rec-api/issues/471)) ([cc92bae](https://github.com/qbitartifacts/rec-api/commit/cc92bae2dc7d91c70e5bfabddfc18354a3f6bfeb))
* **users:** validate phone exec time ([#473](https://github.com/qbitartifacts/rec-api/issues/473)) ([86502b6](https://github.com/qbitartifacts/rec-api/commit/86502b6a6d11db29cd75913800a8c2553b0b277b))

## [2.23.0](https://github.com/qbitartifacts/rec-api/compare/v2.22.0...v2.23.0) (2021-03-31)


### Features

* **accounts:** pdf reports ([#460](https://github.com/qbitartifacts/rec-api/issues/460)) ([e6ca3df](https://github.com/qbitartifacts/rec-api/commit/e6ca3dfd8e28a633eca0ea5055b24b467cc5fa21))


### Bug Fixes

* **accounts:** changed email attachments ([#464](https://github.com/qbitartifacts/rec-api/issues/464)) ([9e63a3d](https://github.com/qbitartifacts/rec-api/commit/9e63a3db489805617d8b89c37c1c878df1ce7e34))
* **accounts:** changed filter params ([#467](https://github.com/qbitartifacts/rec-api/issues/467)) ([3ef603c](https://github.com/qbitartifacts/rec-api/commit/3ef603cb01b417b1f7ab28a62b71d457f561f158))
* **accounts:** minor changes ([#461](https://github.com/qbitartifacts/rec-api/issues/461)) ([cbb9064](https://github.com/qbitartifacts/rec-api/commit/cbb90641df27843d90ed879af4e6c395cc00048b)), closes [#46](https://github.com/qbitartifacts/rec-api/issues/46)
* **accounts:** use LTAB Account to send email ([#463](https://github.com/qbitartifacts/rec-api/issues/463)) ([feb5de5](https://github.com/qbitartifacts/rec-api/commit/feb5de5b381ad54f0a52dd4266807ebc0b665ceb))
* **AccountsController:** changed file location ([#466](https://github.com/qbitartifacts/rec-api/issues/466)) ([4fe767d](https://github.com/qbitartifacts/rec-api/commit/4fe767d51cc8155e267a6d334d9f13cb667a89d7))
* **deps:** bumped version in composer json ([ac26bbb](https://github.com/qbitartifacts/rec-api/commit/ac26bbb2499175719d2c15a363d2c4b5a2f17a79))
* **deps:** updated composer deps ([25ebcad](https://github.com/qbitartifacts/rec-api/commit/25ebcade47c74092a5808805fac543ec6c83a18e))
* **mailing:** csv content ([#465](https://github.com/qbitartifacts/rec-api/issues/465)) ([599a22f](https://github.com/qbitartifacts/rec-api/commit/599a22f3d3171092fab06ff749f6a960179b9f6b))

## [2.22.0](https://github.com/qbitartifacts/rec-api/compare/v2.21.0...v2.22.0) (2021-03-08)


### Features

* **crontab:** added rec:lemon:check:balance command to crontab ([df37a85](https://github.com/qbitartifacts/rec-api/commit/df37a85acd6e546d8cbf1de778d7fcd182408479))
* **Group:** listCommerce method return only active exchangers ([3f72ca8](https://github.com/qbitartifacts/rec-api/commit/3f72ca8488946d1d8a5dba21ff02d63c0efc005e))
* **LemonWayMethod:** get use seconds parameter in GetBalances() ([822b1d3](https://github.com/qbitartifacts/rec-api/commit/822b1d36afe81f6024c99191e6f9f7370992653b))
* **parameters:** added lemonway_sync_balances_last variable to parameters-docker.yml.dist and parameters.yml.dist ([e59ab20](https://github.com/qbitartifacts/rec-api/commit/e59ab20ad08d64c8eb2fd459bdd08d06dbb1af40))
* **UpdateLemonBalanceCommand:** uncomment output ([87d94ba](https://github.com/qbitartifacts/rec-api/commit/87d94ba07ec7fdae7f76292115e2bec748c2093e))
* **UpdateLemonBalanceCommand:** update account->lw_balance ([15913cc](https://github.com/qbitartifacts/rec-api/commit/15913cc54da44c06a4739b6dcb059a71b0cb18e7))


### Bug Fixes

* **DelegatedChangeDataController:** fixed CSV row count ([49e9cbe](https://github.com/qbitartifacts/rec-api/commit/49e9cbe5f640c5e7cac0cf740bc3cbaa8ad41a9d))
* **deps:** updated composer deps ([9433a13](https://github.com/qbitartifacts/rec-api/commit/9433a13ee081d5d4ac7b1a562aefbfe98f098cb2))
* **IncomingController:** added check for rewarded_amount ([fb18b9d](https://github.com/qbitartifacts/rec-api/commit/fb18b9dbfa3423311704b414e259162cae10745f))
* **parameters-docker.yml.dist:** changed LEMONWAY_SYNC_BALANCES_LAST variable name ([999d426](https://github.com/qbitartifacts/rec-api/commit/999d4263aee1c124a5eabfcf991ee15f4fe138be))

## [2.21.0](https://github.com/qbitartifacts/rec-api/compare/v2.20.5...v2.21.0) (2021-02-17)


### Features

* **AccountController:** removed setPublicPhone ([510f40c](https://github.com/qbitartifacts/rec-api/commit/510f40ca3376580b7ac5827b29e8c2da19d52cf8))
* **CreditCardFixture:** implemented CreditCardFixture ([b895eb1](https://github.com/qbitartifacts/rec-api/commit/b895eb165cd9bec0e08294735e391b038ae617b5))
* **CreditCardFixtures:** changer external card id ([59a49a0](https://github.com/qbitartifacts/rec-api/commit/59a49a028bcaf09ecfa048726537c7ed1d273f96))
* **delegated_change:** constraints for KYC and user/accounts status ([#439](https://github.com/qbitartifacts/rec-api/issues/439)) ([8d6c97c](https://github.com/qbitartifacts/rec-api/commit/8d6c97c832b41efe124bc1eec9ad291bee7c3a6b))
* **delegated_change:** delegated changes serialized info ([#446](https://github.com/qbitartifacts/rec-api/issues/446)) ([e331a09](https://github.com/qbitartifacts/rec-api/commit/e331a09f432c1843de41207f8017ddd944baf72b))
* **DelegatedChangeData:** added creditcard_id variable ([9c90752](https://github.com/qbitartifacts/rec-api/commit/9c907523e58907331138323861306255e86669d9))
* **DelegatedChangeData:** changes variable name from creditcard_id to creditcard ([b30a53b](https://github.com/qbitartifacts/rec-api/commit/b30a53b56b35876efeb1f6cf1064e0d915dc5b16))
* **DelegatedChangeData:** seted creditcard_id nullable ([d39de73](https://github.com/qbitartifacts/rec-api/commit/d39de73acfbb346cb6a42e79e084fafe632c5c2f))
* **DelegatedChangeDataController:** added creditcard_id header ([08f11e9](https://github.com/qbitartifacts/rec-api/commit/08f11e99ef8cb255c13cb58e590ae50faa7848f8))
* **DelegatedChangeDataController:** modified variable_name to creditcard_id ([b781c50](https://github.com/qbitartifacts/rec-api/commit/b781c50a27e493d0fb08006ac5214863d459257a))
* **DelegatedChangeFixture:** implemented DelegatedChangeFixture ([eea1668](https://github.com/qbitartifacts/rec-api/commit/eea1668cc4fa7ba3878a3fbc0ec74695a6e57900))
* **DelegatedChangeFixtures:** changer amount (for decimals) ([d625a7f](https://github.com/qbitartifacts/rec-api/commit/d625a7f4749bd97faba366f97a2ee1d3f657d49f))
* **DelegatedChangeTest:** added check for account balance before and after delegated change execution ([be49d3c](https://github.com/qbitartifacts/rec-api/commit/be49d3c64ccd0fc2677e20587b32c816c5d080be))
* **DelegatedChangeTest:** disabled testDelegatedCharge ([26ab016](https://github.com/qbitartifacts/rec-api/commit/26ab0167f2db487cbeb5e6975ceeaec7108997fe))
* **DelegatedChangeTest:** test testDelegatedCharge disabled because the mock fails when run 'rec:delegated_change:run' ([c8ea704](https://github.com/qbitartifacts/rec-api/commit/c8ea704db4f1553b8f5b7d077ba94fc911859078))
* **DelegatedChargeDateController:** optional external credit card id header ([246f389](https://github.com/qbitartifacts/rec-api/commit/246f38972724b15e47eff3b184848c6d98069d8c))
* **DelegatedChargeV2Command:** added required params for createTransaction ([c095893](https://github.com/qbitartifacts/rec-api/commit/c0958939ff196f3f5a81de57def3630438966027))
* **IncomingController:** allow pay with not pertaining card ([7526d73](https://github.com/qbitartifacts/rec-api/commit/7526d73af4eab2d9f32e8689fdf42b597fba84b4))
* **map:** removed /public/map/v1/list and updated SearchAction ([#447](https://github.com/qbitartifacts/rec-api/issues/447)) ([512cb80](https://github.com/qbitartifacts/rec-api/commit/512cb8005159d9bb0511b02c34175a7bdcce1f72))
* **rec:delegated_change:run:** run rec:delegated_change:run in cron ([96e4c6e](https://github.com/qbitartifacts/rec-api/commit/96e4c6e1bba5f1dd2875dba920bca2f44791a0ef))
* **RechargeRecsTest:** Implemented testDelegatedCharge ([1989576](https://github.com/qbitartifacts/rec-api/commit/1989576356cbc2daac57bd1fd1f359d3851973c8))
* **RechargeRecsTest:** moved testDelegatedCharge ([dd082be](https://github.com/qbitartifacts/rec-api/commit/dd082bee18d903fdbea0b9df2a071f67c6f3a3c9))
* **versioning:** bumped minimum app version to v61 ([#425](https://github.com/qbitartifacts/rec-api/issues/425)) ([84ba70a](https://github.com/qbitartifacts/rec-api/commit/84ba70aa9e87314da464abd2759bd4aa4fe0376e))


### Bug Fixes

* **CreditCardFixture:** fixed dependencies ([a2e000f](https://github.com/qbitartifacts/rec-api/commit/a2e000f71164cc0de41fe1be08cc5bd8d240b615))
* **delegated_change:** errors now are more detailed, kyc fix ([#442](https://github.com/qbitartifacts/rec-api/issues/442)) ([7f26ee3](https://github.com/qbitartifacts/rec-api/commit/7f26ee3d65169f629f1f42fd9ae12b378bd300c4))
* **delegated_change:** working ViewHandler error (test still fails by other reasons) ([90c1a61](https://github.com/qbitartifacts/rec-api/commit/90c1a61d5865d92818379ff3d89cf4e9c5d12df0))
* **deps:** updated composer deps ([89f82a9](https://github.com/qbitartifacts/rec-api/commit/89f82a995408ba978d2098266399f2ca352b633f))
* **misc:** Save created and updated date on users and accounts ([#432](https://github.com/qbitartifacts/rec-api/issues/432)) ([6831e93](https://github.com/qbitartifacts/rec-api/commit/6831e93feaadc38655539bc2ede3fd7f79186242)), closes [#430](https://github.com/qbitartifacts/rec-api/issues/430) [#429](https://github.com/qbitartifacts/rec-api/issues/429) [#431](https://github.com/qbitartifacts/rec-api/issues/431)

### [2.20.5](https://github.com/qbitartifacts/rec-api/compare/v2.20.4...v2.20.5) (2020-12-02)


### Bug Fixes

* **deps:** updated composer dependencies ([88a46b1](https://github.com/qbitartifacts/rec-api/commit/88a46b1a746b2c4e78017c22bb2a40975dfa548f))
* **transactions:** transactions list ([#424](https://github.com/qbitartifacts/rec-api/issues/424)) ([7d98b04](https://github.com/qbitartifacts/rec-api/commit/7d98b04dd8b9944d75cff082263dfefd5c017e70))
* **treasure_withdrawal:** changed amount type from integer to bigint ([01d2f3f](https://github.com/qbitartifacts/rec-api/commit/01d2f3fbe90ca877029a0d1aefbe6fcf7e4298b8))

### [2.20.4](https://github.com/qbitartifacts/rec-api/compare/v2.20.3...v2.20.4) (2020-11-30)


### Bug Fixes

* **transactions:** check if rewarded account is private ([#423](https://github.com/qbitartifacts/rec-api/issues/423)) ([7ea091e](https://github.com/qbitartifacts/rec-api/commit/7ea091e7f3e8a74055ad55f4e4311f22dfccb597))
* **withdrawals:** fixed withdrawal amount scale at mail ([#422](https://github.com/qbitartifacts/rec-api/issues/422)) ([6ee17c0](https://github.com/qbitartifacts/rec-api/commit/6ee17c0b61a4cee03b9dd2db991cb6864c373eb0))

### [2.20.3](https://github.com/qbitartifacts/rec-api/compare/v2.20.2...v2.20.3) (2020-11-30)


### Bug Fixes

* **AccountsController:** changed campaign_id for id in search where.([#421](https://github.com/qbitartifacts/rec-api/issues/421)) ([8c09198](https://github.com/qbitartifacts/rec-api/commit/8c0919832f65a9d1126c33c7b5a4936ab5bcf861))

### [2.20.2](https://github.com/qbitartifacts/rec-api/compare/v2.20.1...v2.20.2) (2020-11-27)


### Bug Fixes

* **accounts:** implemented search by campaigns ([#419](https://github.com/qbitartifacts/rec-api/issues/419)) ([7e3ed07](https://github.com/qbitartifacts/rec-api/commit/7e3ed07199981ddb2fd39b984dc1816aad71df3d))
* **accounts:** improved search for accounts ([#420](https://github.com/qbitartifacts/rec-api/issues/420)) ([ac11428](https://github.com/qbitartifacts/rec-api/commit/ac114287041204f04fd43c359d3e562da099a0d9))
* **login:** check version and platform at login ([#418](https://github.com/qbitartifacts/rec-api/issues/418)) ([fa7a564](https://github.com/qbitartifacts/rec-api/commit/fa7a564c46d281453b3ab91227838777354dc33b))

### [2.20.1](https://github.com/qbitartifacts/rec-api/compare/v2.20.0...v2.20.1) (2020-11-26)


### Bug Fixes

* **ltab:** Ltab pays same user private account ([#417](https://github.com/qbitartifacts/rec-api/issues/417)) ([e1ed2cf](https://github.com/qbitartifacts/rec-api/commit/e1ed2cf88228f2153245d6d6a891ab5d6975b662))

## [2.20.0](https://github.com/qbitartifacts/rec-api/compare/v2.19.2...v2.20.0) (2020-11-26)


### Features

* **serializer:** use secureOutput on pin update ([#416](https://github.com/qbitartifacts/rec-api/issues/416)) ([f8e3c0a](https://github.com/qbitartifacts/rec-api/commit/f8e3c0a4dcfc143133cb2b9afd0fba2f791f7708))

### [2.19.2](https://github.com/qbitartifacts/rec-api/compare/v2.19.1...v2.19.2) (2020-11-25)


### Bug Fixes

* **config:** moved kyc email to parameters instead of hardcoded ([#413](https://github.com/qbitartifacts/rec-api/issues/413)) ([45900e6](https://github.com/qbitartifacts/rec-api/commit/45900e6b45bb66f343efd0bcd121c00f686be134))
* **deps:** updated composer deps ([d310099](https://github.com/qbitartifacts/rec-api/commit/d31009965b9e52ab7f92eb2c9cb932278654a9d1))
* **kyc:** changed kyc destination email ([#411](https://github.com/qbitartifacts/rec-api/issues/411)) ([4809b74](https://github.com/qbitartifacts/rec-api/commit/4809b74838c88c52c7531cb05ff62788e2245830))
* **ltab:** allow payments from ltab to private accounts ([#412](https://github.com/qbitartifacts/rec-api/issues/412)) ([ad6c96a](https://github.com/qbitartifacts/rec-api/commit/ad6c96a624a1096878b269ac5459335e39569020))

### [2.19.1](https://github.com/qbitartifacts/rec-api/compare/v2.17.0...v2.19.1) (2020-11-24)


### Features

* **2fa:** check platform ([#400](https://github.com/qbitartifacts/rec-api/issues/400)) ([1099334](https://github.com/qbitartifacts/rec-api/commit/10993341fc5a6aa9c6d4f5a537efe31818d5b4ef))
* **accounts:** sort public accounts user accounts first ([#409](https://github.com/qbitartifacts/rec-api/issues/409)) ([328f493](https://github.com/qbitartifacts/rec-api/commit/328f49389cf7e881bcb0f1be90dbd7b03c511205))
* **bonissim:** created rules in payments to bonissim campaign ([#373](https://github.com/qbitartifacts/rec-api/issues/373)) ([8abc171](https://github.com/qbitartifacts/rec-api/commit/8abc17101acb76a3f99ef79094b8ff6c292e3ccf))
* **campaign:** added campaign image urls ([#395](https://github.com/qbitartifacts/rec-api/issues/395)) ([90abad8](https://github.com/qbitartifacts/rec-api/commit/90abad85727298dcebb6a336d1d5550da2319c0a))
* **campaign:** added min and max recharge amount constraints ([#370](https://github.com/qbitartifacts/rec-api/issues/370)) ([66f5ea7](https://github.com/qbitartifacts/rec-api/commit/66f5ea7f81458f2f5f2cd45192ff029f129d3b31))
* **campaign:** count redeemable and rewarded amounts ([#378](https://github.com/qbitartifacts/rec-api/issues/378)) ([c25dc84](https://github.com/qbitartifacts/rec-api/commit/c25dc8467cded0e1d0ce5e2559f28d3754207c86)), closes [#345](https://github.com/qbitartifacts/rec-api/issues/345)
* **campaign:** create bonissim account ([#367](https://github.com/qbitartifacts/rec-api/issues/367)) ([4b94b26](https://github.com/qbitartifacts/rec-api/commit/4b94b2614897dfacb3d18f2409e6c295fdc9a9c7))
* **campaign:** send 15% reward to payer account ([#382](https://github.com/qbitartifacts/rec-api/issues/382)) ([a4b91e1](https://github.com/qbitartifacts/rec-api/commit/a4b91e11d252cd5b07c8d16e1f367e412831995c))
* **campaign:** set recharged recs at bonissim account creation ([#377](https://github.com/qbitartifacts/rec-api/issues/377)) ([346df9e](https://github.com/qbitartifacts/rec-api/commit/346df9ed01e2537fef384b86672a15414856647b)), closes [#345](https://github.com/qbitartifacts/rec-api/issues/345)
* **campaigns:** add and delete campaign relationship ([#363](https://github.com/qbitartifacts/rec-api/issues/363)) ([ebded4f](https://github.com/qbitartifacts/rec-api/commit/ebded4fd5d594b0cf75b27accd8a931eeadf4ca3))
* **campaigns:** create only on bonissim account ([#372](https://github.com/qbitartifacts/rec-api/issues/372)) ([57bc792](https://github.com/qbitartifacts/rec-api/commit/57bc79287c572acfca62b6e7319fe46b59586b9e))
* **cron:** added missed cron ([5970443](https://github.com/qbitartifacts/rec-api/commit/5970443170f4f2e3010f93118afc6316abcd50f6))
* **documentation:** added POS urls to documentation ,fixes QbitArtifacts/rec-pos[#1](https://github.com/qbitartifacts/rec-api/issues/1) ([03d6155](https://github.com/qbitartifacts/rec-api/commit/03d6155c1362649ecbb79701f5f53983dda9d577))
* **kyc:** check kyc limits ([#394](https://github.com/qbitartifacts/rec-api/issues/394)) ([5869089](https://github.com/qbitartifacts/rec-api/commit/586908911cd0b761544a2c2e565a93661a15919d))
* **kyc:** send kyc mail to admins ([#405](https://github.com/qbitartifacts/rec-api/issues/405)) ([8ef54a8](https://github.com/qbitartifacts/rec-api/commit/8ef54a8bd1c3a608ab563801016a5638a8c3c36c))
* **migrations:** added migration for save the refunded tx in the paymentorder ([1ff0699](https://github.com/qbitartifacts/rec-api/commit/1ff069948150e223e60c0077e0e01690d1c43f3b))
* **pos:** added ip address and payment address to payment orders ([60327e6](https://github.com/qbitartifacts/rec-api/commit/60327e64b878e385360caba2b0a2846929963dd5))
* **pos:** added migration to save transaction_id into PaymentOrder ([766dd3e](https://github.com/qbitartifacts/rec-api/commit/766dd3eef3d79a5029de376bdab9777f4fc02fcb))
* **pos:** added missed configurations to POS module ([263f797](https://github.com/qbitartifacts/rec-api/commit/263f797ccff1a990f805e91b5108db290db3fd6e))
* **pos:** added payment url to payment orders ([66e8a42](https://github.com/qbitartifacts/rec-api/commit/66e8a4229ed1a709271c7629f920b6103f23a176))
* **pos:** added property pos_type to PaymentOrder ([#314](https://github.com/qbitartifacts/rec-api/issues/314)) ([7eadfcf](https://github.com/qbitartifacts/rec-api/commit/7eadfcf4c5381b80cba57eea4edd7b92f66d2e36))
* **pos:** fail POS transactions after 3 pin retries ([#352](https://github.com/qbitartifacts/rec-api/issues/352)) ([fb17aa0](https://github.com/qbitartifacts/rec-api/commit/fb17aa0ec5c4d131aa721009e34dda1b123e06e2))
* **pos:** implemented migration for the POS and Payment Orders ([8361a53](https://github.com/qbitartifacts/rec-api/commit/8361a5322782aec2f2639aee42f73cc8f58e355f))
* **pos:** implemented pos expire pos expire command ([8129548](https://github.com/qbitartifacts/rec-api/commit/812954843488a1a84fb73c18e98e8ff16d37898f)), closes [#294](https://github.com/qbitartifacts/rec-api/issues/294)
* **pos:** implemented pos notifications entities ([dc8b187](https://github.com/qbitartifacts/rec-api/commit/dc8b1875cf31fd90319660e68d7c4428cb1353c9)), closes [#280](https://github.com/qbitartifacts/rec-api/issues/280)
* **pos:** implemented public endpoint for payment_orders ([6bb74ef](https://github.com/qbitartifacts/rec-api/commit/6bb74ef6f76c5108c6f6591773c40e4fc6fe8f4e))
* **pos:** implemented receive payment order, closes [#276](https://github.com/qbitartifacts/rec-api/issues/276) ([1a2b8d7](https://github.com/qbitartifacts/rec-api/commit/1a2b8d72ff140ab3ad38b75f2eac0ce786dd16f6))
* **pos:** implemented refund payment orders ([8add24c](https://github.com/qbitartifacts/rec-api/commit/8add24c70e0ed5e808ed7370a4e32184b87ff7fb)), closes [#279](https://github.com/qbitartifacts/rec-api/issues/279)
* **security:** changed int ids to guid in payments to avoid bruteforce listing ([c0c127a](https://github.com/qbitartifacts/rec-api/commit/c0c127ae21d3a784ac5d8c4d06a4d222f882d917))
* **treasure_withdrawal:** changed email timezone and text ([#337](https://github.com/qbitartifacts/rec-api/issues/337)) ([b533290](https://github.com/qbitartifacts/rec-api/commit/b53329024265759b2b12c1f3a5c1113a53423dd9))
* added Order entity ([2744d12](https://github.com/qbitartifacts/rec-api/commit/2744d12c3bb75c700de99a71f390720b8b91ee44))
* added POS entity ([57500df](https://github.com/qbitartifacts/rec-api/commit/57500df926860dc70ef6529787ebcece93758241))
* added pos relation to Group ([f39e660](https://github.com/qbitartifacts/rec-api/commit/f39e660171f5dc90dcc885871dfd4443fdd5733c))


### Bug Fixes

* **campaign:** changed inversedBy to mappedBy to correct db conflicts ([eb43a8e](https://github.com/qbitartifacts/rec-api/commit/eb43a8e447f037ee6729b778a396b2986989bb69))
* **campaign:** check fake lw & variables type changed ([#385](https://github.com/qbitartifacts/rec-api/issues/385)) ([3aa892f](https://github.com/qbitartifacts/rec-api/commit/3aa892fe1133e15979b732a45ebe5cee10f31a36))
* **campaign:** check tos before create account ([#374](https://github.com/qbitartifacts/rec-api/issues/374)) ([4e98dd7](https://github.com/qbitartifacts/rec-api/commit/4e98dd72b15049c3fafa088dd7b0abe3e86a2247))
* **campaign:** ensure redeemable <= user balance after payment ([#383](https://github.com/qbitartifacts/rec-api/issues/383)) ([288d465](https://github.com/qbitartifacts/rec-api/commit/288d46506afaf35198e2f05a308ab9cd761eb872))
* **ci:** Updated stale action and added new property ([#317](https://github.com/qbitartifacts/rec-api/issues/317)) ([c6df0f5](https://github.com/qbitartifacts/rec-api/commit/c6df0f58bc32fc4ac26c6b72087c5551c440c21a))
* **credit_card:** fixes 30-seg timeout in credit card delete ([#316](https://github.com/qbitartifacts/rec-api/issues/316)) ([a795953](https://github.com/qbitartifacts/rec-api/commit/a795953cf1aa916b9cad3d39157f060c122b78b8))
* **cron_retries:** fixed bug in cron for retries ([3234dce](https://github.com/qbitartifacts/rec-api/commit/3234dce749bdc5284f5d44af8f62341d8e8f600f))
* **dependencies:** updated dependencies ([c9ae684](https://github.com/qbitartifacts/rec-api/commit/c9ae6845319eb167d8b966f8605f755cccda24a2))
* **dependencies:** updated dependencies ([18ac42c](https://github.com/qbitartifacts/rec-api/commit/18ac42c54a51a519a4450eaa8e8663664ed298dd))
* **deps:** updated composer dependencies ([82565fe](https://github.com/qbitartifacts/rec-api/commit/82565fe800f4887d49022107472c4b72dddd1989))
* **deps:** updated composer dependencies ([#330](https://github.com/qbitartifacts/rec-api/issues/330)) ([76ccb55](https://github.com/qbitartifacts/rec-api/commit/76ccb55d4d1787dd216728cd0576c184d7642bd2))
* **dev:** fixed installation script for mongo (was breaking all tests) ([#402](https://github.com/qbitartifacts/rec-api/issues/402)) ([a9f397b](https://github.com/qbitartifacts/rec-api/commit/a9f397b7e594d53315738d2c4254514ad757a204))
* **docs:** fix apidoc url ([#320](https://github.com/qbitartifacts/rec-api/issues/320)) ([3f702c4](https://github.com/qbitartifacts/rec-api/commit/3f702c4b772cfaf08d33bb100e2ec3bfeca3e681))
* **kyc:** KYC refactor ([#398](https://github.com/qbitartifacts/rec-api/issues/398)) ([f1610f4](https://github.com/qbitartifacts/rec-api/commit/f1610f46df371e740c0d346ed72b3c0d77e1c952))
* **lemonway:** added check for existing lw_balance before querying to lw ([d429faa](https://github.com/qbitartifacts/rec-api/commit/d429faab7aa8d3bd813fa0af9ceb47a94291f471)), closes [#305](https://github.com/qbitartifacts/rec-api/issues/305)
* **migrations:** fixed migrations error ([b68cf1a](https://github.com/qbitartifacts/rec-api/commit/b68cf1aadf4db45eaa8331347cec8aabc8360a38))
* **migrations:** fixed migrations error (by second time) ([28db095](https://github.com/qbitartifacts/rec-api/commit/28db0957a0285f6016fc4f751fc1feaecad989aa))
* **notifications:** added check to not notify when notification url is not present ([846e926](https://github.com/qbitartifacts/rec-api/commit/846e926b5100aee777e1f5c203b62d131baf705c)), closes [#300](https://github.com/qbitartifacts/rec-api/issues/300)
* **notifications:** added expire check to notifications after 24h ([ebebf7d](https://github.com/qbitartifacts/rec-api/commit/ebebf7dc1cfa98e34dadb13cea6c3c09a3487c47))
* **notifications:** fixed bug in http notifier ([2e11eaa](https://github.com/qbitartifacts/rec-api/commit/2e11eaa8c8d96676dac3dbf4a178c7d8e69d321f))
* **notifications:** fixed notification order ([b574f25](https://github.com/qbitartifacts/rec-api/commit/b574f25bc208a55d423284459e87612c8035936c)), closes [#281](https://github.com/qbitartifacts/rec-api/issues/281)
* **notifications:** fixes notifications issues ([#299](https://github.com/qbitartifacts/rec-api/issues/299)) ([a315de1](https://github.com/qbitartifacts/rec-api/commit/a315de132a8e79415abf4936078f782d2f3d9684))
* **payment_type:** ([#321](https://github.com/qbitartifacts/rec-api/issues/321)) ([b64438e](https://github.com/qbitartifacts/rec-api/commit/b64438e21332fe8f59156f464bfbacff0671d7f7))
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
* **pos:** payment order custom url ([#322](https://github.com/qbitartifacts/rec-api/issues/322)) ([00f46cc](https://github.com/qbitartifacts/rec-api/commit/00f46cc89c57d433dde0402b3789fce86c64e75f))
* **pos:** restricted only to admins index pos transactions ([a29850d](https://github.com/qbitartifacts/rec-api/commit/a29850d4da9b4173a1788a74745ba890d5f47667))
* **pos:** set notification_url not required ([02b8d95](https://github.com/qbitartifacts/rec-api/commit/02b8d950211193f44b4c2aa9fbb976b70bbab5c0))
* **pos:** set pos active by default at creation ([79fb1f7](https://github.com/qbitartifacts/rec-api/commit/79fb1f74057f9b2a257b63bc2c8738423821c019))
* **public_phone_list:** public phones and dependency updates ([#310](https://github.com/qbitartifacts/rec-api/issues/310)) ([0f54268](https://github.com/qbitartifacts/rec-api/commit/0f542686d02b2d1d3a58e4868ba9e4e4db220962))
* **refactor:** client management in tests ([#384](https://github.com/qbitartifacts/rec-api/issues/384)) ([9aa6675](https://github.com/qbitartifacts/rec-api/commit/9aa667576c7b785c6d785e7b78576d99e32764e0))
* **refactor:** refactored tx tests ([5e240a5](https://github.com/qbitartifacts/rec-api/commit/5e240a5e6adc4e467fd4869c33709753b3ffcf54))
* **refactor:** removed unused code ([727a8e1](https://github.com/qbitartifacts/rec-api/commit/727a8e1d8155e4972c0afdc705b8d2fd17fad5c5))
* **refund:** changed pin to otp code ([44cf2fe](https://github.com/qbitartifacts/rec-api/commit/44cf2fe64dd6272334255fd66c00558126456a68)), closes [#297](https://github.com/qbitartifacts/rec-api/issues/297)
* **rest:** set created and updated public for all entities ([fc7c577](https://github.com/qbitartifacts/rec-api/commit/fc7c5770986fbf3b2fc78a03e2786a4875cf83f2))
* **rest:** set created and updated public for all entities, fixes [#277](https://github.com/qbitartifacts/rec-api/issues/277) ([fa431a4](https://github.com/qbitartifacts/rec-api/commit/fa431a4da5968a479ae12a4e0aa892898dce4e87))
* **rewards:** fixed redeemable failures on delegate rewarded payments ([#388](https://github.com/qbitartifacts/rec-api/issues/388)) ([ebe781b](https://github.com/qbitartifacts/rec-api/commit/ebe781bd1e99513fda3208b6bee6c0148de8536b))
* **routing:** fix order id pattern ([9203c34](https://github.com/qbitartifacts/rec-api/commit/9203c34327ef37f706938d713e4e290371bd6e6b))
* **serializer:** changed max depth on acteve_group ([#380](https://github.com/qbitartifacts/rec-api/issues/380)) ([5a2309b](https://github.com/qbitartifacts/rec-api/commit/5a2309b72ef205503778f2490c5b17f4b7d3a52e))
* **serializer:** incremented max depth for user and account ([#379](https://github.com/qbitartifacts/rec-api/issues/379)) ([ad3eaa0](https://github.com/qbitartifacts/rec-api/commit/ad3eaa04d97a2ae50f7ae84200304fae3831f44e))
* **tests:** removed old test for create payment orders ([0626dab](https://github.com/qbitartifacts/rec-api/commit/0626dabb8adb303313592971aa849c7c0a51b41e))
* **tests:** removed unused test ([e898e1a](https://github.com/qbitartifacts/rec-api/commit/e898e1a414f0a46f112ceecaaf24a8814eb87f61))
* **transactions:** fixed bug with checking limits in creating transactions ([46d27b0](https://github.com/qbitartifacts/rec-api/commit/46d27b0802ad7efc9a5acc2848481f5ab575798b))
* **transactions:** fixes concurrency problem with transactions ([#312](https://github.com/qbitartifacts/rec-api/issues/312)) ([091c008](https://github.com/qbitartifacts/rec-api/commit/091c008defcbe41709e9f60d6a754d0e4451c519))
* **treasure:** fixes treasure withdrawals ([#328](https://github.com/qbitartifacts/rec-api/issues/328)) ([292df26](https://github.com/qbitartifacts/rec-api/commit/292df264075f6a639d7db87c6e78d713ba2db0f5))
* **treasure_withdrawal:** added to to mail ([b8618f4](https://github.com/qbitartifacts/rec-api/commit/b8618f4a84bee6f0b8b18001db5990cd40ab07c2))
* **treasure_withdrawal:** bug fixes and performance issues ([#329](https://github.com/qbitartifacts/rec-api/issues/329)) ([cacd4be](https://github.com/qbitartifacts/rec-api/commit/cacd4be38df5c61d82cce1f61f745464026af29b))
* **treasure_withdrawal:** fixes [#327](https://github.com/qbitartifacts/rec-api/issues/327) ([4eccc73](https://github.com/qbitartifacts/rec-api/commit/4eccc735dea522876d13981c739e6adc742c7568))
* **treasure_withdrawal:** improved tests ([#331](https://github.com/qbitartifacts/rec-api/issues/331)) ([8785be8](https://github.com/qbitartifacts/rec-api/commit/8785be84a7da44959b395b830d21a25f15211344))
* **treasure_withdrawal:** updated email template ([#334](https://github.com/qbitartifacts/rec-api/issues/334)) ([ff02cb4](https://github.com/qbitartifacts/rec-api/commit/ff02cb44e2da3a5d2c8f324ffa35cafb0c3cdafd))
* **version:** login2fa version ([#399](https://github.com/qbitartifacts/rec-api/issues/399)) ([c60872b](https://github.com/qbitartifacts/rec-api/commit/c60872b1f2a7c018693406d172f5268e9a0e613d))
* **version:** returned exact git abbreviated version instead of only the last tag ([71f32a9](https://github.com/qbitartifacts/rec-api/commit/71f32a99548777bf801942d0e85405ee0ac9152e))
* added status consts to Order entity ([9e93a13](https://github.com/qbitartifacts/rec-api/commit/9e93a13dd9464202381c972a933580b409f06b2a))
* added url_ok and url_ko to order ([98586a0](https://github.com/qbitartifacts/rec-api/commit/98586a0143a4a978d88a764d38d275b6473dd9ad))

## [2.19.0](https://github.com/qbitartifacts/rec-api/compare/v2.18.0...v2.19.0) (2020-11-23)


### Features

* **2fa:** check platform ([#400](https://github.com/qbitartifacts/rec-api/issues/400)) ([1099334](https://github.com/qbitartifacts/rec-api/commit/10993341fc5a6aa9c6d4f5a537efe31818d5b4ef))
* **bonissim:** created rules in payments to bonissim campaign ([#373](https://github.com/qbitartifacts/rec-api/issues/373)) ([8abc171](https://github.com/qbitartifacts/rec-api/commit/8abc17101acb76a3f99ef79094b8ff6c292e3ccf))
* **campaign:** added campaign image urls ([#395](https://github.com/qbitartifacts/rec-api/issues/395)) ([90abad8](https://github.com/qbitartifacts/rec-api/commit/90abad85727298dcebb6a336d1d5550da2319c0a))
* **campaign:** added min and max recharge amount constraints ([#370](https://github.com/qbitartifacts/rec-api/issues/370)) ([66f5ea7](https://github.com/qbitartifacts/rec-api/commit/66f5ea7f81458f2f5f2cd45192ff029f129d3b31))
* **campaign:** count redeemable and rewarded amounts ([#378](https://github.com/qbitartifacts/rec-api/issues/378)) ([c25dc84](https://github.com/qbitartifacts/rec-api/commit/c25dc8467cded0e1d0ce5e2559f28d3754207c86)), closes [#345](https://github.com/qbitartifacts/rec-api/issues/345)
* **campaign:** create bonissim account ([#367](https://github.com/qbitartifacts/rec-api/issues/367)) ([4b94b26](https://github.com/qbitartifacts/rec-api/commit/4b94b2614897dfacb3d18f2409e6c295fdc9a9c7))
* **campaign:** send 15% reward to payer account ([#382](https://github.com/qbitartifacts/rec-api/issues/382)) ([a4b91e1](https://github.com/qbitartifacts/rec-api/commit/a4b91e11d252cd5b07c8d16e1f367e412831995c))
* **campaign:** set recharged recs at bonissim account creation ([#377](https://github.com/qbitartifacts/rec-api/issues/377)) ([346df9e](https://github.com/qbitartifacts/rec-api/commit/346df9ed01e2537fef384b86672a15414856647b)), closes [#345](https://github.com/qbitartifacts/rec-api/issues/345)
* **campaigns:** add and delete campaign relationship ([#363](https://github.com/qbitartifacts/rec-api/issues/363)) ([ebded4f](https://github.com/qbitartifacts/rec-api/commit/ebded4fd5d594b0cf75b27accd8a931eeadf4ca3))
* **campaigns:** create only on bonissim account ([#372](https://github.com/qbitartifacts/rec-api/issues/372)) ([57bc792](https://github.com/qbitartifacts/rec-api/commit/57bc79287c572acfca62b6e7319fe46b59586b9e))
* **cron:** added missed cron ([5970443](https://github.com/qbitartifacts/rec-api/commit/5970443170f4f2e3010f93118afc6316abcd50f6))
* **documentation:** added POS urls to documentation ,fixes QbitArtifacts/rec-pos[#1](https://github.com/qbitartifacts/rec-api/issues/1) ([03d6155](https://github.com/qbitartifacts/rec-api/commit/03d6155c1362649ecbb79701f5f53983dda9d577))
* **kyc:** check kyc limits ([#394](https://github.com/qbitartifacts/rec-api/issues/394)) ([5869089](https://github.com/qbitartifacts/rec-api/commit/586908911cd0b761544a2c2e565a93661a15919d))
* **kyc:** send kyc mail to admins ([#405](https://github.com/qbitartifacts/rec-api/issues/405)) ([8ef54a8](https://github.com/qbitartifacts/rec-api/commit/8ef54a8bd1c3a608ab563801016a5638a8c3c36c))
* **migrations:** added migration for save the refunded tx in the paymentorder ([1ff0699](https://github.com/qbitartifacts/rec-api/commit/1ff069948150e223e60c0077e0e01690d1c43f3b))
* **pos:** added ip address and payment address to payment orders ([60327e6](https://github.com/qbitartifacts/rec-api/commit/60327e64b878e385360caba2b0a2846929963dd5))
* **pos:** added migration to save transaction_id into PaymentOrder ([766dd3e](https://github.com/qbitartifacts/rec-api/commit/766dd3eef3d79a5029de376bdab9777f4fc02fcb))
* **pos:** added missed configurations to POS module ([263f797](https://github.com/qbitartifacts/rec-api/commit/263f797ccff1a990f805e91b5108db290db3fd6e))
* **pos:** added payment url to payment orders ([66e8a42](https://github.com/qbitartifacts/rec-api/commit/66e8a4229ed1a709271c7629f920b6103f23a176))
* **pos:** added property pos_type to PaymentOrder ([#314](https://github.com/qbitartifacts/rec-api/issues/314)) ([7eadfcf](https://github.com/qbitartifacts/rec-api/commit/7eadfcf4c5381b80cba57eea4edd7b92f66d2e36))
* **pos:** fail POS transactions after 3 pin retries ([#352](https://github.com/qbitartifacts/rec-api/issues/352)) ([fb17aa0](https://github.com/qbitartifacts/rec-api/commit/fb17aa0ec5c4d131aa721009e34dda1b123e06e2))
* **pos:** implemented migration for the POS and Payment Orders ([8361a53](https://github.com/qbitartifacts/rec-api/commit/8361a5322782aec2f2639aee42f73cc8f58e355f))
* **pos:** implemented pos expire pos expire command ([8129548](https://github.com/qbitartifacts/rec-api/commit/812954843488a1a84fb73c18e98e8ff16d37898f)), closes [#294](https://github.com/qbitartifacts/rec-api/issues/294)
* **pos:** implemented pos notifications entities ([dc8b187](https://github.com/qbitartifacts/rec-api/commit/dc8b1875cf31fd90319660e68d7c4428cb1353c9)), closes [#280](https://github.com/qbitartifacts/rec-api/issues/280)
* **pos:** implemented public endpoint for payment_orders ([6bb74ef](https://github.com/qbitartifacts/rec-api/commit/6bb74ef6f76c5108c6f6591773c40e4fc6fe8f4e))
* **pos:** implemented receive payment order, closes [#276](https://github.com/qbitartifacts/rec-api/issues/276) ([1a2b8d7](https://github.com/qbitartifacts/rec-api/commit/1a2b8d72ff140ab3ad38b75f2eac0ce786dd16f6))
* **pos:** implemented refund payment orders ([8add24c](https://github.com/qbitartifacts/rec-api/commit/8add24c70e0ed5e808ed7370a4e32184b87ff7fb)), closes [#279](https://github.com/qbitartifacts/rec-api/issues/279)
* **security:** changed int ids to guid in payments to avoid bruteforce listing ([c0c127a](https://github.com/qbitartifacts/rec-api/commit/c0c127ae21d3a784ac5d8c4d06a4d222f882d917))
* **treasure_withdrawal:** changed email timezone and text ([#337](https://github.com/qbitartifacts/rec-api/issues/337)) ([b533290](https://github.com/qbitartifacts/rec-api/commit/b53329024265759b2b12c1f3a5c1113a53423dd9))
* added Order entity ([2744d12](https://github.com/qbitartifacts/rec-api/commit/2744d12c3bb75c700de99a71f390720b8b91ee44))
* added POS entity ([57500df](https://github.com/qbitartifacts/rec-api/commit/57500df926860dc70ef6529787ebcece93758241))
* added pos relation to Group ([f39e660](https://github.com/qbitartifacts/rec-api/commit/f39e660171f5dc90dcc885871dfd4443fdd5733c))


### Bug Fixes

* **campaign:** changed inversedBy to mappedBy to correct db conflicts ([eb43a8e](https://github.com/qbitartifacts/rec-api/commit/eb43a8e447f037ee6729b778a396b2986989bb69))
* **campaign:** check fake lw & variables type changed ([#385](https://github.com/qbitartifacts/rec-api/issues/385)) ([3aa892f](https://github.com/qbitartifacts/rec-api/commit/3aa892fe1133e15979b732a45ebe5cee10f31a36))
* **campaign:** check tos before create account ([#374](https://github.com/qbitartifacts/rec-api/issues/374)) ([4e98dd7](https://github.com/qbitartifacts/rec-api/commit/4e98dd72b15049c3fafa088dd7b0abe3e86a2247))
* **campaign:** ensure redeemable <= user balance after payment ([#383](https://github.com/qbitartifacts/rec-api/issues/383)) ([288d465](https://github.com/qbitartifacts/rec-api/commit/288d46506afaf35198e2f05a308ab9cd761eb872))
* **ci:** Updated stale action and added new property ([#317](https://github.com/qbitartifacts/rec-api/issues/317)) ([c6df0f5](https://github.com/qbitartifacts/rec-api/commit/c6df0f58bc32fc4ac26c6b72087c5551c440c21a))
* **credit_card:** fixes 30-seg timeout in credit card delete ([#316](https://github.com/qbitartifacts/rec-api/issues/316)) ([a795953](https://github.com/qbitartifacts/rec-api/commit/a795953cf1aa916b9cad3d39157f060c122b78b8))
* **cron_retries:** fixed bug in cron for retries ([3234dce](https://github.com/qbitartifacts/rec-api/commit/3234dce749bdc5284f5d44af8f62341d8e8f600f))
* **dependencies:** updated dependencies ([c9ae684](https://github.com/qbitartifacts/rec-api/commit/c9ae6845319eb167d8b966f8605f755cccda24a2))
* **dependencies:** updated dependencies ([18ac42c](https://github.com/qbitartifacts/rec-api/commit/18ac42c54a51a519a4450eaa8e8663664ed298dd))
* **deps:** updated composer dependencies ([82565fe](https://github.com/qbitartifacts/rec-api/commit/82565fe800f4887d49022107472c4b72dddd1989))
* **deps:** updated composer dependencies ([#330](https://github.com/qbitartifacts/rec-api/issues/330)) ([76ccb55](https://github.com/qbitartifacts/rec-api/commit/76ccb55d4d1787dd216728cd0576c184d7642bd2))
* **dev:** fixed installation script for mongo (was breaking all tests) ([#402](https://github.com/qbitartifacts/rec-api/issues/402)) ([a9f397b](https://github.com/qbitartifacts/rec-api/commit/a9f397b7e594d53315738d2c4254514ad757a204))
* **docs:** fix apidoc url ([#320](https://github.com/qbitartifacts/rec-api/issues/320)) ([3f702c4](https://github.com/qbitartifacts/rec-api/commit/3f702c4b772cfaf08d33bb100e2ec3bfeca3e681))
* **kyc:** KYC refactor ([#398](https://github.com/qbitartifacts/rec-api/issues/398)) ([f1610f4](https://github.com/qbitartifacts/rec-api/commit/f1610f46df371e740c0d346ed72b3c0d77e1c952))
* **lemonway:** added check for existing lw_balance before querying to lw ([d429faa](https://github.com/qbitartifacts/rec-api/commit/d429faab7aa8d3bd813fa0af9ceb47a94291f471)), closes [#305](https://github.com/qbitartifacts/rec-api/issues/305)
* **migrations:** fixed migrations error ([b68cf1a](https://github.com/qbitartifacts/rec-api/commit/b68cf1aadf4db45eaa8331347cec8aabc8360a38))
* **migrations:** fixed migrations error (by second time) ([28db095](https://github.com/qbitartifacts/rec-api/commit/28db0957a0285f6016fc4f751fc1feaecad989aa))
* **notifications:** added check to not notify when notification url is not present ([846e926](https://github.com/qbitartifacts/rec-api/commit/846e926b5100aee777e1f5c203b62d131baf705c)), closes [#300](https://github.com/qbitartifacts/rec-api/issues/300)
* **notifications:** added expire check to notifications after 24h ([ebebf7d](https://github.com/qbitartifacts/rec-api/commit/ebebf7dc1cfa98e34dadb13cea6c3c09a3487c47))
* **notifications:** fixed bug in http notifier ([2e11eaa](https://github.com/qbitartifacts/rec-api/commit/2e11eaa8c8d96676dac3dbf4a178c7d8e69d321f))
* **notifications:** fixed notification order ([b574f25](https://github.com/qbitartifacts/rec-api/commit/b574f25bc208a55d423284459e87612c8035936c)), closes [#281](https://github.com/qbitartifacts/rec-api/issues/281)
* **notifications:** fixes notifications issues ([#299](https://github.com/qbitartifacts/rec-api/issues/299)) ([a315de1](https://github.com/qbitartifacts/rec-api/commit/a315de132a8e79415abf4936078f782d2f3d9684))
* **payment_type:** ([#321](https://github.com/qbitartifacts/rec-api/issues/321)) ([b64438e](https://github.com/qbitartifacts/rec-api/commit/b64438e21332fe8f59156f464bfbacff0671d7f7))
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
* **pos:** payment order custom url ([#322](https://github.com/qbitartifacts/rec-api/issues/322)) ([00f46cc](https://github.com/qbitartifacts/rec-api/commit/00f46cc89c57d433dde0402b3789fce86c64e75f))
* **pos:** restricted only to admins index pos transactions ([a29850d](https://github.com/qbitartifacts/rec-api/commit/a29850d4da9b4173a1788a74745ba890d5f47667))
* **pos:** set notification_url not required ([02b8d95](https://github.com/qbitartifacts/rec-api/commit/02b8d950211193f44b4c2aa9fbb976b70bbab5c0))
* **pos:** set pos active by default at creation ([79fb1f7](https://github.com/qbitartifacts/rec-api/commit/79fb1f74057f9b2a257b63bc2c8738423821c019))
* **public_phone_list:** public phones and dependency updates ([#310](https://github.com/qbitartifacts/rec-api/issues/310)) ([0f54268](https://github.com/qbitartifacts/rec-api/commit/0f542686d02b2d1d3a58e4868ba9e4e4db220962))
* **refactor:** client management in tests ([#384](https://github.com/qbitartifacts/rec-api/issues/384)) ([9aa6675](https://github.com/qbitartifacts/rec-api/commit/9aa667576c7b785c6d785e7b78576d99e32764e0))
* **refactor:** refactored tx tests ([5e240a5](https://github.com/qbitartifacts/rec-api/commit/5e240a5e6adc4e467fd4869c33709753b3ffcf54))
* **refactor:** removed unused code ([727a8e1](https://github.com/qbitartifacts/rec-api/commit/727a8e1d8155e4972c0afdc705b8d2fd17fad5c5))
* **refund:** changed pin to otp code ([44cf2fe](https://github.com/qbitartifacts/rec-api/commit/44cf2fe64dd6272334255fd66c00558126456a68)), closes [#297](https://github.com/qbitartifacts/rec-api/issues/297)
* **rest:** set created and updated public for all entities ([fc7c577](https://github.com/qbitartifacts/rec-api/commit/fc7c5770986fbf3b2fc78a03e2786a4875cf83f2))
* **rest:** set created and updated public for all entities, fixes [#277](https://github.com/qbitartifacts/rec-api/issues/277) ([fa431a4](https://github.com/qbitartifacts/rec-api/commit/fa431a4da5968a479ae12a4e0aa892898dce4e87))
* **rewards:** fixed redeemable failures on delegate rewarded payments ([#388](https://github.com/qbitartifacts/rec-api/issues/388)) ([ebe781b](https://github.com/qbitartifacts/rec-api/commit/ebe781bd1e99513fda3208b6bee6c0148de8536b))
* **routing:** fix order id pattern ([9203c34](https://github.com/qbitartifacts/rec-api/commit/9203c34327ef37f706938d713e4e290371bd6e6b))
* **serializer:** changed max depth on acteve_group ([#380](https://github.com/qbitartifacts/rec-api/issues/380)) ([5a2309b](https://github.com/qbitartifacts/rec-api/commit/5a2309b72ef205503778f2490c5b17f4b7d3a52e))
* **serializer:** incremented max depth for user and account ([#379](https://github.com/qbitartifacts/rec-api/issues/379)) ([ad3eaa0](https://github.com/qbitartifacts/rec-api/commit/ad3eaa04d97a2ae50f7ae84200304fae3831f44e))
* **tests:** removed old test for create payment orders ([0626dab](https://github.com/qbitartifacts/rec-api/commit/0626dabb8adb303313592971aa849c7c0a51b41e))
* **tests:** removed unused test ([e898e1a](https://github.com/qbitartifacts/rec-api/commit/e898e1a414f0a46f112ceecaaf24a8814eb87f61))
* **transactions:** fixed bug with checking limits in creating transactions ([46d27b0](https://github.com/qbitartifacts/rec-api/commit/46d27b0802ad7efc9a5acc2848481f5ab575798b))
* **transactions:** fixes concurrency problem with transactions ([#312](https://github.com/qbitartifacts/rec-api/issues/312)) ([091c008](https://github.com/qbitartifacts/rec-api/commit/091c008defcbe41709e9f60d6a754d0e4451c519))
* **treasure:** fixes treasure withdrawals ([#328](https://github.com/qbitartifacts/rec-api/issues/328)) ([292df26](https://github.com/qbitartifacts/rec-api/commit/292df264075f6a639d7db87c6e78d713ba2db0f5))
* **treasure_withdrawal:** added to to mail ([b8618f4](https://github.com/qbitartifacts/rec-api/commit/b8618f4a84bee6f0b8b18001db5990cd40ab07c2))
* **treasure_withdrawal:** bug fixes and performance issues ([#329](https://github.com/qbitartifacts/rec-api/issues/329)) ([cacd4be](https://github.com/qbitartifacts/rec-api/commit/cacd4be38df5c61d82cce1f61f745464026af29b))
* **treasure_withdrawal:** fixes [#327](https://github.com/qbitartifacts/rec-api/issues/327) ([4eccc73](https://github.com/qbitartifacts/rec-api/commit/4eccc735dea522876d13981c739e6adc742c7568))
* **treasure_withdrawal:** improved tests ([#331](https://github.com/qbitartifacts/rec-api/issues/331)) ([8785be8](https://github.com/qbitartifacts/rec-api/commit/8785be84a7da44959b395b830d21a25f15211344))
* **treasure_withdrawal:** updated email template ([#334](https://github.com/qbitartifacts/rec-api/issues/334)) ([ff02cb4](https://github.com/qbitartifacts/rec-api/commit/ff02cb44e2da3a5d2c8f324ffa35cafb0c3cdafd))
* **version:** login2fa version ([#399](https://github.com/qbitartifacts/rec-api/issues/399)) ([c60872b](https://github.com/qbitartifacts/rec-api/commit/c60872b1f2a7c018693406d172f5268e9a0e613d))
* **version:** returned exact git abbreviated version instead of only the last tag ([71f32a9](https://github.com/qbitartifacts/rec-api/commit/71f32a99548777bf801942d0e85405ee0ac9152e))
* added status consts to Order entity ([9e93a13](https://github.com/qbitartifacts/rec-api/commit/9e93a13dd9464202381c972a933580b409f06b2a))
* added url_ok and url_ko to order ([98586a0](https://github.com/qbitartifacts/rec-api/commit/98586a0143a4a978d88a764d38d275b6473dd9ad))

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
