# Changelog

All notable changes to `payable` will be documented in this file

## v0.12.0 - 2025-09-01

### :rocket: Features

- add revolut payment gateway integration by @ramazanayyildiz in https://github.com/unusualify/payable/commit/fe181a08081d50402b9bc987422546654abae970
- add ShouldEmbedForm interface for payment integration by @OoBook in https://github.com/unusualify/payable/commit/0f2c75003a64058136282a960b77180ce9571889
- implement built-in form handling in PaymentService by @OoBook in https://github.com/unusualify/payable/commit/f4a0e4b2de8ad65d805ef8ccdb4b0ca8a6c5e344
- add checkout method for payment processing by @OoBook in https://github.com/unusualify/payable/commit/e75fd13c218e837f2dbcecd6bbca1b4c0ad27f0b
- enhance RevolutService with checkout payload handling and order management by @OoBook in https://github.com/unusualify/payable/commit/78dd5a91145f9795a4873185ec46c6a28f477467

### :wrench: Bug Fixes

- enhance error handling for GuzzleHttp exceptions by @OoBook in https://github.com/unusualify/payable/commit/eecfb6cf5172861ce9b803a71d75312ce30cc883

### :memo: Documentation

- add revolut payment integration guide by @ramazanayyildiz in https://github.com/unusualify/payable/commit/1040a0dea19d56f80f2c254a66385ba7f5e1ef2c

### :lipstick: Styling

- lint coding styles for v0.11.0 by @invalid-email-address in https://github.com/unusualify/payable/commit/7d145370bd31fd5161af42e96f1990a736270b82

## v0.11.0 - 2025-07-28

### :recycle: Refactors

- :recycle: update PaypalService to improve response handling and clean up commented code by @OoBook in https://github.com/unusualify/payable/commit/21be8ed2089682ba4e1cf0e1b6e482eca1394c6a
- :recycle: update BuckarooService and configuration for improved payment handling by @OoBook in https://github.com/unusualify/payable/commit/79d683299b808c268ea34679b632d947715e96b2
- :recycle: enhance Payable configuration and model integration for improved payment handling by @OoBook in https://github.com/unusualify/payable/commit/b6a591327e773c9f1a9754830f1e060bef4a9866

### :green_heart: Workflow

- update release.yml by @web-flow in https://github.com/unusualify/payable/commit/792b7ad1f8205c771d7a39c4241058973133d124

### :beers: Other Stuff

- :package: update composer.json to include development dependencies and scripts by @OoBook in https://github.com/unusualify/payable/commit/faae95d77e8cc3bab3224b33c349cf736bc8de9b
- :package: update test-coverage script in composer.json for consistency by @OoBook in https://github.com/unusualify/payable/commit/a102c846524f17d64a89718fe45d13d5fccb17a7

## v0.10.0 - 2025-05-11

### :rocket: Features

- :sparkles: add ideal-qr configuration and enhance payment handling in services by @ramazanayyildiz in https://github.com/unusualify/payable/commit/67e6b67dd5187894294db1467f841bf69a2e0909
- :sparkles: add description field to payment parameters and update expiration date handling by @ramazanayyildiz in https://github.com/unusualify/payable/commit/fe1d3363a0816598db626a95f83ba2d813dca935

### :recycle: Refactors

- :recycle: remove unused facade classes and clean up payment status handling in services, updated tebcommonpos by @ramazanayyildiz in https://github.com/unusualify/payable/commit/8c368e479a9c08a9cd41e195aa73ac08dcf93e4a
- :recycle: streamline payment service methods and enhance response handling in Payable and Paypal services by @ramazanayyildiz in https://github.com/unusualify/payable/commit/f0f352e11b488c6f42bbd57372f7b57cb09966fc
- :recycle: clean up unused code and improve parameter handling in Buckaroo and Ideal services by @ramazanayyildiz in https://github.com/unusualify/payable/commit/7e8a607b0a1aa457c74d5ed8617543b151c48ffd

### :green_heart: Workflow

- update manual-release.yml by @web-flow in https://github.com/unusualify/payable/commit/fc03a71c9a259b96fec723a004f0a5af568788cb

## v0.0.0 -

- Initial Tag
