# Enlighten

[![PHP from Packagist](https://img.shields.io/packagist/php-v/decodelabs/enlighten?style=flat-square)](https://packagist.org/packages/decodelabs/enlighten)
[![Latest Version](https://img.shields.io/packagist/v/decodelabs/enlighten.svg?style=flat-square)](https://packagist.org/packages/decodelabs/enlighten)
[![Total Downloads](https://img.shields.io/packagist/dt/decodelabs/enlighten.svg?style=flat-square)](https://packagist.org/packages/decodelabs/enlighten)
[![Build Status](https://img.shields.io/travis/com/decodelabs/enlighten/main.svg?style=flat-square)](https://travis-ci.com/decodelabs/enlighten)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-44CC11.svg?longCache=true&style=flat-square)](https://github.com/phpstan/phpstan)
[![License](https://img.shields.io/packagist/l/decodelabs/enlighten?style=flat-square)](https://packagist.org/packages/decodelabs/enlighten)

PHP source highlighter

## Installation

Install using Composer:

```bash
composer require decodelabs/enlighten
```

### PHP version

_Please note, the final v1 releases of all Decode Labs libraries will target **PHP8** or above._

Current support for earlier versions of PHP will be phased out in the coming months.


## Usage
Enlighten uses the PHP tokenizer extension which requires the full source from file (including <code>&lt;?php</code> open tag).

It is effectively a more thorough version of <code>highlight_string</code> and <code>highlight_file</code> - grammar is properly wrapped and name entities are parsed according to their surrounding tokens to work out what _type_ they are (function name, class name, etc, etc).

Enlighten also offers the ability to extract certain portions of the code and focus on a specific line. Line numbers are included in the output HTML to aid in readability.

```php
use DecodeLabs\Enlighten\Highlighter;

$highlighter = new Highlighter();
echo $highlighter->highlight($phpSourceCode); // Highlight source code in memory
echo $highlighter->highlightFile($phpFile, 15, 35, 20); // Highlight specific lines (15 to 35) in file (focus on 20)

echo $highlighter->extract($phpSourceCode, 20); // Extract code around specific line
echo $highlighter->extractFromFile($phpFile, 20); // Extract code around specific line
```


## Licensing
Enlighten is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
