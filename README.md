# Enlighten
PHP source highlighter

## Installation

Install using Composer:

```bash
composer require decodelabs/enlighten
```

## Usage
Enlighten uses the PHP tokenizer extension which requires the full source from file (including <code>&lt;?php</code> open tag).


```php
use DecodeLabs\Enlighten\Highlighter;

$highlighter = new Highlighter();
echo $highlighter->highlight($phpSourceCode); // Highlight source code in memory
echo $highlighter->highlightFile($phpFile, 15, 35, 20); // Highlight specific lines (15 to 35) in file (focus on 20)

echo $highlighter->extract($phpSourceCode, 20); // Extract code around specific line
echo $highlighter->extractFromFile($phpFile, 20); // Extract code around specific line
```
