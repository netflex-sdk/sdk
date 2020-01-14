# Netflex SDK

The Netflex SDK is a framework for constructing website using the Netflex Content API.

[![CircleCI](https://circleci.com/gh/netflex-sdk/sdk.svg?style=shield&circle-token=40188cfe2c6ab765c48f112c427785f44f3745f5)](https://circleci.com/gh/netflex-sdk/sdk)
[![Docs](https://img.shields.io/badge/docs-docs.netflex.dev-blue.svg)](https://docs.netflex.dev/docs/1.0)
[![Version](https://img.shields.io/github/tag/netflex-sdk/sdk.svg?label=version)](https://github.com/netflex-sdk/sdk/releases/latest)
[![License: MIT](https://img.shields.io/github/license/netflex-sdk/sdk.svg)](https://opensource.org/licenses/MIT)
[![Contributors](https://img.shields.io/github/contributors/netflex-sdk/sdk.svg?color=green)](https://github.com/netflex-sdk/sdk/graphs/contributors)
![Downloads](https://img.shields.io/packagist/dm/netflex-sdk/sdk.svg)

![banner](https://d3lnipq2e3xuc0.cloudfront.net/media/o/1557406595/banner.png)

## Installation

```bash
composer require netflex/sdk
```

## Usage

```php
<?php

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(Netflex\SDK::bootstrap);
```

## Running the test suite

```bash
composer run-script tests
```

## Updating snapshots

When an existing snapshot test has been updated, or its behaviour has been modified, the snapshot will have to be updated to prevent the tests from failing.

To update the snapshots, run the following command.

```bash
composer run-script tests:update-snapshots
```

<hr>

Copyright &copy; 2020 **[Apility AS](https://apility.no)**
