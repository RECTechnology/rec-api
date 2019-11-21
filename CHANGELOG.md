# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

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
