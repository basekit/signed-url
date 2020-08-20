# Generate signed URL with an optional expiration date

[![Latest Version on Packagist](https://img.shields.io/packagist/v/basekit/signed-url.svg)](https://packagist.org/packages/basekit/signed-url)
[![GitHub Tests Action Status](https://github.com/basekit/signed-url/workflows/Tests/badge.svg)](https://github.com/basekit/signed-url/workflows/Tests/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/basekit/signed-url.svg)](https://packagist.org/packages/basekit/signed-url)

## Introduction

This package is for simple generation and validation of signed URLs.

What is a signed URL?
> A signed URL is a URL that provides limited permission and time to make a request. Signed URLs contain authentication information in their query string, allowing users without credentials to perform specific actions on a resource
[source](https://cloud.google.com/storage/docs/access-control/signed-urls)

## Installation

You can install the package via composer:

```bash
composer require basekit/signed-url
```

## Usage

### Create a signed URL with an expiration

```php
$urlSigner = new BaseKit\SignedUrl('secret');
$url = $urlSigner->sign('http://dev.app', new \DateTime("+ 10 days"));
echo $url;
// http://dev.app?expires=1597606297&signature=2bcbe00d36010bae3e6bc6e6abe79f6cbc135f360285eeb17e9c53753b4b223a"
```

### Create a signed URL without expiration

```php
$urlSigner = new BaseKit\SignedUrl('secret');
$url = $urlSigner->sign('http://dev.app');
echo $url;
// http://dev.app?expires=1597606297&signature=2bcbe00d36010bae3e6bc6e6abe79f6cbc135f360285eeb17e9c53753b4b223a"
```

### Validate a signed URL
```php
$url = "http://dev.app?expires=1597606297&signature=2bcbe00d36010bae3e6bc6e6abe79f6cbc135f360285eeb17e9c53753b4b223a";
$urlSignValidator = new BaseKit\SignedUrl('secret');
$valid = $urlSignValidator->validate($url);
var_dump($valid);
// bool(true)

```

The package will append 1 or 2 querystring parameters to the URL that represent the expiry of the link (when provided), and the signature. 
The signature itself is generated using the original url, the expiry date if provided, and a project specific secret.

It's possible to override the names of these querystring parameters in object instantiation, as below:

```php
$urlSigner = new BaseKit\SignedUrl('secret', 'expirationParam', 'secureSignature');
$url = $urlSigner->sign('http://dev.app', new \DateTime("+ 10 days"));
echo $url;
// https://www.dev.app/?expirationParam=1597608096&secureSignature=ef6839ad6b1a4cfca8e3e04bb2a74da0e9d3d9c4d9870125f499f75c9ef5d2b6
```

## Testing

``` bash
composer test
```

## Credits

- [Rob Mills](https://github.com/robjmills)
- [BaseKit](https://github.com/basekit)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
