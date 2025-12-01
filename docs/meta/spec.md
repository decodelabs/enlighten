# Enlighten — Package Specification

> **Cluster:** `observability`
> **Language:** `php`
> **Milestone:** `m2`
> **Repo:** `https://github.com/decodelabs/enlighten`
> **Role:** PHP source highlighting

This document describes the purpose, contracts, and design of **Enlighten** within the Decode Labs ecosystem.

It is aimed at:

- Developers **using** Enlighten in their own applications or libraries.
- Contributors **maintaining or extending** Enlighten.
- Tools and AI assistants that need to reason about its behaviour.

---

## 1. Overview

### 1.1 Purpose

Enlighten provides exhaustive and accurate PHP source highlighting for use in debugging systems. It uses the PHP tokenizer extension to parse PHP source code and generate HTML with syntax highlighting. It provides grammar-aware token classification, name entity type detection (function names, class names, constants, etc.), line number support, line range extraction, focused line highlighting, and fallback raw processing for unparseable code. It's designed to be more thorough than PHP's built-in `highlight_string` and `highlight_file` functions.

### 1.2 Non-Goals

Enlighten does **not**:

- Provide code execution — it's a highlighting library
- Handle code analysis — it's a presentation library
- Provide code formatting — it's a highlighting library
- Handle code minification — it's a highlighting library
- Provide code validation — it's a highlighting library
- Handle code transformation — it's a highlighting library
- Provide code completion — it's a highlighting library
- Handle code refactoring — it's a highlighting library

---

## 2. Role in the Ecosystem

### 2.1 Cluster & Positioning

- **Cluster:** `observability` (see Chorus taxonomy)
- Enlighten is an observability package that provides PHP source highlighting for the Decode Labs ecosystem. It sits in the observability cluster alongside other debugging and error handling tools. It depends on Coercion and Exceptional. It's used by Glitch for error display and debugging. It provides the core functionality for displaying PHP source code with syntax highlighting in debugging interfaces.

### 2.2 Typical Usage Contexts

Typical places Enlighten appears:

- Error display systems
- Debugging interfaces
- Exception rendering
- Stack trace display
- Code inspection tools
- Development error pages
- Code documentation tools
- Source code viewers

Enlighten is intended to be used whenever code needs to display PHP source code with syntax highlighting, particularly in debugging and error handling contexts.

---

## 3. Public Surface

> This section focuses on the conceptual API, not every symbol.

### 3.1 Key Types

The primary public types are:

- `DecodeLabs\Enlighten\Highlighter`
  Main class for PHP source highlighting. Provides methods for highlighting source code, highlighting files, extracting code around specific lines, and exporting inline styles.

### 3.2 Main Entry Points

The main usage pattern is through the `Highlighter` class:

```php
use DecodeLabs\Enlighten\Highlighter;

$highlighter = new Highlighter();
echo $highlighter->highlight($phpSourceCode);
echo $highlighter->highlightFile($phpFile, 15, 35, 20);
echo $highlighter->extract($phpSourceCode, 20);
echo $highlighter->extractFromFile($phpFile, 20);
```

---

## 4. Dependencies

### 4.1 Decode Labs

- `decodelabs/coercion` (required)
  Used for type coercion when converting token values to strings and parsing token arrays.

- `decodelabs/exceptional` (required)
  Used for exception handling when file operations fail or source code cannot be parsed.

### 4.2 External

None — Enlighten has no external dependencies beyond Decode Labs packages. It uses PHP's built-in `tokenizer` extension (via `token_get_all()`).

### 4.3 Optional Integrations

None — all dependencies are required.

---

## 5. Behaviour & Contracts

### 5.1 Invariants

- Source code must include `<?php` open tag for proper tokenization
- Tokenization uses `token_get_all()` with `TOKEN_PARSE` flag
- Token parsing failures fall back to raw processing
- Line numbers are always included in output
- Line ranges are inclusive (start to end)
- Highlighted lines are marked with `highlighted` class
- Output is wrapped in `<samp class="source">` element
- All content is HTML-escaped
- CSS classes follow consistent naming pattern
- Name entity types are detected from surrounding tokens
- Grammar tokens are properly classified
- String quotes are preserved as data attributes
- Line numbers are formatted with ellipsis for truncated ranges

### 5.2 Input & Output Contracts

**Highlighter Operations:**
- `__construct()` — Creates highlighter instance
- `highlight(string $source, ?int $startLine = null, ?int $endLine = null, ?int $highlight = null): string` — Highlights source code in memory
- `highlightFile(string $path, ?int $startLine = null, ?int $endLine = null, ?int $highlight = null): string` — Highlights source code from file
- `extract(string $source, int $line, int $buffer = 8): string` — Extracts code around specific line
- `extractFromFile(string $path, int $line, int $buffer = 8): string` — Extracts code around specific line from file
- `exportInlineStyles(): string` — Exports inline CSS styles

### 5.3 Token Processing

Token processing:
- Uses `token_get_all()` with `TOKEN_PARSE` flag
- Maintains token history (last 20 tokens) for context
- Classifies tokens by type and context
- Detects name entity types from surrounding tokens
- Handles whitespace and doc comments specially for line range extraction
- Escapes all content for HTML output

### 5.4 Name Entity Type Detection

Name entity types are detected from surrounding tokens:
- `class` — Class names (after `new`, `extends`, `implements`, `instanceof`, etc.)
- `function` — Function names (after whitespace, before `(`)
- `constant` — Constants (uppercase, after `const`, `::`, etc.)
- `member` — Member access (after `->`)
- `namespace` — Namespace (after `\`)
- `class return` — Return type classes (after `:` before `{`)
- `null` — Null literal
- `bool` — Boolean literals (true/false)

### 5.5 Token Classification

Tokens are classified into categories:
- Keywords (language keywords)
- Flow keywords (control flow keywords)
- Types (type hints)
- Casts (type casts)
- Tags (PHP open/close tags)
- Operators (operators and special operators)
- Characters (bad characters, regular characters)
- Constants (magic constants)
- Functions (built-in functions)
- Variables (variables, including `$this`)
- Strings (string literals, encapsed strings)
- Numbers (integers, floats)
- Grammar (whitespace, operators, separators)
- Comments (comments, doc comments)
- HTML (inline HTML)

### 5.6 Line Range Extraction

Line range extraction:
- Extracts specific line ranges from source
- Handles whitespace and doc comments that span multiple lines
- Splits multi-line tokens at line boundaries
- Adds ellipsis markers for truncated ranges
- Includes spacer lines at start and end

### 5.7 Focused Line Highlighting

Focused line highlighting:
- Marks specific line with `highlighted` class
- Used for error line indication
- Works with line range extraction
- Provides visual emphasis in output

### 5.8 Fallback Processing

Fallback processing:
- Used when tokenization fails
- Processes source as raw text
- Still provides line numbers and highlighting
- Marks output with `error` class
- Preserves source structure

### 5.9 CSS Styling

CSS styling:
- Provided via `styles.css` resource file
- Can be exported as inline styles
- Uses CSS custom properties for theming
- Supports dark theme by default
- Provides color coding for all token types

### 5.10 HTML Output Format

HTML output format:
- Wrapped in `<samp class="source">` element
- Each line wrapped in `<span class="line">`
- Line numbers in `<span class="number">`
- Tokens wrapped in `<span class="token-type">`
- Highlighted lines have `highlighted` class
- Truncated ranges show ellipsis (`…`)
- Spacer lines for empty ranges

---

## 6. Error Handling

- Missing files return empty string from `highlightFile()` and `extractFromFile()`
- File read failures throw `Exceptional::Io`
- Token parsing failures throw `Exceptional::UnexpectedValue` (caught and fallback to raw processing)
- Invalid line numbers are clamped to valid ranges (min 1, max file length)
- Buffer size is clamped to valid range (1-30)
- Invalid source code falls back to raw processing with error class

---

## 7. Configuration & Extensibility

- Token classification can be extended via `normalizeName()` method
- Name entity type detection can be extended via `getNameType()` method
- CSS styling can be customized via CSS custom properties
- Output format can be customized by extending `Highlighter` class
- Token processing can be customized by overriding `processTokens()` method
- Raw processing can be customized by overriding `processRaw()` method

---

## 8. Interactions with Other Packages

### 8.1 Coercion

Enlighten uses Coercion for:
- Type conversion when converting token values to strings
- Parsing token arrays safely
- Handling mixed types in token processing

### 8.2 Exceptional

Enlighten uses Exceptional for:
- All exception handling
- Error reporting for file operations
- Error reporting for parsing failures

### 8.3 Glitch

Glitch uses Enlighten for:
- Displaying PHP source code in error pages
- Highlighting error lines in stack traces
- Providing syntax highlighting for debugging

### 8.4 PHP Tokenizer Extension

Enlighten uses PHP's built-in tokenizer extension:
- `token_get_all()` for tokenization
- `token_name()` for token name resolution
- `TOKEN_PARSE` flag for parsing mode
- Token constants (e.g., `T_STRING`, `T_VARIABLE`)

---

## 9. Usage Examples

### 9.1 Basic Highlighting

```php
use DecodeLabs\Enlighten\Highlighter;

$highlighter = new Highlighter();
$source = '<?php echo "Hello World";';
echo $highlighter->highlight($source);
```

### 9.2 File Highlighting

```php
use DecodeLabs\Enlighten\Highlighter;

$highlighter = new Highlighter();
echo $highlighter->highlightFile('/path/to/file.php');
```

### 9.3 Line Range Highlighting

```php
use DecodeLabs\Enlighten\Highlighter;

$highlighter = new Highlighter();
// Highlight lines 15 to 35, focus on line 20
echo $highlighter->highlightFile('/path/to/file.php', 15, 35, 20);
```

### 9.4 Code Extraction

```php
use DecodeLabs\Enlighten\Highlighter;

$highlighter = new Highlighter();
// Extract 8 lines around line 20
echo $highlighter->extract($source, 20, 8);
```

### 9.5 File Extraction

```php
use DecodeLabs\Enlighten\Highlighter;

$highlighter = new Highlighter();
// Extract 8 lines around line 20 from file
echo $highlighter->extractFromFile('/path/to/file.php', 20, 8);
```

### 9.6 Inline Styles

```php
use DecodeLabs\Enlighten\Highlighter;

$highlighter = new Highlighter();
echo $highlighter->exportInlineStyles();
```

### 9.7 Error Display

```php
use DecodeLabs\Enlighten\Highlighter;

$highlighter = new Highlighter();
try {
    // Some code that throws
} catch (Exception $e) {
    $file = $e->getFile();
    $line = $e->getLine();
    
    // Highlight error line
    echo $highlighter->highlightFile($file, $line - 5, $line + 5, $line);
}
```

### 9.8 Custom Buffer Size

```php
use DecodeLabs\Enlighten\Highlighter;

$highlighter = new Highlighter();
// Extract 15 lines around line 50
echo $highlighter->extract($source, 50, 15);
```

### 9.9 Full File Highlighting

```php
use DecodeLabs\Enlighten\Highlighter;

$highlighter = new Highlighter();
// Highlight entire file
echo $highlighter->highlightFile('/path/to/file.php');
```

### 9.10 Source Code Highlighting

```php
use DecodeLabs\Enlighten\Highlighter;

$highlighter = new Highlighter();
$source = '<?php
class MyClass {
    public function hello() {
        return "World";
    }
}';
echo $highlighter->highlight($source);
```

---

## 10. Implementation Notes (for Contributors)

### 10.1 Token Processing

Token processing:
- Uses `token_get_all()` with `TOKEN_PARSE` flag
- Maintains history of last 20 tokens for context
- Handles both array tokens and string tokens
- Tracks line numbers for range extraction
- Splits multi-line tokens at line boundaries

### 10.2 Name Entity Type Detection

Name entity type detection:
- Analyzes token history and upcoming tokens
- Detects context from surrounding tokens
- Handles special cases (null, bool, constants)
- Checks for operators, keywords, and punctuation
- Returns specific type or null for default

### 10.3 Token Classification

Token classification:
- Normalizes token names to CSS class names
- Groups related tokens (keywords, operators, etc.)
- Handles special cases (casts, tags, constants)
- Provides hierarchical class names (e.g., `keyword flow`)

### 10.4 Line Range Extraction

Line range extraction:
- Clamps line numbers to valid ranges
- Handles whitespace and doc comments specially
- Splits tokens at line boundaries
- Adds ellipsis markers for truncated ranges
- Includes spacer lines for formatting

### 10.5 HTML Escaping

HTML escaping:
- Uses `htmlspecialchars()` with `ENT_QUOTES` and `UTF-8`
- Escapes all token content
- Preserves string quotes as data attributes
- Escapes attribute values

### 10.6 CSS Styling

CSS styling:
- Uses CSS custom properties for theming
- Provides dark theme by default
- Supports color coding for all token types
- Includes line number styling
- Includes highlighted line styling

### 10.7 Fallback Processing

Fallback processing:
- Used when tokenization fails
- Processes source as raw text
- Still provides line numbers
- Marks output with error class
- Preserves source structure

### 10.8 Output Format

Output format:
- Wrapped in semantic HTML (`<samp>`)
- Each line is a block element
- Line numbers are inline elements
- Tokens are inline elements with classes
- Supports pretty printing and highlighting

---

## 11. Testing & Quality

- **Code Quality Score:** 2.5/5
- **README Quality Score:** 2.5/5
- **Documentation Score:** 0/5 (this spec)
- **Test Coverage Score:** 0/5

See `composer.json` for supported PHP versions.

---

## 12. Roadmap & Future Ideas

- Improve code quality and test coverage
- Add more comprehensive token classification
- Consider adding code folding support
- Consider adding search and highlight support
- Improve documentation and usage examples
- Add more CSS theme options
- Consider adding line number click handlers
- Consider adding copy-to-clipboard functionality
- Add more name entity type detection
- Improve error handling and recovery
- Consider adding code analysis integration
- Add more customization options

---

## 13. References

- [Coercion Package](https://github.com/decodelabs/coercion) — Type conversion
- [Exceptional Package](https://github.com/decodelabs/exceptional) — Exception handling
- [Glitch Package](https://github.com/decodelabs/glitch) — Error handling (uses Enlighten)
- [PHP Tokenizer Extension](https://www.php.net/manual/en/book.tokenizer.php) — PHP tokenizer documentation
- [Chorus Package Index](../../../chorus/config/packages.json) — Ecosystem metadata

