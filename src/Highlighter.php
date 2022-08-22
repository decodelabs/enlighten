<?php

/**
 * @package Enlighten
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Enlighten;

use DecodeLabs\Coercion;
use DecodeLabs\Exceptional;
use ParseError;
use Throwable;

class Highlighter
{
    /**
     * Extract a specific line from file with $buffer lines around it
     */
    public function extractFromFile(string $path, int $line, int $buffer = 8): string
    {
        if (!file_exists($path)) {
            return '';
        }

        if (false === ($source = file_get_contents($path))) {
            throw Exceptional::Io(
                'Could not load source from file: ' . $path
            );
        }

        return $this->extract($source, $line, $buffer);
    }

    /**
     * Extract a specific line with $buffer lines around it
     */
    public function extract(string $source, int $line, int $buffer = 8): string
    {
        $line = max(1, $line);
        $buffer = min(30, max(1, $buffer));
        $startLine = max(1, $line - $buffer);
        $endLine = $line + $buffer;

        return $this->highlight($source, $startLine, $endLine, $line);
    }

    /**
     * Highlight PHP source from file from $startLine to $endLine, focussing on $highlight
     */
    public function highlightFile(string $path, ?int $startLine = null, ?int $endLine = null, ?int $highlight = null): string
    {
        if (!file_exists($path)) {
            return '';
        }

        if (false === ($source = file_get_contents($path))) {
            throw Exceptional::Io(
                'Could not load source from file: ' . $path
            );
        }

        return $this->highlight($source, $startLine, $endLine, $highlight);
    }

    /**
     * Highlight PHP source from $startLine to $endLine, focussing on $highlight
     */
    public function highlight(string $source, ?int $startLine = null, ?int $endLine = null, ?int $highlight = null): string
    {
        try {
            return $this->processTokens($source, $startLine, $endLine, $highlight);
        } catch (Throwable $e) {
            return $this->processRaw($source, $startLine, $endLine, $highlight);
        }
    }

    /**
     * Tokenize and highlight file with full parsing
     */
    protected function processTokens(string $source, ?int $startLine = null, ?int $endLine = null, ?int $highlight = null): string
    {
        if ($startLine !== null) {
            $startLine = max(1, $startLine);
        }
        if ($endLine !== null && $startLine === null) {
            $startLine = 1;
        }

        try {
            $tokens = token_get_all($source, \TOKEN_PARSE);
        } catch (ParseError $e) {
            throw Exceptional::UnexpectedValue(
                'Unable to parse PHP source',
                ['previous' => $e],
                $source
            );
        }

        $source = '';
        $lastLine = 1;
        $history = [];

        while (!empty($tokens)) {
            $token = array_shift($tokens);
            array_unshift($history, $token);

            if (count($history) > 20) {
                array_pop($history);
            }

            if (is_array($token)) {
                $lastLine = $token[2];
                $name = substr(token_name($token[0]), 2);
                $name = strtolower(str_replace('_', '-', $name));

                if ($startLine !== null) {
                    if ($name === 'whitespace' || $name === 'doc-comment') {
                        if ($lastLine >= $endLine) {
                            $parts = explode("\x00", str_replace("\n", "\x00\n", $token[1]));
                        } else {
                            $parts = explode("\x00", str_replace("\n", "\n\x00", $token[1]));
                        }

                        $token[1] = array_shift($parts);

                        if (!empty($rem = implode($parts))) {
                            $new = $token;
                            $new[1] = $rem;
                            $new[2] += 1;
                            array_unshift($tokens, $new);
                        }
                    }

                    if ($startLine !== null && $lastLine < $startLine) {
                        continue;
                    }
                    if ($endLine !== null && $lastLine > $endLine) {
                        break;
                    }
                }

                $attrs = [];
                $name = $this->normalizeName($origName = $name);
                $tokenContent = (string)$token[1];

                switch ($origName) {
                    case 'whitespace':
                        $source .= $token[1];
                        continue 2;

                    case 'constant-encapsed-string':
                        $quote = substr($tokenContent, 0, 1);
                        $tokenContent = $token[1] = substr($tokenContent, 1, -1);
                        $attrs['data-quote'] = $quote;
                        break;

                    case 'variable':
                        if ($token[1] === '$this') {
                            $name .= ' this';
                        }
                        break;

                    case 'string':
                        $type = $this->getNameType($history, $tokens);

                        if ($type !== null) {
                            $name = $type;
                        }
                        break;
                }


                $inner = explode("\n", str_replace("\r", '', $tokenContent));

                foreach ($attrs as $key => $val) {
                    $attrs[$key] = ' ' . $key . '="' . $this->esc($val) . '"';
                }

                $attrs = implode($attrs);

                foreach ($inner as &$part) {
                    if (!empty($part)) {
                        $part = '<span class="' . $name . '"' . $attrs . '>' . $this->esc($part) . '</span>';
                    }
                }

                $source .= implode("\n", $inner);
            } else {
                if ($startLine !== null && $lastLine < $startLine) {
                    continue;
                }
                if ($endLine !== null && $lastLine > $endLine) {
                    break;
                }

                $source .= '<span class="g">' . $this->esc($token) . '</span>';
            }
        }

        $lines = explode("\n", $source);
        $output = [];
        $i = $startLine ?? 1;

        if ($startLine > 1) {
            $output[] = '<span class="line"><span class="number x">…</span></span>';
        } else {
            $output[] = '<span class="line spacer"><span class="number x"></span></span>';
        }

        foreach ($lines as $line) {
            $output[] = '<span class="line' . ($i === $highlight ? ' highlighted' : null) . '"><span class="number">' . $i . '</span>' . $line . '</span>';
            $i++;
        }

        if ($endLine !== null && $i > $endLine) {
            $output[] = '<span class="line"><span class="number x">…</span></span>';
        } else {
            $output[] = '<span class="line spacer"><span class="number x"></span></span>';
        }

        return '<samp class="source">' . implode("\n", $output) . '</samp>';
    }

    /**
     * Process raw text without parsing as a fallback
     */
    protected function processRaw(string $source, ?int $startLine = null, ?int $endLine = null, ?int $highlight = null): string
    {
        if ($startLine !== null) {
            $startLine = max(1, $startLine);
        }
        if ($endLine !== null && $startLine === null) {
            $startLine = 1;
        }

        $lines = explode("\n", str_replace("\r\n", "\n", $source));
        $count = count($lines);

        if ($endLine !== null) {
            $length = $endLine - ($startLine - 1);
        } else {
            $length = $count - ($startLine - 1);
        }

        $lines = array_slice($lines, $startLine - 1, $length, true);
        $output = [];

        if ($startLine > 1) {
            $output[] = '<span class="line"><span class="number x">…</span></span>';
        } else {
            $output[] = '<span class="line spacer"><span class="number x"></span></span>';
        }

        foreach ($lines as $i => $line) {
            $i += 1;
            $output[] = '<span class="line' . ($i === $highlight ? ' highlighted' : null) . '"><span class="number">' . $i . '</span>' . $line . '</span>';
        }

        if ($endLine !== null && $count > $endLine) {
            $output[] = '<span class="line"><span class="number x">…</span></span>';
        } else {
            $output[] = '<span class="line spacer"><span class="number x"></span></span>';
        }

        return '<samp class="source error">' . implode("\n", $output) . '</samp>';
    }

    /**
     * Attempt to parse name token type
     *
     * @param array<int, mixed> $history
     * @param array<int, mixed> $tokens
     */
    protected function getNameType(array $history, array $tokens): ?string
    {
        $current = Coercion::toArrayOrNull(array_shift($history));

        switch ($current[1] ?? null) {
            case 'null':
                return 'null';

            case 'true':
            case 'false':
                return 'bool';
        }

        $maybeFunction = $maybeClassReturn = false;

        /* @phpstan-ignore-next-line */
        switch ($tokens[0][0] ?? null) {
            case \T_OBJECT_OPERATOR:
                return 'member';

            case \T_PAAMAYIM_NEKUDOTAYIM:
                return 'class';

            case \T_NS_SEPARATOR:
                return 'namespace';

            case \T_VARIABLE:
                return 'class';

            case \T_WHITESPACE:
                /* @phpstan-ignore-next-line */
                switch ($tokens[1][0] ?? null) {
                    case \T_VARIABLE:
                    case \T_ELLIPSIS:
                        return 'class';
                }

                if ($tokens[1] === '{') {
                    $maybeClassReturn = true;
                }
                break;
        }

        if ($tokens[0] === '(') {
            $maybeFunction = true;
        }

        if (
            preg_match(
                '/^[A-Z_]+$/',
                Coercion::toStringOrNull($current[1] ?? null) ?? ''
            ) &&
            !$maybeFunction
        ) {
            return 'constant';
        }

        while (!empty($history)) {
            $token = array_shift($history);

            if (is_array($token)) {
                if ($token[0] === \T_WHITESPACE) {
                    continue;
                }

                if ($maybeFunction) {
                    switch ($token[0]) {
                        case \T_NS_SEPARATOR:
                        case \T_STRING:
                            continue 2;

                        case \T_NEW:
                            return 'class';

                        default:
                            return 'function';
                    }
                }

                switch ($token[0]) {
                    case \T_CONST:
                        return 'constant';

                    case \T_PAAMAYIM_NEKUDOTAYIM:
                        return 'constant';

                    case \T_OBJECT_OPERATOR:
                        return 'member';

                    case \T_EXTENDS:
                    case \T_IMPLEMENTS:
                    case \T_CLASS:
                    case \T_TRAIT:
                    case \T_INTERFACE:
                    case \T_USE:
                    case \T_NS_SEPARATOR:
                    case \T_INSTANCEOF:
                        return 'class';

                }

                if ($maybeClassReturn) {
                    return 'class return';
                }

                return null;
            } else {
                if ($maybeFunction) {
                    return 'function';
                } elseif ($token === ';') {
                    return null;
                }

                switch ($token) {
                    case ':':
                        if ($tokens[0] === '{' || $tokens[1] === '{') {
                            return 'class return';
                        }
                }
            }
        }

        return null;
    }

    /**
     * Escape a value for HTML
     */
    protected function esc(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }


    /**
     * Normalize name
     */
    protected function normalizeName(string $name): string
    {
        switch ($name) {
            case 'abstract':
            case 'array':
            case 'as':
            case 'class':
            case 'clone':
            case 'const':
            case 'declare':
            case 'default':
            case 'echo':
            case 'enddeclare':
            case 'extends':
            case 'final':
            case 'function':
            case 'global':
            case 'implements':
            case 'include':
            case 'include-once':
            case 'insteadof':
            case 'interface':
            case 'namespace':
            case 'new':
            case 'print':
            case 'private':
            case 'public':
            case 'protected':
            case 'require':
            case 'require-once':
            case 'static':
            case 'trait':
            case 'use':
            case 'var':
                return 'keyword ' . $name;

            case 'break':
            case 'case':
            case 'catch':
            case 'continue':
            case 'do':
            case 'else':
            case 'elseif':
            case 'endfor':
            case 'endforeach':
            case 'endif':
            case 'endswitch':
            case 'endwhile':
            case 'exit':
            case 'finally':
            case 'for':
            case 'foreach':
            case 'goto':
            case 'if':
            case 'return':
            case 'switch':
            case 'throw':
            case 'try':
            case 'while':
            case 'yield':
            case 'yield-from':
                return 'keyword flow ' . $name;

            case 'callable':
                return 'type ' . $name;

            case 'array-cast':
            case 'bool-cast':
            case 'double-cast':
            case 'int-cast':
            case 'object-cast':
            case 'string-cast':
            case 'unset-cast':
                return 'cast ' . $name;

            case 'close-tag':
            case 'open-tag':
            case 'open-tag-with-echo':
                return 'tag ' . $name;

            case 'and-equal':
            case 'boolean-and':
            case 'boolean-or':
            case 'coalesce':
            case 'concat-equal':
            case 'dec':
            case 'div-equal':
            case 'inc':
            case 'is-equal':
            case 'is-greater-or-equal':
            case 'is-identical':
            case 'is-not-equal':
            case 'is-not-identical':
            case 'is-smaller-or-equal':
            case 'spaceship':
            case 'logical-and':
            case 'logical-or':
            case 'logical-xor':
            case 'minus-equal':
            case 'mod-equal':
            case 'mul-equal':
            case 'or-equal':
            case 'paamayim-nekudotayim':
            case 'plus-equal':
            case 'pow':
            case 'pow-equal':
            case 'sl':
            case 'sl-equal':
            case 'sr':
            case 'sr-equal':
            case 'xor-equal':
                return 'operator ' . $name;

            case 'ellipsis':
            case 'instanceof':
                return 'operator special ' . $name;

            case 'bad-character':
            case 'character':
                return 'char ' . $name;

            case 'class-c':
            case 'dir':
            case 'file':
            case 'func-c':
            case 'line':
            case 'method-c':
            case 'ns-c':
            case 'trait-c':
                return 'constant ' . $name;

            case 'empty':
            case 'eval':
            case 'halt-compiler':
            case 'isset':
            case 'list':
            case 'unset':
                return 'func ' . $name;

            case 'num-string':
            case 'string-varname':
            case 'variable':
                return 'var ' . $name;

            case 'encapsed-and-whitespace':
            case 'constant-encapsed-string':
                return 'string ' . $name;

            case 'dnumber':
                return 'float';
            case 'lnumber':
                return 'int';

            case 'curly-open':
            case 'dollar-open-curly-braces':
            case 'double-arrow':
            case 'double-colon':
            case 'end-heredoc':
            case 'ns-separator':
            case 'object-operator':
            case 'start-heredoc':
            case 'whitespace':
                return 'g ' . $name;

            case 'comment':
                return $name;
            case 'doc-comment':
                return 'comment ' . $name;

            case 'inline-html':
                return 'html';

            case 'string':
                return 'name';

            default:
                return $name;
        }
    }


    /**
     * Export inline style tag
     */
    public function exportInlineStyles(): string
    {
        return '<style>' . "\n" . file_get_contents(__DIR__ . '/resources/styles.css') . "\n" . '</style>';
    }
}
